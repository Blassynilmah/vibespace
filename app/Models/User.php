<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function moodBoards()
{
    return $this->hasMany(MoodBoard::class);
}

public function series()
{
    return $this->hasMany(Series::class);
}

public function fanMixes()
{
    return $this->hasMany(FanMix::class);
}

public function comments()
{
    return $this->hasMany(Comment::class);
}

public function messages()
{
    return $this->hasMany(\App\Models\Message::class, 'sender_id');
}

public function profilePicture()
{
    return $this->hasOne(ProfilePicture::class);
}

// Who this user is following
public function following()
{
    return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
}

// Who is following this user
public function followers()
{
    return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
}

public function userFiles()
{
    return $this->hasMany(UserFile::class);
}

public function fileLists()
{
    return $this->hasMany(FileList::class);
}

public function files()
{
    return $this->hasMany(UserFile::class);
}

public function favoriteMoodboards()
{
    return $this->hasMany(UserFavoriteMoodboard::class);
}

public function savedMoodboards()
{
    return $this->hasMany(SavedMoodboard::class);
}
}
