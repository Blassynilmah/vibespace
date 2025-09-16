<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SeenContent;

class SeenContentController extends Controller
{
    /**
     * Store or update a seen content record for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'content_type' => 'required|in:board,teaser',
            'content_id' => 'required|integer',
        ]);

        SeenContent::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'content_type' => $request->content_type,
                'content_id' => $request->content_id,
            ],
            [
                'seen_at' => now(),
            ]
        );

        return response()->json(['status' => 'ok']);
    }
}