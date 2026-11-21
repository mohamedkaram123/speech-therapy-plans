<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $fillable = [
        'therapy_plan_id',
        'status',
        'evaluation_completed_at_hijri',
        'performance_summary',
        'strengths',
        'skills_needing_training',
        'observed_progress',
        'final_notes',
        'recommendations',
        'submitted_at',
        'approved_at',
        'approved_by',
        'locked_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'status' => EvaluationStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_snapshot' => 'array',
        ];
    }

    public function therapyPlan(): BelongsTo
    {
        return $this->belongsTo(TherapyPlan::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function goalResults(): HasMany
    {
        return $this->hasMany(EvaluationGoalResult::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EvaluationReview::class)->orderBy('created_at');
    }

    public function isApproved(): bool
    {
        return $this->status === EvaluationStatus::Approved;
    }
}
