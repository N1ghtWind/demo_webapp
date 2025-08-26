<?php

namespace Tests\Feature\Order;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Services\OrderItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderItemControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;
    protected Product $product;
    protected Category $category;
    protected OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->category = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Laptop',
            'price' => 999.99,
            'category_id' => $this->category->id,
        ]);

        $this->order = Order::factory()->create([
            'name' => 'John Doe',
            'email_address' => 'john@example.com',
            'phone_number' => '123-456-7890',
        ]);

        $this->orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 999.99,
        ]);
    }

    protected function actingAsUser(): self
    {
        $token = JWTAuth::fromUser($this->user);
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    /** @test */
    public function it_retrieves_order_items_list_successfully(): void
    {
        // Arrange
        OrderItem::factory()->count(2)->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
        ]);

        // Act
        $response = $this->getJson("/api/order/{$this->order->id}/order-item");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'order_id',
                        'product_id',
                        'quantity',
                        'price',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify we have at least 3 order items (1 from setUp + 2 from factory)
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /** @test */
    public function it_retrieves_single_order_item_successfully(): void
    {
        // Act
        $response = $this->getJson("/api/order/{$this->order->id}/order-item/{$this->orderItem->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_id',
                    'product_id',
                    'quantity',
                    'price',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->orderItem->id,
                    'order_id' => $this->order->id,
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 999.99,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_when_order_item_not_found(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->getJson("/api/order/{$this->order->id}/order-item/{$nonExistentId}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_returns_404_when_order_not_found_for_order_items(): void
    {
        // Arrange
        $nonExistentOrderId = 99999;

        // Act
        $response = $this->getJson("/api/order/{$nonExistentOrderId}/order-item");

        // Assert
        $response->assertNotFound(); // This should be handled by Laravel's route model binding
    }

    /** @test */
    public function it_creates_order_item_successfully_with_valid_data(): void
    {
        // Arrange
        $orderItemData = [
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 1299.99,
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_id',
                    'product_id',
                    'quantity',
                    'price',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_id' => $this->order->id,
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'price' => 1299.99,
                ],
            ]);

        // Verify order item was created in database
        $this->assertDatabaseHas('order_items', [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 1299.99,
        ]);
    }

    /** @test */
    public function it_requires_product_id_when_creating_order_item(): void
    {
        // Arrange
        $orderItemData = [
            'quantity' => 3,
            'price' => 1299.99,
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }

    /** @test */
    public function it_requires_quantity_when_creating_order_item(): void
    {
        // Arrange
        $orderItemData = [
            'product_id' => $this->product->id,
            'price' => 1299.99,
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function it_requires_price_when_creating_order_item(): void
    {
        // Arrange
        $orderItemData = [
            'product_id' => $this->product->id,
            'quantity' => 3,
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function it_validates_positive_quantity_when_creating_order_item(): void
    {
        // Arrange
        $orderItemData = [
            'product_id' => $this->product->id,
            'quantity' => 0, // Invalid quantity
            'price' => 1299.99,
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function it_validates_positive_price_when_creating_order_item(): void
    {
        // Arrange
        $orderItemData = [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => -100.00, // Invalid price
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function it_updates_order_item_successfully_with_valid_data(): void
    {
        // Arrange
        $updateData = [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 1599.99,
        ];

        // Act
        $response = $this->putJson("/api/order/{$this->order->id}/order-item/{$this->orderItem->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->orderItem->id,
                    'order_id' => $this->order->id,
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'price' => 1599.99,
                ],
            ]);

        // Verify order item was updated in database
        $this->assertDatabaseHas('order_items', [
            'id' => $this->orderItem->id,
            'quantity' => 5,
            'price' => 1599.99,
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_non_existent_order_item(): void
    {
        // Arrange
        $nonExistentId = 99999;
        $updateData = [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 1599.99,
        ];

        // Act
        $response = $this->putJson("/api/order/{$this->order->id}/order-item/{$nonExistentId}", $updateData);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_deletes_order_item_successfully(): void
    {
        // Act
        $response = $this->deleteJson("/api/order/{$this->order->id}/order-item/{$this->orderItem->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Order Item deleted successfully',
                ],
            ]);

        // Verify order item was soft deleted from database
        $this->assertSoftDeleted('order_items', [
            'id' => $this->orderItem->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_order_item(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->deleteJson("/api/order/{$this->order->id}/order-item/{$nonExistentId}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_handles_service_exceptions_during_creation(): void
    {
        // Arrange
        $this->mock(OrderItemService::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->andThrow(new Exception('Order item service error'));
        });

        $orderItemData = [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 999.99,
        ];

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertStatus(500);
    }

    /** @test */
    public function it_handles_model_not_found_exceptions_during_update(): void
    {
        // Arrange
        $this->mock(OrderItemService::class, function ($mock) {
            $mock->shouldReceive('update')
                ->once()
                ->andThrow(new ModelNotFoundException('Order item not found'));
        });

        $updateData = [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 1599.99,
        ];

        // Act
        $response = $this->putJson("/api/order/{$this->order->id}/order-item/{$this->orderItem->id}", $updateData);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_accepts_query_parameters_for_order_item_filtering(): void
    {
        // Arrange
        OrderItem::factory()->count(3)->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
        ]);

        // Act - Test with query parameters
        $response = $this->getJson("/api/order/{$this->order->id}/order-item?product_id={$this->product->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function it_calls_order_item_service_with_correct_parameters(): void
    {
        // Arrange
        $orderItemData = [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 999.99,
        ];

        $expectedData = $orderItemData;
        $expectedData['order_id'] = $this->order->id;
        
        $mockService = $this->mock(OrderItemService::class);
        $mockService->shouldReceive('store')
            ->once()
            ->with($this->order->id, $expectedData)
            ->andReturn($this->orderItem);

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $orderItemData);

        // Assert
        $response->assertCreated();
    }

    /** @test */
    public function it_returns_empty_list_when_no_order_items_exist(): void
    {
        // Arrange - Create a new order with no items
        $emptyOrder = Order::factory()->create();

        // Act
        $response = $this->getJson("/api/order/{$emptyOrder->id}/order-item");

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test data provider for order item validation scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function orderItemValidationDataProvider(): array
    {
        return [
            'missing product_id' => [
                'data' => ['quantity' => 2, 'price' => 99.99],
                'errors' => ['product_id'],
            ],
            'missing quantity' => [
                'data' => ['product_id' => 1, 'price' => 99.99],
                'errors' => ['quantity'],
            ],
            'missing price' => [
                'data' => ['product_id' => 1, 'quantity' => 2],
                'errors' => ['price'],
            ],
            'zero quantity' => [
                'data' => ['product_id' => 1, 'quantity' => 0, 'price' => 99.99],
                'errors' => ['quantity'],
            ],
            'negative quantity' => [
                'data' => ['product_id' => 1, 'quantity' => -1, 'price' => 99.99],
                'errors' => ['quantity'],
            ],
            'zero price' => [
                'data' => ['product_id' => 1, 'quantity' => 2, 'price' => 0],
                'errors' => ['price'],
            ],
            'negative price' => [
                'data' => ['product_id' => 1, 'quantity' => 2, 'price' => -10.00],
                'errors' => ['price'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider orderItemValidationDataProvider
     * @param array<string, mixed> $data
     * @param array<string> $expectedErrors
     */
    public function it_validates_order_item_data_correctly(array $data, array $expectedErrors): void
    {
        // Replace placeholder product_id with actual product id
        if (isset($data['product_id']) && $data['product_id'] === 1) {
            $data['product_id'] = $this->product->id;
        }

        // Act
        $response = $this->postJson("/api/order/{$this->order->id}/order-item", $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    /** @test */
    public function it_formats_response_correctly_using_order_item_resource(): void
    {
        // Act
        $response = $this->getJson("/api/order/{$this->order->id}/order-item/{$this->orderItem->id}");

        // Assert
        $response->assertOk();
        
        $data = $response->json('data');
        
        // Verify proper formatting
        $this->assertIsInt($data['id']);
        $this->assertIsInt($data['order_id']);
        $this->assertIsInt($data['product_id']);
        $this->assertIsInt($data['quantity']);
        $this->assertIsNumeric($data['price']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }
}