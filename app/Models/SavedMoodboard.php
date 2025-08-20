<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavedMoodboard extends Model
{
    use HasFactory;

    protected $table = 'saved_moodboards';

    protected $fillable = [
        'user_id',
        'mood_board_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moodBoard()
{
    return $this->belongsTo(MoodBoard::class, 'mood_board_id', 'id');
}

}