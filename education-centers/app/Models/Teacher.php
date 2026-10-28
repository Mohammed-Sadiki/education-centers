<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    use HasFactory;
    use \App\Concerns\HasDomainScope;
    use \App\Models\Concerns\HasContextScope;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'center_id',
        'domain',
        'formation_id',
        'teacher_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'nationality',
        'address',
        'postal_code',
        'city',
        'country',
        'phone',
        'specialization',
        'qualification',
        'monthly_salary',
        'hire_date',
        'notes',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth'  => 'date',
            'hire_date'      => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    /**
     * Get the user account for this teacher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the educational center for this teacher.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Formation principale enseignée par ce professeur.
     */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    /**
     * Classes auxquelles ce professeur est affecté.
     */
    public function classes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_teacher', 'teacher_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Relation : Séances d'emploi du temps du professeur.
     */
    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Fiches de salaire du professeur (module Finance existant).
     */
    public function salaries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherSalary::class);
    }

    /**
     * Relation vers les modèles Domain via le pivot teacher_domains.
     */
    public function teachingDomains(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'teacher_domains', 'teacher_id', 'domain_slug', 'id', 'slug')
            ->withTimestamps();
    }

    /**
     * Synchronise les domaines d'enseignement d'un professeur.
     * Met également à jour la colonne `domain` (premier domaine ou null) pour rétro-compatibilité.
     *
     * @param array<int, string> $slugs
     */
    public function syncTeachingDomains(array $slugs): void
    {
        $validSlugs = Domain::whereIn('slug', $slugs)->pluck('slug')->toArray();
        $this->teachingDomains()->sync($validSlugs);

        $primary = $validSlugs[0] ?? null;
        if ($this->domain !== $primary) {
            $this->domain = $primary;
            $this->save();
        }
    }

    /**
     * Un professeur peut enseigner dans plusieurs domaines :
     * son domaine principal (colonne `domain`), les domaines déclarés via `teacher_domains`
     * OU tout domaine de classe auquel il est affecté via le pivot `class_teacher`.
     */
    public function scopeForDomain(\Illuminate\Database\Eloquent\Builder $query, ?string $slug): \Illuminate\Database\Eloquent\Builder
    {
        if (! $slug || $slug === 'all') {
            return $query;
        }

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($slug) {
            $q->where('domain', $slug)
                ->orWhereHas('teachingDomains', fn (\Illuminate\Database\Eloquent\Builder $d) => $d->where('domains.slug', $slug))
                ->orWhereHas('classes.formation', fn (\Illuminate\Database\Eloquent\Builder $f) => $f->where('domain', $slug));
        });
    }

    /**
     * Slugs des domaines d'un professeur (pivot teacher_domains + domaine principal + classes affectées).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function domainSlugs(): \Illuminate\Support\Collection
    {
        $fromPivot = \Illuminate\Support\Facades\DB::table('teacher_domains')
            ->where('teacher_id', $this->id)
            ->pluck('domain_slug');

        $fromClasses = $this->classes()
            ->with('formation:id,domain')
            ->get()
            ->pluck('formation.domain');

        return $fromPivot
            ->concat($fromClasses)
            ->push($this->domain)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Domaines (modèles) d'un professeur.
     */
    public function domains(): \Illuminate\Support\Collection
    {
        return Domain::whereIn('slug', $this->domainSlugs())->ordered()->get();
    }

    /**
     * Check if the teacher is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the teacher is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Accessor for full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Generate a unique teacher number based on center code.
     * Format: {CENTER_CODE}-T-{000001}
     * Examples: BER-T-000001, MUN-T-000001
     */
    public static function generateTeacherNumber(Center $center): string
    {
        $code = strtoupper($center->code);
        $count = self::where('center_id', $center->id)->count() + 1;
        $teacherNumber = sprintf('%s-T-%06d', $code, $count);

        while (self::where('teacher_number', $teacherNumber)->exists()) {
            $count++;
            $teacherNumber = sprintf('%s-T-%06d', $code, $count);
        }

        return $teacherNumber;
    }
}
