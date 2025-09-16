<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeenContent extends Model
{
    protected $table = 'seen_content';

    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
        'seen_at',
    ];

    public $timestamps = false;

    // Relationships (optional)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}