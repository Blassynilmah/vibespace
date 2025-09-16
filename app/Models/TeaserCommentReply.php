<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeaserCommentReply extends Model
{
    protected $table = 'teaser_comment_replies';

    protected $fillable = [
        'comment_id',
        'user_id',
        'body',
    ];

    public function comment()
    {
        return $this->belongsTo(TeaserComment::class, 'comment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}