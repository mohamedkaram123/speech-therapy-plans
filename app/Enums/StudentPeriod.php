<?php

namespace App\Enums;

enum StudentPeriod: string
{
    case Morning = 'morning';
    case Evening = 'evening';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'صباحي',
            self::Evening => 'مسائي',
        };
    }
}
