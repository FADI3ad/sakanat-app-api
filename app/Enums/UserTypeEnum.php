<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case RESIDENT = 'resident';
    case PROVIDER = 'provider';
    case PROPERTY_OWNER = 'property_owner';
}
