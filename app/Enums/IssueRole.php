<?php

namespace App\Enums;

enum IssueRole: string
{
    case Epic = 'epic';
    case Story = 'story';
    case Task = 'task';
}
