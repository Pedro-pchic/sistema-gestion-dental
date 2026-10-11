<?php

namespace Tests\Feature;

use App\Exceptions\Services\ProductImageStorageException;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductImageStorage;
use Cloudinary\Api\Exception\ApiError;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProductImageAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_create_a_product_with_a_cloudinary_image(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class))->andReturn([
            'url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/shoe.png',
            'public_id' => 'sigec/producto/shoe',
        ]);
        $storage->shouldNotReceive('delete');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->post(route('productos.store'), $this->productData($category) + [
                'image' => UploadedFile::fake()->image('shoe.png'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'sku' => 'IMG-001',
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/shoe.png',
            'image_public_id' => 'sigec/producto/shoe',
        ]);
    }

    public function test_administrator_can_create_a_product_without_an_image(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $storage->shouldNotReceive('delete');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->post(route('productos.store'), $this->productData($category))
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'sku' => 'IMG-001',
            'image_url' => null,
            'image_public_id' => null,
        ]);
    }

    public function test_invalid_image_content_is_rejected_before_upload(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->post(route('productos.store'), $this->productData($category) + [
                'image' => UploadedFile::fake()
                    ->createWithContent('fake.jpg', 'not an image')
                    ->mimeType('text/plain'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_images_larger_than_two_megabytes_are_rejected(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $this->app->instance(ProductImageStorage::class, $storage);
        $largeImage = UploadedFile::fake()->image('large.png');
        $largeImage->size(2049);

        $this->actingAs($administrator)
            ->post(route('productos.store'), $this->productData($category) + ['image' => $largeImage])
            ->assertSessionHasErrors([
                'image' => 'La fotografía no puede superar 2 MB.',
            ]);
    }

    public function test_updating_without_an_image_preserves_the_current_image(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $storage->shouldNotReceive('delete');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product))
            ->assertRedirectToRoute('productos.show', $product);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
    }

    public function test_updating_replaces_the_image_and_then_deletes_the_previous_image(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldReceive('upload')->once()->andReturn([
            'url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/replacement.png',
            'public_id' => 'sigec/producto/replacement',
        ]);
        $storage->shouldReceive('delete')->once()->with('sigec/producto/current');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product) + [
                'image' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertRedirectToRoute('productos.show', $product);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/replacement.png',
            'image_public_id' => 'sigec/producto/replacement',
        ]);
    }

    public function test_administrator_can_explicitly_remove_the_current_image(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $storage->shouldReceive('delete')->once()->with('sigec/producto/current');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product) + [
                'remove_image' => true,
            ])
            ->assertRedirectToRoute('productos.show', $product);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_url' => null,
            'image_public_id' => null,
        ]);
    }

    public function test_upload_failure_keeps_the_existing_image(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldReceive('upload')->once()->andThrow(new ProductImageStorageException(
            'Cloudinary failure',
            0,
            new ApiError('Provider detail must not be logged.', 0),
        ));
        $storage->shouldNotReceive('delete');
        $this->app->instance(ProductImageStorage::class, $storage);
        Log::shouldReceive('warning')
            ->once()
            ->with('Cloudinary product image upload failed.', Mockery::on(static fn (array $context): bool => $context === [
                'diagnostic_code' => 'CLOUDINARY_PRODUCT_IMAGE_UPLOAD_FAILED',
                'exception_type' => ApiError::class,
                'exception_code' => 0,
                'product_id' => $product->id,
            ]));

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product) + [
                'image' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertSessionHasErrors([
                'image' => 'No se pudo cargar la fotografía. La imagen actual se conservó.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_public_id' => 'sigec/producto/current',
        ]);
    }

    public function test_php_upload_error_is_logged_before_validation_and_never_reaches_cloudinary(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $storage->shouldNotReceive('delete');
        $this->app->instance(ProductImageStorage::class, $storage);
        Log::shouldReceive('warning')
            ->once()
            ->with('PHP rejected a product image upload.', Mockery::on(static fn (array $context): bool => $context === [
                'diagnostic_id' => 'SIGEC-PHP-UPLOAD-001',
                'upload_error_code' => UPLOAD_ERR_NO_TMP_DIR,
            ]));
        $failedUpload = new UploadedFile('', 'Foto1.jpg', 'image/jpeg', UPLOAD_ERR_NO_TMP_DIR, true);

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product) + [
                'image' => $failedUpload,
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_public_id' => 'sigec/producto/current',
        ]);
    }

    public function test_failed_database_update_cleans_up_the_new_image_and_keeps_the_old_reference(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldReceive('upload')->once()->andReturn([
            'url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/replacement.png',
            'public_id' => 'sigec/producto/replacement',
        ]);
        $storage->shouldReceive('delete')->once()->with('sigec/producto/replacement');
        $this->app->instance(ProductImageStorage::class, $storage);
        Product::updating(static function (Product $updatedProduct): void {
            throw new RuntimeException('Database detail must not be shown.');
        });

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product) + [
                'image' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertSessionHasErrors([
                'image' => 'No se pudo guardar el producto. La imagen anterior se conservó.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
    }

    public function test_failed_remote_cleanup_keeps_the_new_image_and_reports_manual_cleanup(): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/current.jpg',
            'image_public_id' => 'sigec/producto/current',
        ]);
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldReceive('upload')->once()->andReturn([
            'url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/replacement.png',
            'public_id' => 'sigec/producto/replacement',
        ]);
        $storage->shouldReceive('delete')->once()->with('sigec/producto/current')
            ->andThrow(new ProductImageStorageException('Cloudinary failure'));
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), $this->productData($product->category, $product) + [
                'image' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertRedirectToRoute('productos.show', $product)
            ->assertSessionHas('warning', 'La fotografía anterior no se pudo eliminar y requiere limpieza en Cloudinary.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_public_id' => 'sigec/producto/replacement',
        ]);
    }

    public function test_unauthorized_user_cannot_upload_product_images(): void
    {
        $salesUser = User::factory()
            ->for(Role::factory()->create(['name' => Role::SALES]))
            ->create(['is_active' => true]);
        $category = Category::factory()->create();
        $storage = Mockery::mock(ProductImageStorage::class);
        $storage->shouldNotReceive('upload');
        $this->app->instance(ProductImageStorage::class, $storage);

        $this->actingAs($salesUser)
            ->post(route('productos.store'), $this->productData($category) + [
                'image' => UploadedFile::fake()->image('shoe.png'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_catalog_shows_cloudinary_images_and_keeps_the_default_monogram_without_one(): void
    {
        $imageProduct = Product::factory()->create([
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/shoe.jpg',
            'image_public_id' => 'sigec/producto/shoe',
        ]);
        $plainProduct = Product::factory()->create([
            'name' => 'Sandalia Clara',
            'image_url' => null,
            'image_public_id' => null,
        ]);

        $this->get(route('catalogo.index'))
            ->assertOk()
            ->assertSee('https://res.cloudinary.com/sigec/image/upload/f_auto,q_auto,w_480,c_fill/v1/sigec/producto/shoe.jpg', false)
            ->assertSeeText('Sa');

        $this->get(route('catalogo.show', $imageProduct))
            ->assertOk()
            ->assertSee('https://res.cloudinary.com/sigec/image/upload/f_auto,q_auto,w_960,c_fill/v1/sigec/producto/shoe.jpg', false);

        $this->actingAs($this->administrator())
            ->get(route('productos.show', $imageProduct))
            ->assertOk()
            ->assertSee('https://res.cloudinary.com/sigec/image/upload/f_auto,q_auto,w_640,c_fill/v1/sigec/producto/shoe.jpg', false);

        $this->get(route('catalogo.show', $plainProduct))
            ->assertOk()
            ->assertSeeText('Sa');
    }

    private function administrator(): User
    {
        $role = Role::factory()->create(['name' => Role::ADMINISTRATOR]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productData(Category $category, ?Product $product = null): array
    {
        return [
            'category_id' => $category->id,
            'sku' => $product?->sku ?? 'IMG-001',
            'name' => $product?->name ?? 'Zapato fotografiado',
            'description' => $product?->description,
            'price' => $product?->price ?? '450.00',
            'is_active' => $product?->is_active ?? true,
        ];
    }
}
