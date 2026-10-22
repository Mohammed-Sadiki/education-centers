<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Center;
use App\Models\ContentTranslation;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\Formation;
use App\Services\ContentTranslationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentTranslationController extends Controller
{
    /**
     * Translatable models exposed in the correction screen.
     *
     * @var array<class-string<Model>, string>
     */
    private const TYPES = [
        Formation::class => 'Formations',
        Announcement::class => 'Annonces',
        Center::class => 'Centres',
        ExpenseCategory::class => 'Catégories de dépenses',
        Expense::class => 'Dépenses',
        ExpenseItem::class => 'Articles de dépenses',
    ];

    public function index(Request $request): View
    {
        $type = (string) $request->query('type', Formation::class);
        if (! array_key_exists($type, self::TYPES)) {
            $type = Formation::class;
        }

        /** @var Model $model */
        $model = new $type;
        $fields = method_exists($model, 'translatableFields') ? $model->translatableFields() : [];
        $search = trim((string) $request->query('q', ''));

        $query = $type::query();

        if ($search !== '' && $fields !== []) {
            $query->where(function ($query) use ($fields, $search): void {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }

        $records = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $locales = (array) config('translation.target_locales', ['ar']);

        $translations = ContentTranslation::query()
            ->where('translatable_type', $model->getMorphClass())
            ->whereIn('translatable_id', $records->pluck('id'))
            ->whereIn('locale', $locales)
            ->get()
            ->keyBy(fn (ContentTranslation $translation): string => $translation->translatable_id.'|'.$translation->field.'|'.$translation->locale);

        return view('super-admin.translations.index', [
            'types' => self::TYPES,
            'type' => $type,
            'fields' => $fields,
            'records' => $records,
            'translations' => $translations,
            'locales' => $locales,
            'search' => $search,
        ]);
    }

    public function store(Request $request, ContentTranslationService $service): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
            'id' => ['required', 'integer'],
            'field' => ['required', 'string'],
            'locale' => ['required', 'string', Rule::in((array) config('translation.target_locales', ['ar']))],
            'value' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var Model $model */
        $model = $data['type']::findOrFail($data['id']);

        if (! in_array($data['field'], $model->translatableFields(), true)) {
            abort(422);
        }

        $value = trim((string) ($data['value'] ?? ''));

        if ($value === '') {
            $service->delete($model, $data['field'], $data['locale']);

            return back()->with('success', __('Traduction supprimée.'));
        }

        $service->store($model, $data['field'], $data['locale'], $value, 'manual');

        return back()->with('success', __('Traduction enregistrée.'));
    }

    public function autoTranslate(Request $request, ContentTranslationService $service): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
            'id' => ['required', 'integer'],
            'field' => ['required', 'string'],
            'locale' => ['required', 'string', Rule::in((array) config('translation.target_locales', ['ar']))],
        ]);

        /** @var Model $model */
        $model = $data['type']::findOrFail($data['id']);

        if (! in_array($data['field'], $model->translatableFields(), true)) {
            abort(422);
        }

        $source = (string) config('translation.source_locale', 'fr');
        $original = $model->getAttribute($data['field']);

        if (! is_string($original) || trim($original) === '') {
            return back()->with('error', __('Aucun contenu à traduire.'));
        }

        $translated = $service->translateText($original, $source, $data['locale']);

        if ($translated === null) {
            return back()->with('error', __('Traduction automatique indisponible. Vérifiez la configuration du fournisseur.'));
        }

        $service->store($model, $data['field'], $data['locale'], $translated, 'machine');

        return back()->with('success', __('Traduction automatique générée.'));
    }
}
