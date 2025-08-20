<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoodBoard extends Model
{
    use HasFactory;

   protected $fillable = [
    'user_id',
    'title',
    'description',
    'latest_mood',
    'image',
    'video',
];


    // 👇 Add this so it's included in API output
    protected $appends = ['reaction_counts'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function reactions()
    {
        return $this->hasMany(\App\Models\Reaction::class);
    }

    public function getReactionCountsAttribute()
    {
        return $this->reactions()
            ->selectRaw('mood, count(*) as count')
            ->groupBy('mood')
            ->pluck('count', 'mood');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
    {
        return $this->hasMany(UserFavoriteMoodboard::class, 'moodboard_id');
    }

    public function saves()
{
    return $this->hasMany(SavedMoodboard::class, 'mood_board_id', 'id');
}

}
