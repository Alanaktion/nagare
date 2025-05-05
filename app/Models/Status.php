<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use SoftDeletes;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['name', 'sort', 'is_closed'];

    protected $casts = [
        'is_closed' => 'boolean',
    ];
}
