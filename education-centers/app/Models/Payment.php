<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Payment extends Model {
    use HasFactory;
    protected $fillable = [
        'center_id','student_id','enrollment_id','formation_id','invoice_id',
        'amount','currency','payment_date','method','reference','status','notes','recorded_by',
    ];
    protected function casts(): array {
        return ['payment_date' => 'date', 'amount' => 'decimal:2'];
    }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function formation(): BelongsTo { return $this->belongsTo(Formation::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function getMethodLabelAttribute(): string {
        return match($this->method) {
            'cash' => 'Espèces', 'card' => 'Carte', 'bank_transfer' => 'Virement', 'other' => 'Autre',
            default => ucfirst($this->method),
        };
    }
    public function getStatusLabelAttribute(): string {
        return match($this->status) {
            'pending' => 'En attente', 'paid' => 'Payé', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé',
            default => ucfirst($this->status),
        };
    }
    public function getStatusBadgeClassAttribute(): string {
        return match($this->status) {
            'paid'      => 'bg-success-subtle text-success border border-success-subtle',
            'pending'   => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'cancelled' => 'bg-secondary-subtle text-secondary border',
            'refunded'  => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            default     => 'bg-light text-dark border',
        };
    }
    public static function generateReference(Center $center): string {
        $code = strtoupper($center->code);
        $last = static::where('reference', 'LIKE', "PAY-{$code}-%")->orderBy('id','desc')->first();
        $next = $last ? ((int) substr($last->reference, -6)) + 1 : 1;
        return "PAY-{$code}-" . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}