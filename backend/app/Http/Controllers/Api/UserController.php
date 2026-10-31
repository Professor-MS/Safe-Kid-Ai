<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Retrieve the authenticated user profile along with linked relations.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found. Please complete profile registration.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Eager load links based on role
        if ($user->isParent()) {
            $user->load(['children' => function ($query) {
                $query->wherePivot('status', 'active');
            }]);
        } else {
            $user->load(['parents' => function ($query) {
                $query->wherePivot('status', 'active');
            }]);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Upsert FCM device token for push notifications into device_tokens table.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
        ]);

        $deviceToken = DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $validated['token'],
            ],
            [
                'platform' => $validated['platform'] ?? 'android',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'FCM device token registered successfully',
            'data' => $deviceToken,
        ]);
    }
}

