<?php

namespace App\Enums;

enum GoalResult: string
{
    case Mastered = 'mastered';
    case InTraining = 'in_training';
    case NotMastered = 'not_mastered';

    public function label(): string
    {
        return match ($this) {
            self::Mastered => 'أتقن',
            self::InTraining => 'في طور التدريب',
            self::NotMastered => 'غير متقن',
        };
    }
}
