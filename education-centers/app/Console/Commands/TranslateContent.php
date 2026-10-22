<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Center;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\Formation;
use App\Services\ContentTranslationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class TranslateContent extends Command
{
    /**
     * @var string
     */
    protected $signature = 'content:translate
        {--type= : Limit to one model (Formation, Announcement, Center, ExpenseCategory, Expense, ExpenseItem)}
        {--locale= : Target locale (defaults to configured target locales)}
        {--force : Re-translate fields that already have a translation}
        {--limit=0 : Maximum number of records per model (0 = all)}';

    /**
     * @var string
     */
    protected $description = 'Generate missing translations for dynamic content (formations, announcements, centers, expenses)';

    /**
     * @var array<class-string<Model>, string>
     */
    private const TYPES = [
        Formation::class => 'Formation',
        Announcement::class => 'Announcement',
        Center::class => 'Center',
        ExpenseCategory::class => 'ExpenseCategory',
        Expense::class => 'Expense',
        ExpenseItem::class => 'ExpenseItem',
    ];

    public function handle(ContentTranslationService $service): int
    {
        if (! $service->isAvailable()) {
            $this->error('No translation provider is configured. Set GOOGLE_TRANSLATE_API_KEY or DEEPL_API_KEY in .env');

            return self::FAILURE;
        }

        $types = self::TYPES;
        $requested = (string) $this->option('type');

        if ($requested !== '') {
            $types = array_filter(
                self::TYPES,
                fn (string $class, string $short): bool => strcasecmp($short, $requested) === 0 || strcasecmp($class, $requested) === 0,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($types === []) {
                $this->error('Unknown type: '.$requested);

                return self::FAILURE;
            }
        }

        $locales = $this->option('locale')
            ? [(string) $this->option('locale')]
            : (array) config('translation.target_locales', ['ar']);

        $source = (string) config('translation.source_locale', 'fr');
        $force = (bool) $this->option('force');
        $limit = (int) $this->option('limit');

        $this->info('Provider: '.$service->providerName().' — target: '.implode(', ', $locales));

        $translated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($types as $class => $short) {
            /** @var Model $model */
            $model = new $class;
            $fields = method_exists($model, 'translatableFields') ? $model->translatableFields() : [];

            $query = $class::query()->orderBy('id');
            if ($limit > 0) {
                $query->limit($limit);
            }

            $query->chunkById(100, function ($records) use ($fields, $locales, $source, $force, $service, $short, &$translated, &$skipped, &$failed): void {
                foreach ($records as $record) {
                    foreach ($fields as $field) {
                        $original = $record->getAttribute($field);

                        if (! is_string($original) || trim($original) === '') {
                            continue;
                        }

                        foreach ($locales as $locale) {
                            if (! $force && $record->translationFor($field, $locale) !== null) {
                                $skipped++;

                                continue;
                            }

                            $value = $service->translateText($original, $source, $locale);

                            if ($value === null) {
                                $failed++;
                                $this->warn(sprintf('%s #%s — %s: translation failed', $short, $record->getKey(), $field));

                                continue;
                            }

                            $service->store($record, $field, $locale, $value, 'machine');
                            $translated++;
                        }
                    }
                }
            });
        }

        $this->newLine();
        $this->info("Translated: {$translated} — skipped: {$skipped} — failed: {$failed}");

        return $failed > 0 && $translated === 0 ? self::FAILURE : self::SUCCESS;
    }
}
