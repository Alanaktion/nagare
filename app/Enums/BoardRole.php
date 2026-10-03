<?php

namespace App\Enums;

enum BoardRole: string
{
    case Admin = 'admin';
    case Member = 'member';
}
