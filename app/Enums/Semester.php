<?php

namespace App\Enums;

enum Semester: string
{
    case First = 'first';
    case Second = 'second';

    public function label(): string
    {
        return match ($this) {
            self::First => 'الفصل الدراسي الأول',
            self::Second => 'الفصل الدراسي الثاني',
        };
    }
}
