<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserRegistrationService;
use App\Repositories\Interfaces\UserRegistrationInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserRegistrationService $userRegistrationService;
    protected UserRegistrationInterface $mockRepository;
    protected OrderRepositoryInterface $mockOrderRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(UserRegistrationInterface::class);
        $this->mockOrderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->userRegistrationService = new UserRegistrationService($this->mockRepository, $this->mockOrderRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_registers_user_successfully_with_valid_data(): void
    {
        // Arrange
        Mail::fake();
        
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $mockUser = User::factory()->make([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->mockRepository
            ->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andReturn($mockUser);

        $this->mockOrderRepository
            ->shouldReceive('assignUserToOrdersByEmail')
            ->once()
            ->with($mockUser->id, $mockUser->email);

        // Act
        $result = $this->userRegistrationService->registration($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    /** @test */
    public function it_throws_exception_when_registration_fails(): void
    {
        // Arrange
        Mail::fake();
        
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->mockRepository
            ->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andThrow(new Exception('Registration failed'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Registration failed');
        
        $this->userRegistrationService->registration($userData);
    }

    /** @test */
    public function it_activates_user_successfully_with_valid_token(): void
    {
        // Arrange
        $activationToken = 'valid_token_123';

        $this->mockRepository
            ->shouldReceive('activation')
            ->once()
            ->with($activationToken)
            ->andReturn(true);

        // Act
        $result = $this->userRegistrationService->activation($activationToken);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_activation_fails(): void
    {
        // Arrange
        $activationToken = 'invalid_token_123';

        $this->mockRepository
            ->shouldReceive('activation')
            ->once()
            ->with($activationToken)
            ->andThrow(new Exception('Invalid activation token'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid activation token');
        
        $this->userRegistrationService->activation($activationToken);
    }

    /** @test */
    public function it_passes_correct_data_to_repository_for_registration(): void
    {
        // Arrange
        Mail::fake();
        
        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'securepassword',
            'password_confirmation' => 'securepassword',
        ];

        $mockUser = User::factory()->make([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $this->mockRepository
            ->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andReturn($mockUser);

        $this->mockOrderRepository
            ->shouldReceive('assignUserToOrdersByEmail')
            ->once()
            ->with($mockUser->id, $mockUser->email);

        // Act
        $result = $this->userRegistrationService->registration($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Jane Smith', $result->name);
        $this->assertEquals('jane@example.com', $result->email);
        // The assertion is implicit in the shouldReceive()->with() expectation
    }

    /** @test */
    public function it_passes_correct_token_to_repository_for_activation(): void
    {
        // Arrange
        $activationToken = 'specific_activation_token_456';

        $this->mockRepository
            ->shouldReceive('activation')
            ->once()
            ->with($activationToken)
            ->andReturn(true);

        // Act
        $result = $this->userRegistrationService->activation($activationToken);

        // Assert
        $this->assertTrue($result);
        // The assertion is implicit in the shouldReceive()->with() expectation
    }

    /** @test */
    public function it_handles_repository_exception_for_registration(): void
    {
        // Arrange
        Mail::fake();
        
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $this->mockRepository
            ->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andThrow(new Exception('Registration failed'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Registration failed');
        
        $this->userRegistrationService->registration($userData);
    }

    /** @test */
    public function it_handles_repository_returning_false_for_activation(): void
    {
        // Arrange
        $activationToken = 'some_token';

        $this->mockRepository
            ->shouldReceive('activation')
            ->once()
            ->with($activationToken)
            ->andReturn(false);

        // Act & Assert
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid token');
        
        $this->userRegistrationService->activation($activationToken);
    }

    /** @test */
    public function it_handles_empty_user_data_for_registration(): void
    {
        // Arrange
        Mail::fake();
        
        $userData = [];

        $this->mockRepository
            ->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andThrow(new Exception('Invalid user data'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid user data');
        
        $this->userRegistrationService->registration($userData);
    }

    /** @test */
    public function it_handles_empty_token_for_activation(): void
    {
        // Arrange
        $activationToken = '';

        $this->mockRepository
            ->shouldReceive('activation')
            ->once()
            ->with($activationToken)
            ->andThrow(new Exception('Token cannot be empty'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token cannot be empty');
        
        $this->userRegistrationService->activation($activationToken);
    }

    /**
     * Test data provider for various user data scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function userDataProvider(): array
    {
        return [
            'complete user data' => [
                'data' => [
                    'name' => 'Complete User',
                    'email' => 'complete@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expected_result' => true,
            ],
            'minimal user data' => [
                'data' => [
                    'name' => 'Min User',
                    'email' => 'min@example.com',
                    'password' => 'pass',
                ],
                'expected_result' => true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider userDataProvider
     * @param array<string, mixed> $userData
     * @param bool $expectedResult
     */
    public function it_handles_various_user_data_scenarios(array $userData, bool $expectedResult): void
    {
        // Arrange
        Mail::fake();
        
        $mockUser = User::factory()->make([
            'id' => 3,
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        $this->mockRepository
            ->shouldReceive('registration')
            ->once()
            ->with($userData)
            ->andReturn($mockUser);

        $this->mockOrderRepository
            ->shouldReceive('assignUserToOrdersByEmail')
            ->once()
            ->with($mockUser->id, $mockUser->email);

        // Act
        $result = $this->userRegistrationService->registration($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($userData['name'], $result->name);
        $this->assertEquals($userData['email'], $result->email);
    }

    /**
     * Test data provider for various activation token scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function activationTokenProvider(): array
    {
        return [
            'uuid token' => [
                'token' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'expected_result' => true,
            ],
            'hash token' => [
                'token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                'expected_result' => true,
            ],
            'short token' => [
                'token' => 'abc123',
                'expected_result' => true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider activationTokenProvider
     * @param string $token
     * @param bool $expectedResult
     */
    public function it_handles_various_activation_token_scenarios(string $token, bool $expectedResult): void
    {
        // Arrange
        $this->mockRepository
            ->shouldReceive('activation')
            ->once()
            ->with($token)
            ->andReturn($expectedResult);

        // Act
        $result = $this->userRegistrationService->activation($token);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    /** @test */
    public function it_maintains_method_interface_contract(): void
    {
        // This test ensures that the service maintains the expected method signatures
        
        // Arrange
        Mail::fake();
        
        $userData = ['name' => 'Test', 'email' => 'test@example.com'];
        $token = 'test_token';

        $mockUser = User::factory()->make([
            'id' => 4,
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $this->mockRepository->shouldReceive('registration')->once()->andReturn($mockUser);
        $this->mockOrderRepository->shouldReceive('assignUserToOrdersByEmail')->once();
        $this->mockRepository->shouldReceive('activation')->once()->andReturn(true);

        // Act & Assert - Methods should exist and be callable
        $this->assertTrue(method_exists($this->userRegistrationService, 'registration'));
        $this->assertTrue(method_exists($this->userRegistrationService, 'activation'));
        
        // Verify methods can be called
        $registrationResult = $this->userRegistrationService->registration($userData);
        $activationResult = $this->userRegistrationService->activation($token);
        
        $this->assertInstanceOf(User::class, $registrationResult);
        $this->assertTrue($activationResult);
    }
}