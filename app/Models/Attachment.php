<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
    'file_name',
    'file_path',
    'mime_type',
    'size',
    'sender_id',
    'receiver_id',
    'message_id',
];

protected $casts = [
    'size' => 'integer',
];

public function message()
{
    return $this->belongsTo(Message::class);
}

public function sender()
{
    return $this->belongsTo(User::class, 'sender_id');
}

public function receiver()
{
    return $this->belongsTo(User::class, 'receiver_id');
}

}
