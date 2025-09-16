<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeaserCommentReaction extends Model
{
    protected $table = 'teaser_comment_reactions';

    protected $fillable = [
        'comment_id',
        'user_id',
        'reaction_type',
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