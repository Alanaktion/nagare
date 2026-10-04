<?php

namespace App\Enums;

/**
 * The colours a label can have. The client maps each to a set of classes.
 */
enum LabelColor: string
{
    case Gray = 'gray';
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Green = 'green';
    case Teal = 'teal';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Purple = 'purple';
    case Pink = 'pink';
}
