<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table = 'blocks';
    protected $fillable = ['blocker_id', 'blocked_id', 'block_type', 'blocked_at'];
    public $timestamps = false;
}