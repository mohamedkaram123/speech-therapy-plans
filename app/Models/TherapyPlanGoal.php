<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TherapyPlanGoal extends Model
{
    protected $fillable = [
        'therapy_plan_id',
        'bank_goal_id',
        'domain_name',
        'level',
        'subcategory',
        'printed_code',
        'goal_text',
        'training_elements',
        'generalization',
        'teacher_note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function therapyPlan(): BelongsTo
    {
        return $this->belongsTo(TherapyPlan::class);
    }

    public function bankGoal(): BelongsTo
    {
        return $this->belongsTo(BankGoal::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(EvaluationGoalResult::class);
    }
}
