<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mute extends Model
{
    protected $table = 'mutes';

    protected $fillable = [
        'muter_id',
        'muted_id',
        'muted_at',
        'mute_until',
    ];

    public $timestamps = false;

    // Relationships (optional)
    public function muter()
    {
        return $this->belongsTo(User::class, 'muter_id');
    }

    public function muted()
    {
        return $this->belongsTo(User::class, 'muted_id');
    }
}