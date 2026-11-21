<?php

namespace App\Models;

use App\Enums\GoalResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationGoalResult extends Model
{
    protected $fillable = ['evaluation_id', 'therapy_plan_goal_id', 'result', 'note'];

    protected function casts(): array
    {
        return ['result' => GoalResult::class];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function therapyPlanGoal(): BelongsTo
    {
        return $this->belongsTo(TherapyPlanGoal::class);
    }
}
