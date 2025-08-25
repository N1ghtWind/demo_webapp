<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Repositories\Interfaces\UserAuthenticationInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    /** @test */
    public function it_logs_in_user_successfully_with_valid_credentials(): void
    {
        // Arrange
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                    'refresh_token',
                ],
            ]);
    }

    /** @test */
    public function it_fails_to_login_with_invalid_credentials(): void
    {
        // Arrange
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_fails_to_login_with_non_existent_user(): void
    {
        // Arrange
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_requires_email_field_for_login(): void
    {
        // Arrange
        $credentials = [
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_password_field_for_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'test@example.com',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_valid_email_format_for_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_logs_out_authenticated_user_successfully(): void
    {
        // Arrange
        $token = JWTAuth::fromUser($this->user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Successfully logged out',
                ],
            ]);
    }

    /** @test */
    public function it_fails_to_logout_without_authentication(): void
    {
        // Act
        $response = $this->postJson('/api/auth/logout');

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_fails_to_logout_with_invalid_token(): void
    {
        // Arrange
        $invalidToken = 'invalid.token.here';

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $invalidToken,
        ])->postJson('/api/auth/logout');

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_refreshes_token_successfully_for_authenticated_user(): void
    {
        // Arrange - Create a proper refresh token
        $refreshToken = JWTAuth::claims(['is_refresh_token' => true])->fromUser($this->user);
        $this->actingAs($this->user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $refreshToken,
        ])->getJson('/api/auth/refresh-token');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                ],
            ]);
    }

    /** @test */
    public function it_fails_to_refresh_token_without_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/auth/refresh-token');

        // Assert
        $response->assertStatus(402); // Middleware returns 402 for missing/invalid tokens
    }

    /** @test */
    public function it_fails_to_refresh_token_with_invalid_token(): void
    {
        // Arrange - Use regular token (not refresh token) to test middleware validation
        $regularToken = JWTAuth::fromUser($this->user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $regularToken,
        ])->getJson('/api/auth/refresh-token');

        // Assert
        $response->assertStatus(402); // Middleware returns 402 for non-refresh tokens
    }

    /** @test */
    public function it_handles_repository_exceptions_during_login(): void
    {
        // Arrange
        $this->mock(UserAuthenticationInterface::class, function ($mock) {
            $mock->shouldReceive('login')
                ->once()
                ->andThrow(new NotFoundHttpException('User not found'));
        });

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_handles_jwt_exceptions_during_logout(): void
    {
        // Arrange
        $this->mock(UserAuthenticationInterface::class, function ($mock) {
            $mock->shouldReceive('logout')
                ->once()
                ->andThrow(new JWTException('Token could not be parsed'));
        });

        $token = JWTAuth::fromUser($this->user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(500);
    }

    /**
     * Test data provider for invalid login attempts
     *
     * @return array<string, array<string, mixed>>
     */
    public static function invalidLoginDataProvider(): array
    {
        return [
            'empty email' => ['', 'password123', ['email']],
            'empty password' => ['test@example.com', '', ['password']],
            'both empty' => ['', '', ['email', 'password']],
            'invalid email format' => ['not-an-email', 'password123', ['email']],
            'null email' => [null, 'password123', ['email']],
            'null password' => ['test@example.com', null, ['password']],
        ];
    }

    /**
     * @test
     * @dataProvider invalidLoginDataProvider
     * @param mixed $email
     * @param mixed $password
     * @param array<string> $expectedErrors
     */
    public function it_validates_login_input_correctly(mixed $email, mixed $password, array $expectedErrors): void
    {
        // Arrange
        $credentials = array_filter([
            'email' => $email,
            'password' => $password,
        ], fn($value) => $value !== null);

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    /** @test */
    public function it_returns_proper_json_structure_on_successful_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                    'refresh_token',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData['token']);
        $this->assertNotEmpty($responseData['refresh_token']);
        $this->assertEquals($this->user->email, $responseData['user']['email']);
    }
}
