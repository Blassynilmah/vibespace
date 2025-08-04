<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoodBoardResource extends JsonResource
{
// app/Http/Resources/MoodBoardResource.php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'description' => $this->description,
        'post_count' => $this->posts_count,
        'latest_mood' => $this->latest_mood,
        'created_at' => $this->created_at,
        'user' => [
            'username' => $this->user->username,
        ],
    ];
}

}
