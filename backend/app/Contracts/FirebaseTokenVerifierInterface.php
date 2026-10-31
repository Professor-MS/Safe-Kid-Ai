<?php

namespace App\Contracts;

interface FirebaseTokenVerifierInterface
{
    /**
     * Verifies the given Firebase ID token string.
     *
     * @param string $idToken
     * @return array Verified claims containing at least 'sub' (uid) and 'email'.
     * @throws \Throwable If the token is invalid or expired.
     */
    public function verifyIdToken(string $idToken): array;
}

