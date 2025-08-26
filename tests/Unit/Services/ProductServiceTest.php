<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductService;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Exception;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;
    protected ProductRepositoryInterface $mockRepository;
    protected Product $sampleProduct;
    protected Category $sampleCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->productService = new ProductService($this->mockRepository);
        
        // Create sample data for testing
        $this->sampleCategory = Category::factory()->make(['id' => 1, 'name' => 'Electronics']);
        $this->sampleProduct = Product::factory()->make([
            'id' => 1,
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'category_id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_retrieves_paginated_products_for_public_users(): void
    {
        // Arrange
        $filters = ['search' => 'laptop', 'category_id' => 1];
        $isAdmin = false;
        
        $mockPaginator = new LengthAwarePaginator(
            collect([$this->sampleProduct]),
            1,
            15,
            1
        );

        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->with($filters, $isAdmin)
            ->andReturn($mockPaginator);

        // Act
        $result = $this->productService->index($filters, $isAdmin);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals(1, $result->count());
    }

    /** @test */
    public function it_retrieves_paginated_products_for_admin_users(): void
    {
        // Arrange
        $filters = ['search' => 'laptop'];
        $isAdmin = true;
        
        $mockPaginator = new LengthAwarePaginator(
            collect([$this->sampleProduct]),
            1,
            15,
            1
        );

        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->with($filters, $isAdmin)
            ->andReturn($mockPaginator);

        // Act
        $result = $this->productService->index($filters, $isAdmin);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertTrue($result->total() > 0);
    }

    /** @test */
    public function it_retrieves_products_with_empty_filters(): void
    {
        // Arrange
        $filters = [];
        $isAdmin = false;
        
        $mockPaginator = new LengthAwarePaginator(
            collect([$this->sampleProduct]),
            1,
            15,
            1
        );

        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->with($filters, $isAdmin)
            ->andReturn($mockPaginator);

        // Act
        $result = $this->productService->index($filters, $isAdmin);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_shows_single_product_by_id(): void
    {
        // Arrange
        $productId = 1;

        $this->mockRepository
            ->shouldReceive('show')
            ->once()
            ->with($productId)
            ->andReturn($this->sampleProduct);

        // Act
        $result = $this->productService->show($productId);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($productId, $result->id);
        $this->assertEquals('Test Product', $result->name);
    }

    /** @test */
    public function it_throws_exception_when_product_not_found(): void
    {
        // Arrange
        $productId = 999;

        $this->mockRepository
            ->shouldReceive('show')
            ->once()
            ->with($productId)
            ->andThrow(new ModelNotFoundException('Product not found'));

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Product not found');
        
        $this->productService->show($productId);
    }

    /** @test */
    public function it_stores_new_product_with_valid_data(): void
    {
        // Arrange
        $productData = [
            'name' => 'New Product',
            'description' => 'New product description',
            'price' => 199.99,
            'category_id' => 1,
        ];

        $newProduct = Product::factory()->make([
            'id' => 2,
            'name' => 'New Product',
            'description' => 'New product description',
            'price' => 199.99,
            'category_id' => 1,
        ]);

        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->with($productData)
            ->andReturn($newProduct);

        // Act
        $result = $this->productService->store($productData);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('New Product', $result->name);
        $this->assertEquals(199.99, $result->price);
    }

    /** @test */
    public function it_throws_exception_when_storing_product_fails(): void
    {
        // Arrange
        $productData = [
            'name' => 'Invalid Product',
            'description' => 'Invalid description',
            'price' => -10.00, // Invalid price
            'category_id' => 999, // Invalid category
        ];

        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->with($productData)
            ->andThrow(new Exception('Failed to store product'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to store product');
        
        $this->productService->store($productData);
    }

    /** @test */
    public function it_updates_existing_product_with_valid_data(): void
    {
        // Arrange
        $productId = 1;
        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 299.99,
            'category_id' => 1,
        ];

        $updatedProduct = Product::factory()->make([
            'id' => 1,
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 299.99,
            'category_id' => 1,
        ]);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($productId, $updateData)
            ->andReturn($updatedProduct);

        // Act
        $result = $this->productService->update($updateData, $productId);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Updated Product', $result->name);
        $this->assertEquals(299.99, $result->price);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_product(): void
    {
        // Arrange
        $productId = 999;
        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 299.99,
            'category_id' => 1,
        ];

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($productId, $updateData)
            ->andThrow(new ModelNotFoundException('Product not found'));

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Product not found');
        
        $this->productService->update($updateData, $productId);
    }

    /** @test */
    public function it_destroys_product_by_id(): void
    {
        // Arrange
        $productId = 1;

        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with($productId)
            ->andReturn(true);

        // Act
        $result = $this->productService->destroy($productId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_destroying_non_existent_product(): void
    {
        // Arrange
        $productId = 999;

        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with($productId)
            ->andThrow(new ModelNotFoundException('Product not found'));

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Product not found');
        
        $this->productService->destroy($productId);
    }

    /** @test */
    public function it_bulk_destroys_products_by_ids(): void
    {
        // Arrange
        $productIds = [1, 2, 3];

        $this->mockRepository
            ->shouldReceive('bulkDestroy')
            ->once()
            ->with($productIds)
            ->andReturn(3); // Number of deleted products

        // Act
        $result = $this->productService->bulkDestroy($productIds);

        // Assert
        $this->assertEquals(3, $result);
    }

    /** @test */
    public function it_handles_empty_ids_array_for_bulk_destroy(): void
    {
        // Arrange
        $productIds = [];

        $this->mockRepository
            ->shouldReceive('bulkDestroy')
            ->once()
            ->with($productIds)
            ->andReturn(0);

        // Act
        $result = $this->productService->bulkDestroy($productIds);

        // Assert
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_throws_exception_for_bulk_destroy_when_repository_fails(): void
    {
        // Arrange
        $productIds = [1, 2, 3];

        $this->mockRepository
            ->shouldReceive('bulkDestroy')
            ->once()
            ->with($productIds)
            ->andThrow(new Exception('Bulk destroy failed'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Bulk destroy failed');
        
        $this->productService->bulkDestroy($productIds);
    }

    /**
     * Test data provider for various filter scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function filterDataProvider(): array
    {
        return [
            'search filter only' => [
                'filters' => ['search' => 'laptop'],
                'isAdmin' => false,
            ],
            'category filter only' => [
                'filters' => ['category_id' => 1],
                'isAdmin' => false,
            ],
            'both filters' => [
                'filters' => ['search' => 'gaming', 'category_id' => 1],
                'isAdmin' => false,
            ],
            'admin with filters' => [
                'filters' => ['search' => 'laptop', 'category_id' => 1],
                'isAdmin' => true,
            ],
            'pagination filters' => [
                'filters' => ['page' => 2, 'per_page' => 10],
                'isAdmin' => false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterDataProvider
     * @param array<string, mixed> $filters
     * @param bool $isAdmin
     */
    public function it_handles_various_filter_scenarios(array $filters, bool $isAdmin): void
    {
        // Arrange
        $mockPaginator = new LengthAwarePaginator(
            collect([$this->sampleProduct]),
            1,
            15,
            1
        );

        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->with($filters, $isAdmin)
            ->andReturn($mockPaginator);

        // Act
        $result = $this->productService->index($filters, $isAdmin);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_maintains_method_interface_contracts(): void
    {
        // This test ensures that the service maintains the expected method signatures
        
        $this->assertTrue(method_exists($this->productService, 'index'));
        $this->assertTrue(method_exists($this->productService, 'show'));
        $this->assertTrue(method_exists($this->productService, 'store'));
        $this->assertTrue(method_exists($this->productService, 'update'));
        $this->assertTrue(method_exists($this->productService, 'destroy'));
        $this->assertTrue(method_exists($this->productService, 'bulkDestroy'));
    }

    /** @test */
    public function it_passes_correct_parameters_to_repository_methods(): void
    {
        // Test that service correctly passes parameters to repository
        
        // Test show method
        $this->mockRepository
            ->shouldReceive('show')
            ->once()
            ->with(123)
            ->andReturn($this->sampleProduct);

        $result = $this->productService->show(123);
        $this->assertInstanceOf(Product::class, $result);

        // Test destroy method
        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with(456)
            ->andReturn(true);

        $result = $this->productService->destroy(456);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_correct_data_types_from_methods(): void
    {
        // Arrange and test index method return type
        $mockPaginator = new LengthAwarePaginator([], 0, 15, 1);
        $this->mockRepository->shouldReceive('index')->once()->andReturn($mockPaginator);
        
        $indexResult = $this->productService->index([], false);
        $this->assertInstanceOf(LengthAwarePaginator::class, $indexResult);

        // Test show method return type
        $this->mockRepository->shouldReceive('show')->once()->andReturn($this->sampleProduct);
        
        $showResult = $this->productService->show(1);
        $this->assertInstanceOf(Product::class, $showResult);

        // Test store method return type
        $this->mockRepository->shouldReceive('store')->once()->andReturn($this->sampleProduct);
        
        $storeResult = $this->productService->store([]);
        $this->assertInstanceOf(Product::class, $storeResult);

        // Test destroy method return type
        $this->mockRepository->shouldReceive('destroy')->once()->andReturn(true);
        
        $destroyResult = $this->productService->destroy(1);
        $this->assertTrue($destroyResult);
    }
}