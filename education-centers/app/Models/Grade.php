<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Grade extends Model {
    use HasFactory;
    protected $fillable = [
        'center_id','evaluation_id','student_id','enrollment_id',
        'grade','comment','graded_at','is_absent',
    ];
    protected function casts(): array {
        return ['graded_at' => 'date', 'grade' => 'decimal:2', 'is_absent' => 'boolean'];
    }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function getFormattedGradeAttribute(): string {
        if ($this->is_absent) return 'Absent';
        if ($this->grade === null) return '—';
        $max = $this->evaluation?->max_grade ?? 20;
        return number_format((float)$this->grade, 2, ',', ' ') . '/' . number_format((float)$max, 2, ',', ' ');
    }
}