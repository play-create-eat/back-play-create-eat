<?php

namespace App\Enums;

enum FamilyPassStatusEnum: string
{
    case Active = 'active';
    case Feature = 'feature';
    case Expired = 'expired';
}
