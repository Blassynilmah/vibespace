<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileListItem extends Model
{
    protected $fillable = ['file_list_id', 'file_id'];

    public function file()
    {
        return $this->belongsTo(UserFile::class, 'file_id'); // ✅ correct model + foreign key
    }

    public function lists()
{
    return $this->hasManyThrough(FileList::class, FileListItem::class);
}
}
