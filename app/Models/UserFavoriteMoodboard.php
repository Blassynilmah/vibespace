<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFavoriteMoodboard extends Model
{
    protected $table = 'user_favorite_moodboards';

    protected $fillable = [
        'user_id',
        'moodboard_id',
    ];

    public $timestamps = true;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moodboard()
    {
        return $this->belongsTo(MoodBoard::class);
    }
}
