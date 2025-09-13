<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeaserReaction extends Model
{
    protected $fillable = ['teaser_id', 'user_id', 'reaction'];
    public $timestamps = false;
}