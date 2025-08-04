<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFile extends Model
{
protected $fillable = [
    'filename',
    'path',
    'content_type',
    'user_id',
];

protected $table = 'user_files';

public function lists()
{
    return $this->belongsToMany(FileList::class, 'file_list_items');
}
}
