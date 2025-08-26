<?php

namespace Tests\Feature\Product;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Image;
use App\Services\ProductService;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => 1,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'price' => 99.99,
        ]);
    }

    protected function actingAsAdmin(): self
    {
        $token = JWTAuth::fromUser($this->adminUser);

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    /** @test */
    public function it_retrieves_products_list_for_admin_successfully(): void
    {
        // Arrange
        Product::factory()->count(3)->create(['category_id' => $this->category->id]);

        // Act
        $response = $this->actingAsAdmin()->getJson('/api/admin/product');

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
                    'meta',
                ],
            ]);
    }

    /** @test */
    public function it_requires_admin_authentication_to_access_product_list(): void
    {
        // Act
        $response = $this->getJson('/api/admin/product');

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_retrieves_single_product_for_admin_successfully(): void
    {
        // Act
        $response = $this->actingAsAdmin()->getJson("/api/admin/product/{$this->product->id}");

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
                ],
            ]);
    }

    /** @test */
    public function it_creates_product_successfully_with_valid_data(): void
    {
        // Arrange
        $productData = [
            'name' => 'New Product',
            'description' => 'New product description',
            'category_id' => $this->category->id,
            'price' => 199.99,
        ];

        // Act
        $response = $this->actingAsAdmin()->postJson('/api/admin/product', $productData);

        // Assert
        $response->assertCreated()
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
                    'name' => 'New Product',
                    'description' => 'New product description',
                    'price' => 199.99,
                ],
            ]);

        // Verify product was created in database
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'description' => 'New product description',
            'price' => 199.99,
            'category_id' => $this->category->id,
        ]);
    }

    /** @test */
    public function it_requires_admin_authentication_to_create_product(): void
    {
        // Arrange
        $productData = [
            'name' => 'New Product',
            'description' => 'New product description',
            'category_id' => $this->category->id,
            'price' => 199.99,
        ];

        // Act
        $response = $this->postJson('/api/admin/product', $productData);

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_required_fields_when_creating_product(): void
    {
        // Act
        $response = $this->actingAsAdmin()->postJson('/api/admin/product', []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'category_id', 'price']);
    }

    /** @test */
    public function it_updates_product_successfully_with_valid_data(): void
    {
        // Arrange
        $updateData = [
            'name' => 'Updated Product Name',
            'description' => 'Updated description',
            'price' => 299.99,
            'category_id' => $this->category->id,
        ];

        // Act
        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/product/{$this->product->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->product->id,
                    'name' => 'Updated Product Name',
                    'description' => 'Updated description',
                    'price' => 299.99,
                ],
            ]);

        // Verify product was updated in database
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'Updated Product Name',
            'description' => 'Updated description',
            'price' => 299.99,
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_non_existent_product(): void
    {
        // Arrange
        $nonExistentId = 99999;
        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 299.99,
            'category_id' => $this->category->id,
        ];

        // Act
        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/product/$nonExistentId", $updateData);

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_deletes_product_successfully(): void
    {
        // Act
        $response = $this->actingAsAdmin()
            ->deleteJson("/api/admin/product/{$this->product->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Product deleted successfully',
                ],
            ]);

        // Verify product was soft deleted from database
        $this->assertSoftDeleted('products', [
            'id' => $this->product->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_product(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->actingAsAdmin()
            ->deleteJson("/api/admin/product/$nonExistentId");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_bulk_deletes_products_successfully(): void
    {
        // Arrange
        $product1 = Product::factory()->create(['category_id' => $this->category->id]);
        $product2 = Product::factory()->create(['category_id' => $this->category->id]);
        $product3 = Product::factory()->create(['category_id' => $this->category->id]);

        $idsToDelete = "$product1->id,$product2->id,$product3->id";

        // Act
        $response = $this->actingAsAdmin()
            ->deleteJson('/api/admin/product/bulk-destroy', [
                'ids' => $idsToDelete,
            ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Products deleted successfully',
                ],
            ]);

        // Verify products were soft deleted from database
        $this->assertSoftDeleted('products', ['id' => $product1->id]);
        $this->assertSoftDeleted('products', ['id' => $product2->id]);
        $this->assertSoftDeleted('products', ['id' => $product3->id]);
    }

    /** @test */
    public function it_uploads_images_to_product_successfully(): void
    {
        // Arrange
        $image1 = UploadedFile::fake()->image('product1.jpg', 10, 10);
        $image2 = UploadedFile::fake()->image('product2.jpg', 10, 10);

        // Act
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/product/upload-images/{$this->product->id}", [
                'images' => [$image1, $image2],
            ]);

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
            ]);
    }

    /** @test */
    public function it_requires_images_field_for_image_upload(): void
    {
        // Act
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/product/upload-images/{$this->product->id}", []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images']);
    }

    /** @test */
    public function it_validates_image_file_types_for_upload(): void
    {
        // Arrange
        $invalidFile = UploadedFile::fake()->create('document.txt', 100);

        // Act
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/product/upload-images/{$this->product->id}", [
                'images' => [$invalidFile],
            ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0']);
    }

    /** @test */
    public function it_sets_image_as_first_successfully(): void
    {
        // Arrange
        $image = Image::factory()->create();

        // Act
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/product/set-image-to-first', [
                'productId' => $this->product->id,
                'imageId' => $image->id,
            ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Successfully set first product image',
                ],
            ]);
    }

    /** @test */
    public function it_deletes_product_image_successfully(): void
    {
        // Arrange
        $image = Image::factory()->create();

        // Act
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/product/delete-image', [
                'productId' => $this->product->id,
                'imageId' => $image->id,
            ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Product Image deleted successfully',
                ],
            ]);
    }

    /** @test */
    public function it_handles_service_exceptions_gracefully(): void
    {
        // Arrange
        $this->mock(ProductService::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->andThrow(new Exception('Product service error'));
        });

        $productData = [
            'name' => 'New Product',
            'description' => 'New product description',
            'category_id' => $this->category->id,
            'price' => 199.99,
        ];

        // Act
        $response = $this->actingAsAdmin()->postJson('/api/admin/product', $productData);

        // Assert
        $response->assertStatus(500);
    }

    /** @test */
    public function it_handles_model_not_found_exceptions_during_update(): void
    {
        // Arrange
        $this->mock(ProductService::class, function ($mock) {
            $mock->shouldReceive('update')
                ->once()
                ->andThrow(new ModelNotFoundException('Product not found'));
        });

        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 299.99,
            'category_id' => $this->category->id,
        ];

        // Act
        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/product/{$this->product->id}", $updateData);

        // Assert
        $response->assertNotFound();
    }

    /**
     * Test data provider for product validation scenarios
     *
     * @return array<string, array<string, mixed>>
     */
    public static function productValidationDataProvider(): array
    {
        return [
            'missing name' => [
                'data' => ['description' => 'Test', 'category_id' => 1, 'price' => 99.99],
                'errors' => ['name'],
            ],
            'missing description' => [
                'data' => ['name' => 'Test', 'category_id' => 1, 'price' => 99.99],
                'errors' => ['description'],
            ],
            'missing category_id' => [
                'data' => ['name' => 'Test', 'description' => 'Test', 'price' => 99.99],
                'errors' => ['category_id'],
            ],
            'missing price' => [
                'data' => ['name' => 'Test', 'description' => 'Test', 'category_id' => 1],
                'errors' => ['price'],
            ],
            'invalid price format' => [
                'data' => ['name' => 'Test', 'description' => 'Test', 'category_id' => 1, 'price' => 'invalid'],
                'errors' => ['price'],
            ],
            'negative price' => [
                'data' => ['name' => 'Test', 'description' => 'Test', 'category_id' => 1, 'price' => -10.00],
                'errors' => ['price'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider productValidationDataProvider
     * @param array<string, mixed> $data
     * @param array<string> $expectedErrors
     */
    public function it_validates_product_data_correctly_for_creation(array $data, array $expectedErrors): void
    {
        // Act
        $response = $this->actingAsAdmin()->postJson('/api/admin/product', $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    /** @test */
    public function it_calls_product_service_with_admin_flag_true(): void
    {
        // Arrange
        $mockService = $this->mock(ProductService::class);
        $mockService->shouldReceive('index')
            ->once()
            ->with([], true) // isAdmin should be true for AdminProductController
            ->andReturn(new LengthAwarePaginator(
                collect([$this->product]),
                1,
                15,
                1
            ));

        // Act
        $response = $this->actingAsAdmin()->getJson('/api/admin/product');

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function it_handles_image_service_exceptions_during_upload(): void
    {
        // Arrange
        $this->mock(ProductImageService::class, function ($mock) {
            $mock->shouldReceive('uploadImages')
                ->once()
                ->andThrow(new Exception('Image upload failed'));
        });

        $image = UploadedFile::fake()->image('product.jpg');

        // Act
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/product/upload-images/{$this->product->id}", [
                'images' => [$image],
            ]);

        // Assert
        $response->assertStatus(500);
    }
}
