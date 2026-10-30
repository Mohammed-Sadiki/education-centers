<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AppNotification extends Model {
    use HasFactory;
    protected $table = 'app_notifications';
    protected $fillable = [
        'user_id','center_id','type','title','message','icon','link',
        'related_type','related_id','metadata','fingerprint','read_at',
    ];
    protected function casts(): array {
        return [
            'read_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public function isRead(): bool { return $this->read_at !== null; }
    public function markAsRead(): void {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Icône (composant <x-icon>) recommandée selon le type de notification.
     * Le stockage historique conserve des classes bootstrap (« bi-* ») dans
     * `icon` ; on ne casse rien : on dérive l'icône d'affichage depuis le type,
     * avec repli sur la cloche.
     */
    public static function iconForType(?string $type): string {
        return match ($type) {
            'message' => 'message',
            'enrollment', 'stagiaire' => 'users',
            'dossier_incomplete' => 'warning',
            'payment' => 'banknotes',
            'payment_due', 'payment_overdue' => 'clock',
            'expense' => 'arrow-trending-down',
            'salary' => 'briefcase',
            'teacher_assignment' => 'academic',
            'schedule_change' => 'calendar',
            'attendance' => 'clipboard',
            'certificate' => 'certificate',
            'announcement' => 'megaphone',
            default => 'bell',
        };
    }
}
