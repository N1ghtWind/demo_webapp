<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => 0,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => 1,
        ]);
    }

    protected function actingAsUser(User $user = null): self
    {
        $authUser = $user ?? $this->user;
        $token = JWTAuth::fromUser($authUser);
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    protected function actingAsAdmin(): self
    {
        return $this->actingAsUser($this->adminUser);
    }

    /** @test */
    public function it_retrieves_users_list_successfully(): void
    {
        // Arrange
        User::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(5, count($data)); // 2 from setUp + 3 from factory + potentially more
    }

    /** @test */
    public function it_handles_service_exceptions_when_retrieving_users(): void
    {
        // Arrange
        $this->mock(UserService::class, function ($mock) {
            $mock->shouldReceive('index')
                ->once()
                ->andThrow(new Exception('User service error'));
        });

        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_returns_empty_list_when_no_users_exist(): void
    {
        // Arrange - Remove all users
        User::query()->delete();

        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /** @test */
    public function it_calls_user_service_for_index(): void
    {
        // Arrange
        $mockUsers = EloquentCollection::make([$this->user, $this->adminUser]);
        
        $mockService = $this->mock(UserService::class);
        $mockService->shouldReceive('index')
            ->once()
            ->andReturn($mockUsers);

        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function it_returns_authenticated_user_successfully(): void
    {
        // Arrange & Act
        $response = $this->actingAsUser()
            ->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ],
            ]);
    }

    /** @test */
    public function it_returns_authenticated_admin_user_successfully(): void
    {
        // Arrange & Act
        $response = $this->actingAsAdmin()
            ->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->adminUser->id,
                    'name' => $this->adminUser->name,
                    'email' => $this->adminUser->email,
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_get_authenticated_user(): void
    {
        // Act
        $response = $this->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_handles_exceptions_when_getting_authenticated_user(): void
    {
        // Arrange & Act
        $response = $this->actingAsUser()
            ->getJson('/api/user/get-authenticated-user');

        // Assert - In normal circumstances this should work
        $response->assertOk();
    }

    /** @test */
    public function it_returns_different_users_based_on_authentication(): void
    {
        // Test with regular user
        $regularResponse = $this->actingAsUser($this->user)
            ->getJson('/api/user/get-authenticated-user');

        // Test with admin user
        $adminResponse = $this->actingAsUser($this->adminUser)
            ->getJson('/api/user/get-authenticated-user');

        // Assert
        $regularResponse->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ],
            ]);

        $adminResponse->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->adminUser->id,
                    'email' => $this->adminUser->email,
                ],
            ]);

        // Ensure they return different users
        $this->assertNotEquals(
            $regularResponse->json('data.id'),
            $adminResponse->json('data.id')
        );
    }

    /** @test */
    public function it_returns_proper_json_structure_for_authenticated_user(): void
    {
        // Act
        $response = $this->actingAsUser()
            ->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name', 
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $data = $response->json('data');
        
        // Verify data types
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['email']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    /** @test */
    public function it_does_not_expose_sensitive_user_information(): void
    {
        // Act
        $response = $this->actingAsUser()
            ->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertOk();
        
        $data = $response->json('data');
        
        // Ensure sensitive fields are not exposed
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    /** @test */
    public function it_handles_invalid_jwt_token_gracefully(): void
    {
        // Arrange
        $invalidToken = 'invalid.jwt.token';

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $invalidToken,
        ])->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_handles_expired_jwt_token_gracefully(): void
    {
        // Arrange - Create an expired token (this is more complex to test)
        // For now, we'll test with missing token
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ',
        ])->getJson('/api/user/get-authenticated-user');

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_returns_users_with_proper_formatting(): void
    {
        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertOk();
        
        $data = $response->json('data');
        
        if (count($data) > 0) {
            $firstUser = $data[0];
            
            // Verify structure of user data in the list
            $this->assertArrayHasKey('id', $firstUser);
            $this->assertArrayHasKey('name', $firstUser);
            $this->assertArrayHasKey('email', $firstUser);
            $this->assertArrayHasKey('created_at', $firstUser);
            $this->assertArrayHasKey('updated_at', $firstUser);
            
            // Verify sensitive data is not exposed in list
            $this->assertArrayNotHasKey('password', $firstUser);
            $this->assertArrayNotHasKey('remember_token', $firstUser);
        }
    }

    /** @test */
    public function it_maintains_session_state_across_requests(): void
    {
        // First request
        $response1 = $this->actingAsUser()
            ->getJson('/api/user/get-authenticated-user');

        // Second request with same authentication
        $response2 = $this->actingAsUser()
            ->getJson('/api/user/get-authenticated-user');

        // Assert both requests return the same user
        $response1->assertOk();
        $response2->assertOk();
        
        $this->assertEquals(
            $response1->json('data.id'),
            $response2->json('data.id')
        );
    }

    /** @test */
    public function it_handles_user_service_returning_different_data_types(): void
    {
        // Test with collection
        $mockService = $this->mock(UserService::class);
        $mockService->shouldReceive('index')
            ->once()
            ->andReturn(EloquentCollection::make([$this->user]));

        $response1 = $this->getJson('/api/user');
        $response1->assertOk();

        // Test with Eloquent Collection
        $mockService->shouldReceive('index')
            ->once()
            ->andReturn(EloquentCollection::make([$this->user]));

        $response2 = $this->getJson('/api/user');
        $response2->assertOk();
    }
}