<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Rôles disponibles.
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_CENTER_ADMIN = 'center_admin';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_STUDENT = 'student';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_CENTER_ADMIN,
        self::ROLE_TEACHER,
        self::ROLE_STUDENT,
    ];

    /**
     * Statuts disponibles.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Champs autorisés à la masse assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'center_id',
        'phone',
        'status',
        'locale',
    ];

    /**
     * Champs masqués dans la sérialisation.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts automatiques.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Règles de cohérence métier à la sauvegarde du modèle.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // Par défaut, si aucun rôle n'est spécifié
            if (empty($user->role)) {
                $user->role = empty($user->center_id) ? self::ROLE_SUPER_ADMIN : self::ROLE_STUDENT;
            }

            // Un super_admin ne doit JAMAIS avoir de center_id
            if ($user->role === self::ROLE_SUPER_ADMIN) {
                $user->center_id = null;
            } else {
                // Pour tout utilisateur non super_admin (center_admin, teacher, student) : center_id est obligatoire
                if (empty($user->center_id)) {
                    throw new \InvalidArgumentException(
                        __('L\'utilisateur avec le rôle [:role] doit obligatoirement être rattaché à un centre (center_id requis).', ['role' => $user->role])
                    );
                }
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Relations
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Un utilisateur appartient à un centre.
     * Retourne null pour le Super Admin (center_id = NULL).
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Un utilisateur étudiant possède un dossier étudiant (0 ou 1).
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Un utilisateur professeur possède un dossier professeur (0 ou 1).
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function totalUnreadMessagesCount(): int
    {
        $participants = $this->conversationParticipants()->get();
        $count = 0;
        foreach ($participants as $participant) {
            $query = Message::where('conversation_id', $participant->conversation_id)
                ->where('sender_id', '!=', $this->id);
            if ($participant->last_read_at) {
                $query->where('created_at', '>', $participant->last_read_at);
            }
            $count += $query->count();
        }

        return $count;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Vérification des Rôles
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Détermine si l'utilisateur est un Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Détermine si l'utilisateur est un Admin de Centre.
     */
    public function isCenterAdmin(): bool
    {
        return $this->role === self::ROLE_CENTER_ADMIN;
    }

    /**
     * Détermine si l'utilisateur est un Professeur.
     */
    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    /**
     * Détermine si l'utilisateur est un Élève.
     */
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * Détermine si l'utilisateur a un rôle spécifique parmi une liste.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($this->role, $roles, true);
    }

    /**
     * Détermine si l'utilisateur appartient à un centre spécifique.
     */
    public function belongsToCenter(int $centerId): bool
    {
        return $this->center_id === $centerId;
    }

    /**
     * Détermine si le compte utilisateur est actif.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Détermine si le compte utilisateur est inactif.
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }
}
