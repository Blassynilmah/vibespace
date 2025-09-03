<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use App\Models\MoodBoard;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ReactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Basic request context
        Log::info('Reactions: store called', [
            'ip'      => $request->ip(),
            'user_id' => optional($request->user())->id,
            'payload' => $request->only(['mood_board_id', 'mood']),
        ]);

        // Require authenticated user
        if (!$request->user()) {
            Log::warning('Reactions: unauthenticated request');
            return response()->json([
                'success' => false,
                'error'   => 'Unauthenticated.',
            ], 401);
        }

        // Validate with explicit logging
        $validator = Validator::make($request->all(), [
            'mood_board_id' => 'required|exists:mood_boards,id',
            'mood'          => 'required|in:fire,love,funny,mind-blown,cool,crying,clap,flirty',
        ]);

        if ($validator->fails()) {
            Log::warning('Reactions: validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data   = $validator->validated();
        $userId = $request->user()->id;

        try {
            // Fetch previous mood (if any)
            $previous = Reaction::where('user_id', $userId)
                ->where('mood_board_id', $data['mood_board_id'])
                ->value('mood');

            Log::debug('Reactions: previous lookup', [
                'user_id'       => $userId,
                'mood_board_id' => $data['mood_board_id'],
                'previous'      => $previous,
            ]);

            // Short-circuit if same mood
            if ($previous === $data['mood']) {
                Log::info('Reactions: no change (same mood)', [
                    'user_id'       => $userId,
                    'mood_board_id' => $data['mood_board_id'],
                    'mood'          => $data['mood'],
                ]);

                return response()->json([
                    'success'  => true,
                    'mood'     => $previous,
                    'previous' => $previous,
                    'changed'  => false,
                ], 200);
            }

            // Upsert new mood
            $reaction = Reaction::updateOrCreate(
                [
                    'user_id'       => $userId,
                    'mood_board_id' => $data['mood_board_id'],
                ],
                [
                    'mood' => $data['mood'],
                ]
            );

            // Send notification to the mood board owner (if not reacting to own board)
            $moodBoard = MoodBoard::find($data['mood_board_id']);
            if ($moodBoard && $moodBoard->user_id !== $userId) {
                Notification::create([
                    'user_id' => $moodBoard->user_id,
                    'type'    => 'reaction',
                    'data'    => [
                        'message' => $request->user()->name . " reacted to your mood board with '{$data['mood']}'",
                        'mood_board_id' => $moodBoard->id,
                        'reactor_id' => $userId,
                    ],
                    'read_at' => null,
                ]);
            }

            Log::info('Reactions: upserted', [
                'user_id'       => $userId,
                'mood_board_id' => $data['mood_board_id'],
                'previous'      => $previous,
                'new'           => $data['mood'],
                'created'       => $reaction->wasRecentlyCreated,
                'reaction_id'   => $reaction->id,
            ]);

            return response()->json([
                'success'  => true,
                'mood'     => $data['mood'],
                'previous' => $previous,
                'changed'  => true,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Reactions: exception during store', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                // Optional: comment in for deep debugging, but avoid in prod
                // 'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Something went wrong while saving your reaction.',
            ], 500);
        }
    }
}