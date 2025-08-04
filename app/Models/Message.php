<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'body',
        'is_read',
    ];

    public function file()
{
    return $this->belongsTo(UserFile::class);
}

public function attachments()
{
    return $this->hasMany(\App\Models\Attachment::class, 'message_id');
}
}

