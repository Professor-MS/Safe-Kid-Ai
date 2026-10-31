<?php

namespace App\Services;

use App\Contracts\FirebaseTokenVerifierInterface;
use Kreait\Firebase\Contract\Auth;

class FirebaseTokenVerifier implements FirebaseTokenVerifierInterface
{
    public function __construct(protected Auth $auth)
    {
    }

    /**
     * Verifies the given Firebase ID token string using Kreait Firebase Auth.
     */
    public function verifyIdToken(string $idToken): array
    {
        $verifiedIdToken = $this->auth->verifyIdToken($idToken);
        return $verifiedIdToken->claims()->all();
    }
}

