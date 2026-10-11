<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Evaluation extends Model {
    use HasFactory;
    protected $fillable = [
        'center_id','class_id','formation_id','teacher_id','schedule_id',
        'title','type','evaluation_date','max_grade','coefficient','description','status',
    ];
    protected function casts(): array {
        return [
            'evaluation_date' => 'date',
            'max_grade'       => 'decimal:2',
            'coefficient'     => 'decimal:2',
        ];
    }
    public function center(): BelongsTo { return $this->belongsTo(Center::class); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class, 'class_id'); }
    public function formation(): BelongsTo { return $this->belongsTo(Formation::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function grades(): HasMany { return $this->hasMany(Grade::class); }
    public function getTypeLabelAttribute(): string {
        return match($this->type) {
            'exam' => 'Examen', 'control' => 'Contrôle', 'homework' => 'Devoir',
            'oral' => 'Oral', 'project' => 'Projet', 'participation' => 'Participation',
            default => ucfirst($this->type),
        };
    }
    public function getAverageGradeAttribute(): ?float {
        $grades = $this->grades()->whereNotNull('grade')->get();
        if ($grades->isEmpty()) return null;
        return round($grades->avg('grade'), 2);
    }
}