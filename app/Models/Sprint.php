<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    public static function dateSlug(string $cycle, int $start): string
    {
        return match ($cycle) {
            'weekly' => date('Y\\WW', $start),
            'monthly' => date('Y-m', $start),
            'quarterly' => date('Y\\Q', $start).ceil(date('n', $start) / 3),
            default => date('Y-m-d', $start),
        };
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
