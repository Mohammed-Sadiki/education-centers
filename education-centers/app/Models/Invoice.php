<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Invoice extends Model {
    use HasFactory;
    protected $fillable = [
        'center_id','student_id','enrollment_id','formation_id',
        'invoice_number','total_amount','currency','invoice_date','due_date','status','notes','created_by',
    ];
    protected function casts(): array {
        return ['invoice_date' => 'date', 'due_date' => 'date', 'total_amount' => 'decimal:2'];
    }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function formation(): BelongsTo { return $this->belongsTo(Formation::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function getTotalPaidAttribute(): float {
        return (float) $this->payments()->where('status','paid')->sum('amount');
    }
    public function getRemainingAmountAttribute(): float {
        return max(0, (float)$this->total_amount - $this->total_paid);
    }
    public function isPaid(): bool { return $this->status === 'paid'; }
    public function getStatusLabelAttribute(): string {
        return match($this->status) {
            'draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée',
            'cancelled' => 'Annulée', 'overdue' => 'En retard',
            default => ucfirst($this->status),
        };
    }
    public static function generateInvoiceNumber(Center $center): string {
        $code = strtoupper($center->code);
        $last = static::where('invoice_number', 'LIKE', "INV-{$code}-%")->orderBy('id','desc')->first();
        $next = $last ? ((int) substr($last->invoice_number, -6)) + 1 : 1;
        return "INV-{$code}-" . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}