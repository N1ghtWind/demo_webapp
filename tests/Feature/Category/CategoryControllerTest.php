<?php

namespace Tests\Feature\Category;

use Tests\TestCase;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create([
            'name' => 'Electronics',
        ]);
    }

    /** @test */
    public function it_retrieves_categories_list_successfully(): void
    {
        // Arrange
        Category::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/category');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify we have at least 4 categories (1 from setUp + 3 from factory)
        $this->assertGreaterThanOrEqual(4, count($response->json('data')));
    }

    /** @test */
    public function it_retrieves_single_category_successfully(): void
    {
        // Act
        $response = $this->getJson("/api/category/{$this->category->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'description' => $this->category->description,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_when_category_not_found(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->getJson("/api/category/{$nonExistentId}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_creates_category_successfully_with_valid_data(): void
    {
        // Arrange
        $categoryData = [
            'name' => 'Home & Garden',
            'description' => 'Home improvement and garden supplies',
        ];

        // Act
        $response = $this->postJson('/api/category', $categoryData);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Home & Garden',
                    'description' => 'Home improvement and garden supplies',
                ],
            ]);

        // Verify category was created in database
        $this->assertDatabaseHas('categories', [
            'name' => 'Home & Garden',
            'description' => 'Home improvement and garden supplies',
        ]);
    }

    /** @test */
    public function it_requires_name_field_when_creating_category(): void
    {
        // Arrange
        $categoryData = [
            'description' => 'Some description',
        ];

        // Act
        $response = $this->postJson('/api/category', $categoryData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_allows_optional_description_when_creating_category(): void
    {
        // Arrange
        $categoryData = [
            'name' => 'Sports',
        ];

        // Act
        $response = $this->postJson('/api/category', $categoryData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Sports',
                ],
            ]);

        // Verify category was created in database
        $this->assertDatabaseHas('categories', [
            'name' => 'Sports',
        ]);
    }

    /** @test */
    public function it_requires_unique_category_name(): void
    {
        // Arrange
        $categoryData = [
            'name' => $this->category->name, // Using existing category name
            'description' => 'Different description',
        ];

        // Act
        $response = $this->postJson('/api/category', $categoryData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_updates_category_successfully_with_valid_data(): void
    {
        // Arrange
        $updateData = [
            'name' => 'Updated Electronics',
            'description' => 'Updated description for electronics',
        ];

        // Act
        $response = $this->putJson("/api/category/{$this->category->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->category->id,
                    'name' => 'Updated Electronics',
                ],
            ]);

        // Verify category was updated in database
        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name' => 'Updated Electronics',
            'description' => 'Updated description for electronics',
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_non_existent_category(): void
    {
        // Arrange
        $nonExistentId = 99999;
        $updateData = [
            'name' => 'Updated Category',
            'description' => 'Updated description',
        ];

        // Act
        $response = $this->putJson("/api/category/{$nonExistentId}", $updateData);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_requires_unique_name_when_updating_category(): void
    {
        // Arrange
        $otherCategory = Category::factory()->create(['name' => 'Other Category']);

        $updateData = [
            'name' => $otherCategory->name, // Using another existing category name
            'description' => 'Updated description',
        ];

        // Act
        $response = $this->putJson("/api/category/{$this->category->id}", $updateData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_allows_same_name_when_updating_category_with_unchanged_name(): void
    {
        // Arrange - Update with same name but different description
        $updateData = [
            'name' => $this->category->name, // Same name
            'description' => 'Updated description only',
        ];

        // Act
        $response = $this->putJson("/api/category/{$this->category->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ],
            ]);
    }

    /** @test */
    public function it_deletes_category_successfully(): void
    {
        // Act
        $response = $this->deleteJson("/api/category/{$this->category->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Category deleted successfully',
                ],
            ]);

        // Verify category was deleted from database
        $this->assertDatabaseMissing('categories', [
            'id' => $this->category->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_category(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->deleteJson("/api/category/{$nonExistentId}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_handles_service_exceptions_during_creation(): void
    {
        // Arrange
        $this->mock(CategoryService::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->andThrow(new Exception('Category service error'));
        });

        $categoryData = [
            'name' => 'New Category',
        ];

        // Act
        $response = $this->postJson('/api/category', $categoryData);

        // Assert
        $response->assertStatus(500);
    }

    /** @test */
    public function it_handles_model_not_found_exceptions_during_update(): void
    {
        // Arrange
        $this->mock(CategoryService::class, function ($mock) {
            $mock->shouldReceive('update')
                ->once()
                ->andThrow(new ModelNotFoundException('Category not found'));
        });

        $updateData = [
            'name' => 'Updated Category',
            'description' => 'Updated description',
        ];

        // Act
        $response = $this->putJson("/api/category/{$this->category->id}", $updateData);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_handles_service_exceptions_during_retrieval(): void
    {
        // Arrange
        $this->mock(CategoryService::class, function ($mock) {
            $mock->shouldReceive('show')
                ->once()
                ->with($this->category->id)
                ->andThrow(new Exception('Category retrieval error'));
        });

        // Act
        $response = $this->getJson("/api/category/{$this->category->id}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_calls_category_service_with_correct_data_for_creation(): void
    {
        // Arrange
        $categoryData = [
            'name' => 'Service Test Category',
            'description' => 'Service test description',
        ];

        $mockService = $this->mock(CategoryService::class);
        $mockService->shouldReceive('store')
            ->once()
            ->with($categoryData)
            ->andReturn($this->category);

        // Act
        $response = $this->postJson('/api/category', $categoryData);

        // Assert
        $response->assertCreated();
    }

    /** @test */
    public function it_calls_category_service_with_correct_parameters_for_update(): void
    {
        // Arrange
        $updateData = [
            'name' => 'Service Updated Category',
        ];

        $mockService = $this->mock(CategoryService::class);
        $mockService->shouldReceive('update')
            ->once()
            ->with($updateData, $this->category->id)
            ->andReturn($this->category);

        // Act
        $response = $this->putJson("/api/category/{$this->category->id}", $updateData);

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function it_returns_empty_list_when_no_categories_exist(): void
    {
        // Arrange - Remove all categories
        Category::query()->delete();

        // Act
        $response = $this->getJson('/api/category');

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test data provider for category validation scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function categoryValidationDataProvider(): array
    {
        return [
            'empty name' => [
                'data' => ['name' => '', 'description' => 'Some description'],
                'errors' => ['name'],
            ],
            'null name' => [
                'data' => ['description' => 'Some description'],
                'errors' => ['name'],
            ],
            'name too long' => [
                'data' => ['name' => str_repeat('A', 256), 'description' => 'Some description'],
                'errors' => ['name'],
            ],
            'description too long' => [
                'data' => ['name' => 'Valid Name', 'description' => str_repeat('A', 1001)],
                'errors' => ['description'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider categoryValidationDataProvider
     * @param array<string, mixed> $data
     * @param array<string> $expectedErrors
     */
    public function it_validates_category_data_correctly(array $data, array $expectedErrors): void
    {
        // Act
        $response = $this->postJson('/api/category', $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    /** @test */
    public function it_formats_response_correctly_using_category_resource(): void
    {
        // Act
        $response = $this->getJson("/api/category/{$this->category->id}");

        // Assert
        $response->assertOk();

        $data = $response->json('data');

        // Verify proper formatting
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['description']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    /** @test */
    public function it_returns_proper_json_structure_for_category_list(): void
    {
        // Arrange
        Category::factory()->count(2)->create();

        // Act
        $response = $this->getJson('/api/category');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data'));
        $this->assertGreaterThan(0, count($response->json('data')));
    }
}
