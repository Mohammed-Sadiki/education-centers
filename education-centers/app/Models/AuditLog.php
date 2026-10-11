<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AuditLog extends Model {
    protected $fillable = [
        'user_id','center_id','action','auditable_type','auditable_id',
        'old_values','new_values','ip_address','user_agent','description',
    ];
    public $timestamps = false;
    protected function casts(): array {
        return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public static function record(string $action, ?Model $model = null, array $oldValues = [], array $newValues = [], ?string $description = null): void {
        $user = auth()->user();
        static::create([
            'user_id'        => $user?->id,
            'center_id'      => $user?->center_id,
            'action'         => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model?->id,
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
            'description'    => $description,
        ]);
    }
}