<?php

namespace App\Models;

use Database\Factories\GoalDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalDomain extends Model
{
    /** @use HasFactory<GoalDomainFactory> */
    use HasFactory;

    protected $fillable = ['name', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function bankGoals(): HasMany
    {
        return $this->hasMany(BankGoal::class);
    }
}
