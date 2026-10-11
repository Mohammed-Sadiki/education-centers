<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ContactMessage extends Model {
    use HasFactory;
    protected $table = 'contact_messages';
    protected $fillable = [
        'center_id','name','email','phone','subject','message',
        'status','reply','replied_at','ip_address',
    ];
    protected function casts(): array {
        return ['replied_at' => 'datetime'];
    }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
}