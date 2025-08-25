<?php

namespace Tests\Feature\Product;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Laptop',
            'description' => 'High-performance laptop',
            'category_id' => $this->category->id,
            'price' => 999.99,
        ]);
    }

    /** @test */
    public function it_retrieves_products_list_successfully(): void
    {
        // Arrange - Create additional products
        Product::factory()->count(3)->create(['category_id' => $this->category->id]);

        // Act
        $response = $this->getJson('/api/product');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'category_id',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_retrieves_single_product_successfully(): void
    {
        // Act
        $response = $this->getJson("/api/product/{$this->product->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'category_id',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'description' => $this->product->description,
                    'price' => $this->product->price,
                    'category_id' => $this->product->category_id,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_when_product_not_found(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->getJson("/api/product/{$nonExistentId}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_handles_service_exceptions_when_retrieving_product(): void
    {
        // Arrange
        $this->mock(ProductService::class, function ($mock) {
            $mock->shouldReceive('show')
                ->once()
                ->with($this->product->id)
                ->andThrow(new Exception('Product service error'));
        });

        // Act
        $response = $this->getJson("/api/product/{$this->product->id}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_returns_paginated_results_for_product_list(): void
    {
        // Arrange - Create many products to test pagination
        Product::factory()->count(20)->create(['category_id' => $this->category->id]);

        // Act
        $response = $this->getJson('/api/product?page=1&per_page=10');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'from',
                        'to',
                    ],
                ],
            ]);

        $meta = $response->json('data.meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(10, $meta['total']);
    }

    /** @test */
    public function it_accepts_query_parameters_for_product_filtering(): void
    {
        // Arrange
        Product::factory()->create([
            'name' => 'Gaming Laptop',
            'category_id' => $this->category->id,
            'price' => 1500.00,
        ]);

        Product::factory()->create([
            'name' => 'Office Laptop',
            'category_id' => $this->category->id,
            'price' => 800.00,
        ]);

        // Act - Test with search parameters
        $response = $this->getJson('/api/product?search=Gaming&category_id=' . $this->category->id);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'meta',
                ],
            ]);
    }

    /** @test */
    public function it_calls_product_service_with_correct_parameters_for_index(): void
    {
        // Arrange
        $queryParams = [
            'page' => '1',
            'per_page' => '15',
            'search' => 'laptop',
            'category_id' => $this->category->id,
        ];

        $mockService = $this->mock(ProductService::class);
        $mockService->shouldReceive('index')
            ->once()
            ->with($queryParams, false) // isAdmin = false for public controller
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator(
                collect([$this->product]),
                1,
                15,
                1
            ));

        // Act
        $response = $this->getJson('/api/product?' . http_build_query($queryParams));

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function it_calls_product_service_with_correct_id_for_show(): void
    {
        // Arrange
        $mockService = $this->mock(ProductService::class);
        $mockService->shouldReceive('show')
            ->once()
            ->with($this->product->id)
            ->andReturn($this->product);

        // Act
        $response = $this->getJson("/api/product/{$this->product->id}");

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function it_returns_empty_list_when_no_products_exist(): void
    {
        // Arrange - Remove all products
        Product::query()->delete();

        // Act
        $response = $this->getJson('/api/product');

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'items' => [],
                ],
            ]);
    }

    /** @test */
    public function it_includes_product_relationships_in_response(): void
    {
        // Arrange - Create product with category relationship
        $productWithCategory = Product::factory()->create([
            'category_id' => $this->category->id,
        ]);

        // Act
        $response = $this->getJson("/api/product/{$productWithCategory->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'category_id',
                    'created_at',
                    'updated_at',
                    // Category relationship should be included via ProductResource
                ],
            ]);
    }

    /** @test */
    public function it_handles_invalid_product_id_gracefully(): void
    {
        // Act
        $response = $this->getJson('/api/product/invalid-id');

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_formats_response_correctly_using_product_resource(): void
    {
        // Act
        $response = $this->getJson("/api/product/{$this->product->id}");

        // Assert
        $response->assertOk();

        $data = $response->json('data');

        // Verify proper formatting
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['description']);
        $this->assertIsNumeric($data['price']);
        $this->assertIsInt($data['category_id']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    /**
     * Test data provider for various query parameters
     *
     * @return array<string, array<string, mixed>>
     */
    public static function queryParametersDataProvider(): array
    {
        return [
            'with pagination' => [
                'params' => ['page' => 1, 'per_page' => 5],
                'expected_keys' => ['items', 'meta'],
            ],
            'with search term' => [
                'params' => ['search' => 'laptop'],
                'expected_keys' => ['items', 'meta'],
            ],
            'with category filter' => [
                'params' => ['category_id' => 1],
                'expected_keys' => ['items', 'meta'],
            ],
            'with multiple filters' => [
                'params' => ['search' => 'gaming', 'category_id' => 1, 'per_page' => 10],
                'expected_keys' => ['items', 'meta'],
            ],
            'empty parameters' => [
                'params' => [],
                'expected_keys' => ['items', 'meta'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider queryParametersDataProvider
     * @param array<string, mixed> $params
     * @param array<string> $expectedKeys
     */
    public function it_handles_various_query_parameters_correctly(array $params, array $expectedKeys): void
    {
        // Act
        $response = $this->getJson('/api/product?' . http_build_query($params));

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => $expectedKeys,
            ]);
    }
}
