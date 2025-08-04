<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    protected $fillable = [
    'user_id',
    'comment_id',
    'type',
];

public function comment()
{
    return $this->belongsTo(Comment::class);
}

public function user() {
    return $this->belongsTo(User::class);
}

public function likes()
{
    return $this->reactions()->where('type', 'like');
}

public function dislikes()
{
    return $this->reactions()->where('type', 'dislike');
}

public function userReaction()
{
    return $this->reactions()->where('user_id', auth()->id())->first();
}}
