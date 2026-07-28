<?php

namespace App\Enums;

enum UtilityTypeEnum: string
{
    case ELECTRICITY = 'electricity';
    case WATER       = 'water';
    case GAS         = 'gas';
    case OTHER       = 'other';
}
