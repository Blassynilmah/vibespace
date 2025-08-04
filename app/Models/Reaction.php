<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $fillable = ['user_id', 'mood_board_id', 'mood'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moodBoard()
    {
        return $this->belongsTo(MoodBoard::class);
    }
}
