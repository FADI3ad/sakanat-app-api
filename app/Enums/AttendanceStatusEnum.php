<?php

namespace App\Enums;

enum AttendanceStatusEnum: string
{
    case PRESENT = 'present';
    case ABSENT  = 'absent';
    case LATE    = 'late';
}
