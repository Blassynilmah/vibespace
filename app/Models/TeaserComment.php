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
}