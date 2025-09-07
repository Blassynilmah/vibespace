<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teaser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teaser_id',
        'hashtags',
        'video',
        'expires_after',
        'expires_on',
        'created_at',
        'updated_at',
        'description'
    ];

    protected $dates = [
        'expires_on',
        'created_at',
        'updated_at'
    ];

public function user()
{
    return $this->belongsTo(User::class);
}
}