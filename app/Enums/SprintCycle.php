<?php

namespace App\Enums;

enum SprintCycle: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Custom = 'custom';
}
