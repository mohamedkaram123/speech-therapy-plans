<?php

namespace App\Models;

use App\Enums\PlanStatus;
use App\Enums\Semester;
use App\Enums\StudentPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TherapyPlan extends Model
{
    protected $fillable = [
        'student_id',
        'teacher_id',
        'student_name',
        'teacher_name',
        'birth_date_hijri',
        'period',
        'diagnosis',
        'academic_year',
        'semester',
        'start_date_hijri',
        'end_date_hijri',
        'sessions_count',
        'sessions_per_week',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'locked_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period' => StudentPeriod::class,
            'semester' => Semester::class,
            'status' => PlanStatus::class,
            'sessions_count' => 'integer',
            'sessions_per_week' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_snapshot' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(TherapyPlanGoal::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TherapyPlanReview::class)->orderBy('created_at');
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function isApproved(): bool
    {
        return $this->status === PlanStatus::Approved;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [PlanStatus::Draft, PlanStatus::Returned], true);
    }
}
