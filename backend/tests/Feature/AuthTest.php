<?php

namespace Tests\Feature;

use App\Contracts\FirebaseTokenVerifierInterface;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to bind a controllable fake Firebase token verifier into the service container.
     */
    protected function bindFakeFirebaseVerifier(?array $tokenMap = null): void
    {
        $this->app->instance(FirebaseTokenVerifierInterface::class, new class($tokenMap) implements FirebaseTokenVerifierInterface {
            public function __construct(protected ?array $tokenMap)
            {
            }

            public function verifyIdToken(string $idToken): array
            {
                if ($idToken === 'invalid-token' || $idToken === 'expired-token') {
                    throw new \InvalidArgumentException('The token is invalid or expired.');
                }

                if ($this->tokenMap && isset($this->tokenMap[$idToken])) {
                    return $this->tokenMap[$idToken];
                }

                return [
                    'sub' => 'uid_' . $idToken,
                    'email' => $idToken . '@example.com',
                ];
            }
        });
    }

    public function test_request_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Authorization token required',
            ]);
    }

    public function test_request_with_invalid_token_returns_401(): void
    {
        $this->bindFakeFirebaseVerifier();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired Firebase token',
            ]);
    }

    public function test_register_profile_creates_user_with_token_claims_and_body_role(): void
    {
        $this->bindFakeFirebaseVerifier([
            'parent-token' => [
                'sub' => 'firebase_uid_parent_123',
                'email' => 'parent@example.com',
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer parent-token',
        ])->postJson('/api/auth/register-profile', [
            'name' => 'Alice Parent',
            'role' => 'parent',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User profile registered successfully',
                'data' => [
                    'firebase_uid' => 'firebase_uid_parent_123',
                    'email' => 'parent@example.com',
                    'name' => 'Alice Parent',
                    'role' => 'parent',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'firebase_uid' => 'firebase_uid_parent_123',
            'email' => 'parent@example.com',
            'role' => 'parent',
        ]);
    }

    public function test_register_profile_role_is_immutable_and_second_call_returns_409(): void
    {
        $this->bindFakeFirebaseVerifier([
            'user-token' => [
                'sub' => 'firebase_uid_child_456',
                'email' => 'child@example.com',
            ],
        ]);

        // First registration: creates child role
        $this->withHeaders([
            'Authorization' => 'Bearer user-token',
        ])->postJson('/api/auth/register-profile', [
            'name' => 'Bob Child',
            'role' => 'child',
        ])->assertStatus(201);

        // Second registration attempt: trying to change role to parent
        $secondResponse = $this->withHeaders([
            'Authorization' => 'Bearer user-token',
        ])->postJson('/api/auth/register-profile', [
            'name' => 'Bob Trying To Be Parent',
            'role' => 'parent',
        ]);

        $secondResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Profile already registered. Role is immutable.',
            ]);

        // Verify the database still has the original immutable child role
        $this->assertDatabaseHas('users', [
            'firebase_uid' => 'firebase_uid_child_456',
            'role' => 'child',
            'name' => 'Bob Child',
        ]);
    }

    public function test_user_can_retrieve_own_profile_via_get_me(): void
    {
        $this->bindFakeFirebaseVerifier([
            'valid-token' => [
                'sub' => 'uid_user_789',
                'email' => 'testuser@example.com',
            ],
        ]);

        User::create([
            'firebase_uid' => 'uid_user_789',
            'name' => 'Verified User',
            'email' => 'testuser@example.com',
            'role' => 'parent',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'firebase_uid' => 'uid_user_789',
                    'name' => 'Verified User',
                    'role' => 'parent',
                ],
            ]);
    }

    public function test_user_a_cannot_read_or_access_user_b_profile(): void
    {
        $this->bindFakeFirebaseVerifier([
            'token-user-a' => [
                'sub' => 'uid_user_a',
                'email' => 'usera@example.com',
            ],
            'token-user-b' => [
                'sub' => 'uid_user_b',
                'email' => 'userb@example.com',
            ],
        ]);

        $userA = User::create([
            'firebase_uid' => 'uid_user_a',
            'name' => 'User A',
            'email' => 'usera@example.com',
            'role' => 'parent',
        ]);

        $userB = User::create([
            'firebase_uid' => 'uid_user_b',
            'name' => 'User B',
            'email' => 'userb@example.com',
            'role' => 'child',
        ]);

        // Request with User A's token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token-user-a',
        ])->getJson('/api/me');

        $response->assertStatus(200);
        $this->assertEquals($userA->id, $response->json('data.id'));
        $this->assertEquals('User A', $response->json('data.name'));
        $this->assertNotEquals($userB->id, $response->json('data.id'));
        $this->assertNotEquals('User B', $response->json('data.name'));
    }

    public function test_user_can_upsert_fcm_token_in_device_tokens_table(): void
    {
        $this->bindFakeFirebaseVerifier([
            'device-token-user' => [
                'sub' => 'uid_fcm_user',
                'email' => 'fcm@example.com',
            ],
        ]);

        $user = User::create([
            'firebase_uid' => 'uid_fcm_user',
            'name' => 'FCM User',
            'email' => 'fcm@example.com',
            'role' => 'parent',
        ]);

        // First registration of device token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer device-token-user',
        ])->putJson('/api/me/fcm-token', [
            'token' => 'fcm_sample_token_xyz_123',
            'platform' => 'android',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FCM device token registered successfully',
            ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm_sample_token_xyz_123',
            'platform' => 'android',
        ]);

        // Upserting the same token does not duplicate rows
        $this->withHeaders([
            'Authorization' => 'Bearer device-token-user',
        ])->putJson('/api/me/fcm-token', [
            'token' => 'fcm_sample_token_xyz_123',
            'platform' => 'android',
        ])->assertStatus(200);

        $this->assertEquals(1, DeviceToken::where('user_id', $user->id)->count());
    }
}

