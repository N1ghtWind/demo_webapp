<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Services\UserRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Exception;

class UserRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
    }

    /** @test */
    public function it_registers_user_successfully_with_valid_data(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Registration is success! Please check your mail to activate your user.',
                ],
            ]);
    }

    /** @test */
    public function it_requires_name_field_for_registration(): void
    {
        // Arrange
        $userData = [
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_requires_email_field_for_registration(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_password_field_for_registration(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_password_confirmation_for_registration(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_valid_email_format_for_registration(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_password_confirmation_to_match_password(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'DifferentPassword123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_unique_email_for_registration(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);
        
        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_minimum_password_length_for_registration(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_handles_registration_service_exceptions(): void
    {
        // Arrange - Use valid password that passes validation
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Mock service to throw exception after validation passes
        $this->mock(UserRegistrationService::class, function ($mock) use ($userData) {
            $mock->shouldReceive('registration')
                ->once()
                ->with($userData)
                ->andThrow(new Exception('Registration service error'));
        });

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertStatus(500);
    }

    /** @test */
    public function it_activates_user_successfully_with_valid_token(): void
    {
        // Arrange - Create a user with a real activation token
        $user = User::factory()->create([
            'email_verified_at' => null,
            'activation_token' => 'valid_activation_token_123'
        ]);

        // Act
        $response = $this->postJson('/api/activation', [
            'token' => $user->activation_token,
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'User activated successfully!',
                ],
            ]);
    }

    /** @test */
    public function it_requires_token_field_for_activation(): void
    {
        // Act
        $response = $this->postJson('/api/activation', []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    /** @test */
    public function it_handles_activation_service_exceptions(): void
    {
        // Act - Use a token that doesn't exist in database
        $response = $this->postJson('/api/activation', [
            'token' => 'invalid_token_that_does_not_exist',
        ]);

        // Assert - Service throws NotFoundHttpException (404) for invalid tokens
        $response->assertStatus(404);
    }

    /** @test */
    public function it_calls_registration_service_with_correct_data(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $mockService = $this->mock(UserRegistrationService::class);
        $mockService->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andReturn(true);

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function it_calls_activation_service_with_correct_token(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => null,
            'activation_token' => 'test_activation_token_123'
        ]);

        $mockService = $this->mock(UserRegistrationService::class);
        $mockService->shouldReceive('activation')
            ->once()
            ->with($user->activation_token)
            ->andReturn(true);

        // Act
        $response = $this->postJson('/api/activation', [
            'token' => $user->activation_token,
        ]);

        // Assert
        $response->assertOk();
    }

    /**
     * Test data provider for invalid registration data
     *
     * @return array<string, array<string, mixed>>
     */
    public static function invalidRegistrationDataProvider(): array
    {
        return [
            'empty name' => [
                'data' => ['name' => '', 'email' => 'test@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123'],
                'errors' => ['name'],
            ],
            'empty email' => [
                'data' => ['name' => 'John', 'email' => '', 'password' => 'Password123', 'password_confirmation' => 'Password123'],
                'errors' => ['email'],
            ],
            'empty password' => [
                'data' => ['name' => 'John', 'email' => 'test@example.com', 'password' => '', 'password_confirmation' => 'Password123'],
                'errors' => ['password'],
            ],
            'mismatched passwords' => [
                'data' => ['name' => 'John', 'email' => 'test@example.com', 'password' => 'Password123', 'password_confirmation' => 'different'],
                'errors' => ['password'],
            ],
            'invalid email format' => [
                'data' => ['name' => 'John', 'email' => 'invalid-email', 'password' => 'Password123', 'password_confirmation' => 'Password123'],
                'errors' => ['email'],
            ],
            'short password' => [
                'data' => ['name' => 'John', 'email' => 'test@example.com', 'password' => '123', 'password_confirmation' => '123'],
                'errors' => ['password'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidRegistrationDataProvider
     * @param array<string, mixed> $data
     * @param array<string> $expectedErrors
     */
    public function it_validates_registration_input_correctly(array $data, array $expectedErrors): void
    {
        // Act
        $response = $this->postJson('/api/registration', $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    /** @test */
    public function it_returns_proper_success_response_structure_for_registration(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Act
        $response = $this->postJson('/api/registration', $userData);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Registration is success! Please check your mail to activate your user.',
                ],
            ]);
    }

    /** @test */
    public function it_returns_proper_success_response_structure_for_activation(): void
    {
        // Arrange - Create a user with a real activation token
        $user = User::factory()->create([
            'email_verified_at' => null,
            'activation_token' => 'valid_token_123_test'
        ]);

        // Act
        $response = $this->postJson('/api/activation', [
            'token' => $user->activation_token,
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'User activated successfully!',
                ],
            ]);
    }
}