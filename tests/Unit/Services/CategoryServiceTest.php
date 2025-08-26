<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CategoryService;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Exception;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryService $categoryService;
    protected CategoryRepositoryInterface $mockRepository;
    protected Category $sampleCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(CategoryRepositoryInterface::class);
        $this->categoryService = new CategoryService($this->mockRepository);
        
        // Create sample data for testing
        $this->sampleCategory = Category::factory()->make([
            'id' => 1,
            'name' => 'Electronics',
            'description' => 'Electronic devices and gadgets',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_retrieves_all_categories(): void
    {
        // Arrange
        $categories = EloquentCollection::make([
            $this->sampleCategory,
            Category::factory()->make(['id' => 2, 'name' => 'Books']),
            Category::factory()->make(['id' => 3, 'name' => 'Clothing']),
        ]);

        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->andReturn($categories);

        // Act
        $result = $this->categoryService->index();

        // Assert
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertEquals('Electronics', $result->first()->name);
    }

    /** @test */
    public function it_returns_empty_collection_when_no_categories_exist(): void
    {
        // Arrange
        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->andReturn(EloquentCollection::make());

        // Act
        $result = $this->categoryService->index();

        // Assert
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertEquals(0, $result->count());
        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function it_shows_single_category_by_id(): void
    {
        // Arrange
        $categoryId = 1;

        $this->mockRepository
            ->shouldReceive('show')
            ->once()
            ->with($categoryId)
            ->andReturn($this->sampleCategory);

        // Act
        $result = $this->categoryService->show($categoryId);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals($categoryId, $result->id);
        $this->assertEquals('Electronics', $result->name);
        $this->assertEquals('Electronic devices and gadgets', $result->description);
    }

    /** @test */
    public function it_throws_exception_when_category_not_found(): void
    {
        // Arrange
        $categoryId = 999;

        $this->mockRepository
            ->shouldReceive('show')
            ->once()
            ->with($categoryId)
            ->andThrow(new ModelNotFoundException('Category not found'));

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Category not found');
        
        $this->categoryService->show($categoryId);
    }

    /** @test */
    public function it_stores_new_category_with_valid_data(): void
    {
        // Arrange
        $categoryData = [
            'name' => 'Home & Garden',
            'description' => 'Home improvement and garden supplies',
        ];

        $newCategory = Category::factory()->make([
            'id' => 2,
            'name' => 'Home & Garden',
            'description' => 'Home improvement and garden supplies',
        ]);

        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->with($categoryData)
            ->andReturn($newCategory);

        // Act
        $result = $this->categoryService->store($categoryData);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Home & Garden', $result->name);
        $this->assertEquals('Home improvement and garden supplies', $result->description);
    }

    /** @test */
    public function it_stores_category_with_only_name_field(): void
    {
        // Arrange
        $categoryData = [
            'name' => 'Sports',
        ];

        $newCategory = Category::factory()->make([
            'id' => 2,
            'name' => 'Sports',
            'description' => null,
        ]);

        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->with($categoryData)
            ->andReturn($newCategory);

        // Act
        $result = $this->categoryService->store($categoryData);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Sports', $result->name);
        $this->assertNull($result->description);
    }

    /** @test */
    public function it_throws_exception_when_storing_category_fails(): void
    {
        // Arrange
        $categoryData = [
            'name' => 'Duplicate Category',
            'description' => 'This category already exists',
        ];

        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->with($categoryData)
            ->andThrow(new Exception('Failed to store category'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to store category');
        
        $this->categoryService->store($categoryData);
    }

    /** @test */
    public function it_updates_existing_category_with_valid_data(): void
    {
        // Arrange
        $categoryId = 1;
        $updateData = [
            'name' => 'Updated Electronics',
            'description' => 'Updated description for electronics',
        ];

        $updatedCategory = Category::factory()->make([
            'id' => 1,
            'name' => 'Updated Electronics',
            'description' => 'Updated description for electronics',
        ]);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($categoryId, $updateData)
            ->andReturn($updatedCategory);

        // Act
        $result = $this->categoryService->update($updateData, $categoryId);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Updated Electronics', $result->name);
        $this->assertEquals('Updated description for electronics', $result->description);
    }

    /** @test */
    public function it_updates_only_name_field_of_category(): void
    {
        // Arrange
        $categoryId = 1;
        $updateData = [
            'name' => 'Updated Name Only',
        ];

        $updatedCategory = Category::factory()->make([
            'id' => 1,
            'name' => 'Updated Name Only',
            'description' => 'Original description',
        ]);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($categoryId, $updateData)
            ->andReturn($updatedCategory);

        // Act
        $result = $this->categoryService->update($updateData, $categoryId);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Updated Name Only', $result->name);
        $this->assertEquals('Original description', $result->description);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_category(): void
    {
        // Arrange
        $categoryId = 999;
        $updateData = [
            'name' => 'Non-existent Category',
            'description' => 'This category does not exist',
        ];

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($categoryId, $updateData)
            ->andThrow(new ModelNotFoundException('Category not found'));

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Category not found');
        
        $this->categoryService->update($updateData, $categoryId);
    }

    /** @test */
    public function it_destroys_category_by_id(): void
    {
        // Arrange
        $categoryId = 1;

        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with($categoryId)
            ->andReturn(true);

        // Act
        $result = $this->categoryService->destroy($categoryId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_category_cannot_be_destroyed(): void
    {
        // Arrange
        $categoryId = 1;

        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with($categoryId)
            ->andReturn(false);

        // Act
        $result = $this->categoryService->destroy($categoryId);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_throws_exception_when_destroying_non_existent_category(): void
    {
        // Arrange
        $categoryId = 999;

        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with($categoryId)
            ->andThrow(new ModelNotFoundException('Category not found'));

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Category not found');
        
        $this->categoryService->destroy($categoryId);
    }

    /**
     * Test data provider for various category data scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function categoryDataProvider(): array
    {
        return [
            'complete category data' => [
                'data' => [
                    'name' => 'Complete Category',
                    'description' => 'Complete description',
                ],
                'expected_name' => 'Complete Category',
                'expected_description' => 'Complete description',
            ],
            'name only' => [
                'data' => [
                    'name' => 'Name Only Category',
                ],
                'expected_name' => 'Name Only Category',
                'expected_description' => null,
            ],
            'long description' => [
                'data' => [
                    'name' => 'Long Desc Category',
                    'description' => 'This is a very long description that contains multiple sentences and provides detailed information about the category.',
                ],
                'expected_name' => 'Long Desc Category',
                'expected_description' => 'This is a very long description that contains multiple sentences and provides detailed information about the category.',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider categoryDataProvider
     * @param array<string, mixed> $categoryData
     * @param string $expectedName
     * @param string|null $expectedDescription
     */
    public function it_handles_various_category_data_scenarios(
        array $categoryData, 
        string $expectedName, 
        ?string $expectedDescription
    ): void {
        // Arrange
        $newCategory = Category::factory()->make([
            'id' => 1,
            'name' => $expectedName,
            'description' => $expectedDescription,
        ]);

        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->with($categoryData)
            ->andReturn($newCategory);

        // Act
        $result = $this->categoryService->store($categoryData);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals($expectedName, $result->name);
        $this->assertEquals($expectedDescription, $result->description);
    }

    /** @test */
    public function it_maintains_method_interface_contracts(): void
    {
        // This test ensures that the service maintains the expected method signatures
        
        $this->assertTrue(method_exists($this->categoryService, 'index'));
        $this->assertTrue(method_exists($this->categoryService, 'show'));
        $this->assertTrue(method_exists($this->categoryService, 'store'));
        $this->assertTrue(method_exists($this->categoryService, 'update'));
        $this->assertTrue(method_exists($this->categoryService, 'destroy'));
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
            ->andReturn($this->sampleCategory);

        $result = $this->categoryService->show(123);
        $this->assertInstanceOf(Category::class, $result);

        // Test destroy method
        $this->mockRepository
            ->shouldReceive('destroy')
            ->once()
            ->with(456)
            ->andReturn(true);

        $result = $this->categoryService->destroy(456);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_correct_data_types_from_methods(): void
    {
        // Test index method return type
        $mockCollection = EloquentCollection::make([$this->sampleCategory]);
        $this->mockRepository->shouldReceive('index')->once()->andReturn($mockCollection);
        
        $indexResult = $this->categoryService->index();
        $this->assertInstanceOf(EloquentCollection::class, $indexResult);

        // Test show method return type
        $this->mockRepository->shouldReceive('show')->once()->andReturn($this->sampleCategory);
        
        $showResult = $this->categoryService->show(1);
        $this->assertInstanceOf(Category::class, $showResult);

        // Test store method return type
        $this->mockRepository->shouldReceive('store')->once()->andReturn($this->sampleCategory);
        
        $storeResult = $this->categoryService->store([]);
        $this->assertInstanceOf(Category::class, $storeResult);

        // Test destroy method return type
        $this->mockRepository->shouldReceive('destroy')->once()->andReturn(true);
        
        $destroyResult = $this->categoryService->destroy(1);
        $this->assertIsBool($destroyResult);
    }

    /** @test */
    public function it_handles_repository_exceptions_gracefully(): void
    {
        // Test that service doesn't catch exceptions but lets them bubble up
        // This is important for proper error handling in controllers
        
        $this->mockRepository
            ->shouldReceive('index')
            ->once()
            ->andThrow(new Exception('Database connection error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database connection error');
        
        $this->categoryService->index();
    }

    /** @test */
    public function it_handles_empty_update_data(): void
    {
        // Arrange
        $categoryId = 1;
        $updateData = [];

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($categoryId, $updateData)
            ->andReturn($this->sampleCategory);

        // Act
        $result = $this->categoryService->update($updateData, $categoryId);

        // Assert
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('Electronics', $result->name);
    }
}