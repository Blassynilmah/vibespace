<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeaserComment extends Model
{
    protected $fillable = ['teaser_id', 'user_id', 'body'];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
public function reactions()
{
    return $this->hasMany(TeaserCommentReaction::class, 'comment_id');
}

public function replies()
{
    return $this->hasMany(TeaserCommentReply::class, 'comment_id');
}
}