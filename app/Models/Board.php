<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_KANBAN = 'kanban';
    public const TYPE_SCRUM = 'scrum';

    public function statuses()
    {
        return $this->hasMany(Status::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
