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
        'description',
        'teaser_mood'
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

public function reactions()
{
    return $this->hasMany(\App\Models\TeaserReaction::class, 'teaser_id');
}

public function comments()
{
    return $this->hasMany(\App\Models\TeaserComment::class, 'teaser_id');
}

public function saves()
{
    return $this->hasMany(\App\Models\TeaserSave::class, 'teaser_id');
}
}