<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'mood_board_id',
        'body',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moodBoard()
    {
        return $this->belongsTo(MoodBoard::class);
    }
    public function replies()
{
    return $this->hasMany(Reply::class);
}

public function commentReactions()
{
    return $this->hasMany(\App\Models\CommentReaction::class, 'comment_id');
}
}
