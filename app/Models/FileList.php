<?php

namespace App\Models;
use App\Models\UserFile;

use Illuminate\Database\Eloquent\Model;

class FileList extends Model
{
    protected $fillable = ['name', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(FileListItem::class);
    }

    // Shortcut to get actual files
public function files()
{
    return $this->belongsToMany(UserFile::class, 'file_list_items', 'file_list_id', 'file_id');
}


}