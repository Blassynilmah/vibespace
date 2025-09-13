<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeaserSave extends Model
{
    protected $fillable = ['teaser_id', 'user_id'];
    public $timestamps = false;
}