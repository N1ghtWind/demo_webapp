<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\UserAuthenticationInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'is_admin' => 1,
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => 0,
        ]);
    }

    /** @test */
    public function it_logs_in_admin_user_successfully_with_valid_credentials(): void
    {
        // Arrange
        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertCreated()
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
    }

    /** @test */
    public function it_fails_to_login_admin_with_invalid_credentials(): void
    {
        // Arrange
        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'wrong_password',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_fails_to_login_with_non_existent_admin(): void
    {
        // Arrange
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_requires_email_field_for_admin_login(): void
    {
        // Arrange
        $credentials = [
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_password_field_for_admin_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'admin@example.com',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_valid_email_format_for_admin_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'invalid-admin-email',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_passes_admin_flag_to_repository_for_admin_login(): void
    {
        // Arrange
        $mockRepository = $this->mock(UserAuthenticationInterface::class);
        $mockRepository->shouldReceive('login')
            ->once()
            ->with(
                ['email' => 'admin@example.com', 'password' => 'admin123'],
                1 // Admin flag should be 1
            )
            ->andReturn([
                'user' => $this->adminUser,
                'token' => 'fake.jwt.token',
                'refresh_token' => 'fake.refresh.token',
            ]);

        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertCreated();
    }

    /** @test */
    public function it_handles_repository_exceptions_during_admin_login(): void
    {
        // Arrange
        $this->mock(UserAuthenticationInterface::class, function ($mock) {
            $mock->shouldReceive('login')
                ->once()
                ->with(
                    ['email' => 'admin@example.com', 'password' => 'admin123'],
                    1
                )
                ->andThrow(new NotFoundHttpException('Admin user not found'));
        });

        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_returns_created_status_code_on_successful_admin_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertStatus(201); // Specifically testing the 201 status code
    }

    /** @test */
    public function it_returns_proper_json_structure_on_successful_admin_login(): void
    {
        // Arrange
        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert

        $response->assertCreated()
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
        $this->assertEquals($this->adminUser->email, $responseData['user']['email']);
    }

    /**
     * Test data provider for invalid admin login attempts
     *
     * @return array<string, array<string, mixed>>
     */
    public static function invalidAdminLoginDataProvider(): array
    {
        return [
            'empty email' => ['', 'admin123', ['email']],
            'empty password' => ['admin@example.com', '', ['password']],
            'both empty' => ['', '', ['email', 'password']],
            'invalid email format' => ['not-an-email', 'admin123', ['email']],
            'null email' => [null, 'admin123', ['email']],
            'null password' => ['admin@example.com', null, ['password']],
            'too short password' => ['admin@example.com', '123', ['password']],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAdminLoginDataProvider
     * @param mixed $email
     * @param mixed $password
     * @param array<string> $expectedErrors
     */
    public function it_validates_admin_login_input_correctly(mixed $email, mixed $password, array $expectedErrors): void
    {
        // Arrange
        $credentials = array_filter([
            'email' => $email,
            'password' => $password,
        ], fn($value) => $value !== null);

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    /** @test */
    public function it_distinguishes_between_admin_and_regular_user_login(): void
    {
        // This test ensures that the admin login uses the correct isAdmin parameter
        // The actual business logic for checking admin status would be in the repository

        // Arrange
        $mockRepository = $this->mock(UserAuthenticationInterface::class);

        // Admin login should pass isAdmin = 1
        $mockRepository->shouldReceive('login')
            ->once()
            ->with(
                ['email' => 'admin@example.com', 'password' => 'admin123'],
                1 // This is the key difference - admin flag is 1
            )
            ->andReturn([
                'user' => $this->adminUser,
                'token' => 'admin.jwt.token',
                'refresh_token' => 'admin.refresh.token',
            ]);

        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ];

        // Act
        $response = $this->postJson('/api/admin/auth/login', $credentials);

        // Assert
        $response->assertCreated();
    }
}
