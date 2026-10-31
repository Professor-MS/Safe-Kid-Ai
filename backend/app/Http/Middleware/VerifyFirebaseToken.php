<?php

namespace App\Http\Middleware;

use App\Contracts\FirebaseTokenVerifierInterface;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyFirebaseToken
{
    public function __construct(
        protected FirebaseTokenVerifierInterface $verifier
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($header, 7);

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $claims = $this->verifier->verifyIdToken($token);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired Firebase token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $firebaseUid = $claims['sub'] ?? null;
        $email = $claims['email'] ?? null;

        if (! $firebaseUid) {
            return response()->json([
                'success' => false,
                'message' => 'Token missing subject claim',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Store verified claims in request attributes for controllers
        $request->attributes->set('firebase_uid', $firebaseUid);
        $request->attributes->set('firebase_email', $email);

        // Find user by firebase_uid in local database
        $user = User::where('firebase_uid', $firebaseUid)->first();

        if ($user) {
            // Bind user to request so $request->user() works across Laravel
            $request->setUserResolver(fn () => $user);
        }

        return $next($request);
    }
}

