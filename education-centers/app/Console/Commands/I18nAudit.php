<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class I18nAudit extends Command
{
    /**
     * @var string
     */
    protected $signature = 'i18n:audit
        {--json : Output the report as JSON}
        {--strict : Return a non-zero exit code when issues are found}';

    /**
     * @var string
     */
    protected $description = 'Audit translation keys and hardcoded French strings in views and PHP files';

    /**
     * French words used to detect untranslated copy. Accented characters are
     * already a strong signal, so these are only used for unaccented text.
     *
     * @var array<int, string>
     */
    private array $frenchWords = [
        'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'et', 'ou', 'pour',
        'avec', 'sur', 'dans', 'par', 'au', 'aux', 'est', 'sont', 'cette', 'ce',
        'ces', 'votre', 'vos', 'notre', 'nos', 'nous', 'vous', 'aucun', 'aucune',
        'nouveau', 'nouvelle', 'tous', 'toutes', 'tout', 'toute', 'plus', 'moins',
        'ajouter', 'modifier', 'supprimer', 'enregistrer', 'annuler', 'retour',
        'rechercher', 'aucune', 'aucun', 'bienvenue', 'connexion', 'mot',
        'total', 'date', 'nom', 'adresse', 'email', 'téléphone', 'telephone',
        'statut', 'statuts', 'actions', 'détails', 'details', 'créer', 'creer',
        'fermer', 'suivant', 'précédent', 'precedent', 'oui', 'non', 'actif',
        'inactif', 'payé', 'paye', 'impayé', 'impaye', 'filtre', 'exporter',
        'imprimer', 'télécharger', 'telecharger', 'voir', 'éditer', 'editer',
        'valider', 'refuser', 'accepter', 'brouillon', 'semaine', 'mois',
    ];

    public function handle(): int
    {
        $keys = $this->collectTranslationKeys();
        $fr = $this->loadJsonMap('fr');
        $ar = $this->loadJsonMap('ar');

        $missingFr = $this->missingKeys($keys, $fr);
        $missingAr = $this->missingKeys($keys, $ar);
        $hardcoded = $this->findHardcodedFrench();

        $report = [
            'translation_keys_found' => $keys->count(),
            'fr_entries' => $fr->count(),
            'ar_entries' => $ar->count(),
            'missing_fr' => $missingFr->values()->all(),
            'missing_ar' => $missingAr->values()->all(),
            'hardcoded_total' => $hardcoded->count(),
            'hardcoded_by_file' => $hardcoded
                ->groupBy('file')
                ->map(fn (Collection $items): int => $items->count())
                ->sortDesc()
                ->all(),
            'hardcoded_samples' => $hardcoded->take(50)->values()->all(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report);
        }

        $this->info('Translation keys found: '.$report['translation_keys_found']);
        $this->line('French entries: '.$report['fr_entries'].' — Arabic entries: '.$report['ar_entries']);
        $this->newLine();

        $this->reportMissing('Missing from lang/fr.json', $missingFr);
        $this->reportMissing('Missing from lang/ar.json', $missingAr);

        $this->line('<fg=yellow>Hardcoded French candidates: '.$hardcoded->count().'</>');
        foreach (array_slice($report['hardcoded_by_file'], 0, 25, true) as $file => $count) {
            $this->line(sprintf('  %-70s %d', $this->relativePath($file), $count));
        }

        if ($hardcoded->isNotEmpty()) {
            $this->newLine();
            $this->line('<comment>Sample:</comment>');
            foreach ($hardcoded->take(15) as $hit) {
                $this->line(sprintf('  %s:%d  %s', $this->relativePath($hit['file']), $hit['line'], mb_strimwidth($hit['text'], 0, 90, '…')));
            }
        }

        return $this->exitCode($report);
    }

    /**
     * @return Collection<int, string>
     */
    private function collectTranslationKeys(): Collection
    {
        $pattern = '/(?:\b(?:__|trans|trans_choice)|@lang)\s*\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s';
        $keys = collect();

        foreach ($this->sourceFiles() as $file) {
            $contents = File::get($file->getRealPath());

            if (preg_match_all($pattern, $contents, $matches)) {
                foreach ($matches[2] as $key) {
                    $key = $this->unescape($key);

                    if ($key === '' || $this->isPhpTranslationFileKey($key)) {
                        continue;
                    }

                    $keys->push($key);
                }
            }
        }

        return $keys->unique()->sort()->values();
    }

    private function isPhpTranslationFileKey(string $key): bool
    {
        if (! preg_match('/^([a-z0-9_-]+)\.(.+)$/i', $key, $matches)) {
            return false;
        }

        return File::exists(lang_path('fr/'.$matches[1].'.php'));
    }

    /**
     * @return Collection<string, string>
     */
    private function loadJsonMap(string $locale): Collection
    {
        $path = lang_path($locale.'.json');

        if (! File::exists($path)) {
            return collect();
        }

        /** @var array<string, string> $decoded */
        $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return collect($decoded);
    }

    /**
     * @param  Collection<int, string>  $keys
     * @param  Collection<string, string>  $map
     * @return Collection<int, string>
     */
    private function missingKeys(Collection $keys, Collection $map): Collection
    {
        return $keys->reject(fn (string $key): bool => $map->has($key))->values();
    }

    private function reportMissing(string $title, Collection $missing): void
    {
        if ($missing->isEmpty()) {
            $this->line('<fg=green>'.$title.': none</>');

            return;
        }

        $this->line('<fg=red>'.$title.': '.$missing->count().'</>');
        foreach ($missing->take(40) as $key) {
            $this->line('  - '.mb_strimwidth($key, 0, 120, '…'));
        }
        $this->newLine();
    }

    /**
     * @return Collection<int, array{file: string, line: int, text: string}>
     */
    private function findHardcodedFrench(): Collection
    {
        $hits = collect();

        foreach ($this->sourceFiles() as $file) {
            $path = $file->getRealPath();

            // Hardcoded copy is only relevant in Blade templates. PHP classes
            // are covered by the translation-key inventory instead.
            if (! str_ends_with($path, '.blade.php')) {
                continue;
            }

            $relative = $this->relativePath($path);
            $lines = preg_split('/\R/', File::get($path)) ?: [];
            $inBladeComment = false;
            $inPhpBlock = false;
            $inScript = false;
            $inStyle = false;

            foreach ($lines as $index => $line) {
                $trimmed = trim($line);

                if ($inBladeComment) {
                    if (str_contains($trimmed, '--}}')) {
                        $inBladeComment = false;
                    }

                    continue;
                }

                if (str_contains($trimmed, '{{--')) {
                    if (! str_contains($trimmed, '--}}')) {
                        $inBladeComment = true;
                    }

                    continue;
                }

                if (preg_match('/^@php\b/', $trimmed)) {
                    if (! str_contains($trimmed, '@endphp')) {
                        $inPhpBlock = true;
                    }

                    continue;
                }

                if ($inPhpBlock) {
                    if (str_contains($trimmed, '@endphp')) {
                        $inPhpBlock = false;
                    }

                    continue;
                }

                if (preg_match('/<script\b/i', $trimmed)) {
                    $inScript = ! str_contains($trimmed, '</script>');
                } elseif ($inScript) {
                    if (str_contains($trimmed, '</script>')) {
                        $inScript = false;
                    }

                    continue;
                }

                if (preg_match('/<style\b/i', $trimmed)) {
                    $inStyle = ! str_contains($trimmed, '</style>');
                } elseif ($inStyle) {
                    if (str_contains($trimmed, '</style>')) {
                        $inStyle = false;
                    }

                    continue;
                }

                // Attribute continuations from multi-line HTML tags.
                if (preg_match('/^\s*[a-zA-Z_:@.-]+\s*=\s*["\']/', $trimmed)) {
                    continue;
                }

                if (str_contains($trimmed, '}}') && ! str_contains($trimmed, '>')) {
                    continue;
                }

                // Remnants of a multi-line tag or PHP expression.
                if (str_contains($trimmed, '<') && ! str_contains($trimmed, '>')) {
                    continue;
                }

                if (preg_match('/^\s*[\'"]/', $trimmed)) {
                    continue;
                }

                if (preg_match('/(?:->|===|=>|icon=|@click|@change|@submit|@js\()/', $trimmed)) {
                    continue;
                }

                if (preg_match('/^\s*[A-Za-z0-9_.-]+\s*\}\}/', $trimmed)) {
                    continue;
                }

                foreach ($this->hardcodedFragments($line) as $fragment) {
                    if ($this->fragmentIsTranslated($line, $fragment)) {
                        continue;
                    }

                    if ($this->looksFrench($fragment)) {
                        $hits->push([
                            'file' => $path,
                            'line' => $index + 1,
                            'text' => trim(preg_replace('/\s+/', ' ', $fragment) ?? ''),
                        ]);
                    }
                }
            }
        }

        return $hits;
    }

    /**
     * Extract text fragments that are not already inside a translation call.
     *
     * @return array<int, string>
     */
    private function hardcodedFragments(string $line): array
    {
        // Ignore pure PHP, comments and lines that already translate their copy.
        if (preg_match('/^\s*(\/\/|\*|\/\*|#)/', $line)) {
            return [];
        }

        $fragments = [];

        // Visible text nodes: >text<
        if (preg_match_all('/>([^<>{}@]+)</u', $line, $matches)) {
            foreach ($matches[1] as $text) {
                $fragments[] = $text;
            }
        }

        // Text that starts after a tag opened on a previous line. Strip tags
        // and Blade expressions, then keep whatever plain copy remains.
        $plain = preg_replace('/<[^>]*>/u', ' ', $line) ?? '';
        $plain = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/u', ' ', $plain) ?? '';
        $plain = preg_replace('/@\w+(?:\([^)]*\))?/u', ' ', $plain) ?? '';
        $plain = trim($plain);

        if ($plain !== '' && preg_match('/[A-Za-zÀ-ÿ]/u', $plain)) {
            $fragments[] = $plain;
        }

        // User-facing attributes.
        if (preg_match_all('/\b(?:placeholder|title|alt|aria-label|data-confirm|data-title)="([^"{}]+)"/u', $line, $matches)) {
            foreach ($matches[1] as $text) {
                $fragments[] = $text;
            }
        }

        // JavaScript string literals inside alert()/confirm().
        if (preg_match_all('/\b(?:alert|confirm)\(\s*[\'"]([^\'"]+)[\'"]/u', $line, $matches)) {
            foreach ($matches[1] as $text) {
                $fragments[] = $text;
            }
        }

        // Alpine-bound labels: :title="'...'" / :aria-label="'...'".
        if (preg_match_all('/:(?:title|aria-label|placeholder)="([^"]*)"/u', $line, $matches)) {
            foreach ($matches[1] as $expression) {
                if (str_contains($expression, '__(') || str_contains($expression, '@js')) {
                    continue;
                }

                if (preg_match_all('/[\'"]([^\'"]{4,})[\'"]/u', $expression, $literals)) {
                    foreach ($literals[1] as $text) {
                        $fragments[] = $text;
                    }
                }
            }
        }

        return array_filter($fragments, fn (string $fragment): bool => mb_strlen(trim($fragment)) > 2);
    }

    /**
     * Whether a fragment is already inside a translation call on the same line.
     */
    private function fragmentIsTranslated(string $line, string $fragment): bool
    {
        $quoted = preg_quote(trim($fragment), '/');

        return (bool) preg_match('/(?:__|trans|trans_choice|@lang)\s*\(\s*[\'"]'.$quoted.'[\'"]/u', $line);
    }

    private function looksFrench(string $text): bool
    {
        $text = trim($text);

        if ($text === '' || ! preg_match('/[A-Za-zÀ-ÿ]/u', $text)) {
            return false;
        }

        // Already localized or generated markup.
        if (preg_match('/^[\s\d\W]*$|__\(|@js|@lang|\{\{|@\w+|=>|::|\$|;\s*$/', $text)) {
            return false;
        }

        // Accented characters are a strong French signal.
        if (preg_match('/[àâçéèêëîïôùûüÿœæ]/iu', $text)) {
            return true;
        }

        // Otherwise require at least two common French words, or one
        // unambiguous action/label word (\"Annuler\", \"Total\", \"Date\"...).
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matches = array_intersect($words, $this->frenchWords);

        if (count($matches) >= 2) {
            return true;
        }

        return count($matches) === 1 && mb_strlen((string) reset($matches)) >= 3;
    }

    /**
     * @return Collection<int, SplFileInfo>
     */
    private function sourceFiles(): Collection
    {
        $files = collect();

        foreach ([resource_path('views'), app_path()] as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if (! in_array($file->getExtension(), ['php'], true)) {
                    continue;
                }

                // Never audit the audit command itself: its regex literals
                // contain translation-like sequences.
                if ($file->getFilename() === 'I18nAudit.php') {
                    continue;
                }

                $files->push($file);
            }
        }

        return $files;
    }

    private function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }

    private function unescape(string $value): string
    {
        return str_replace(['\\\\', '\\\'', '\\"'], ['\\', '\'', '"'], $value);
    }

    /**
     * @param  array{missing_fr: array<int, string>, missing_ar: array<int, string>, hardcoded_total: int}  $report
     */
    private function exitCode(array $report): int
    {
        if (! $this->option('strict')) {
            return self::SUCCESS;
        }

        $hasIssues = $report['missing_fr'] !== []
            || $report['missing_ar'] !== []
            || $report['hardcoded_total'] > 0;

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }
}
