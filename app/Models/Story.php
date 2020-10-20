<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const SPRINT_FORMAT_WEEKLY = 'Y\\WW';
    public const SPRINT_FORMAT_MONTHLY = 'Y-M';

    public static function getSprint(string $cycle = 'weekly', ?int $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return date(
            $cycle == 'monthly' ? self::SPRINT_FORMAT_MONTHLY : self::SPRINT_FORMAT_WEEKLY,
            $timestamp
        );
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }
}
