<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register or link local profile following Firebase signup.
     * Takes firebase_uid and email solely from verified Firebase token claims.
     * Accepts only name and role from request body.
     * Role is immutable once created.
     */
    public function registerProfile(Request $request): JsonResponse
    {
        $firebaseUid = $request->attributes->get('firebase_uid');
        $firebaseEmail = $request->attributes->get('firebase_email');

        if (! $firebaseUid) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated: missing verified Firebase identity',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:parent,child'],
        ]);

        $existingUser = User::where('firebase_uid', $firebaseUid)->first();

        if ($existingUser) {
            // Role is set once and is strictly immutable.
            return response()->json([
                'success' => false,
                'message' => 'Profile already registered. Role is immutable.',
                'data' => $existingUser,
            ], Response::HTTP_CONFLICT);
        }

        $user = User::create([
            'firebase_uid' => $firebaseUid,
            'email' => $firebaseEmail ?? '',
            'name' => $validated['name'],
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User profile registered successfully',
            'data' => $user,
        ], Response::HTTP_CREATED);
    }
}

