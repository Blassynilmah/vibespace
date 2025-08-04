<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['mood_board_id', 'type', 'content', 'caption'];

    public function board()
    {
        return $this->belongsTo(MoodBoard::class, 'mood_board_id');
    }
}
