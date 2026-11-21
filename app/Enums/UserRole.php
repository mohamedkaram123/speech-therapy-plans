<?php

namespace App\Enums;

enum UserRole: string
{
    case Supervisor = 'supervisor';
    case Teacher = 'teacher';

    public function label(): string
    {
        return match ($this) {
            self::Supervisor => 'مشرف',
            self::Teacher => 'معلم',
        };
    }
}
