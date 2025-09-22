<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteTeaser extends Model
{
    protected $table = 'favorite_teasers';

    protected $fillable = [
        'teaser_id',
        'user_id',
    ];

    public $timestamps = false;

    // Relations
    public function teaser()
    {
        return $this->belongsTo(Teaser::class, 'teaser_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}