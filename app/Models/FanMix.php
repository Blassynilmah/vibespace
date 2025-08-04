<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FanMix extends Model
{
    protected $fillable = ['user_id', 'title', 'post_ids'];

    protected $casts = [
        'post_ids' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return Post::whereIn('id', $this->post_ids)->get();
    }
}

