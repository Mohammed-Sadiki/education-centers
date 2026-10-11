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
        'related_type','related_id','read_at',
    ];
    protected function casts(): array {
        return ['read_at' => 'datetime'];
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public function isRead(): bool { return $this->read_at !== null; }
    public function markAsRead(): void {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}