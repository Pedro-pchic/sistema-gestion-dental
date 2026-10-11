<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_create_product(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();

        $this->actingAs($administrator)
            ->post(route('productos.store'), [
                'category_id' => $category->id,
                'sku' => 'CAL-001',
                'name' => 'Tenis urbano',
                'description' => 'Calzado casual.',
                'price' => '499.90',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'CAL-001',
            'name' => 'Tenis urbano',
            'price' => 499.90,
            'is_active' => true,
        ]);

        $product = Product::query()->where('sku', 'CAL-001')->firstOrFail();

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'stock' => 0,
            'minimum_stock' => 0,
        ]);
    }

    public function test_administrator_can_view_product_with_its_category(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create(['name' => 'Formales']);
        $product = Product::factory()->for($category)->create([
            'sku' => 'FOR-001',
            'name' => 'Zapato Oxford',
        ]);

        $this->actingAs($administrator)
            ->get(route('productos.show', $product))
            ->assertOk()
            ->assertSeeText('FOR-001')
            ->assertSeeText('Zapato Oxford')
            ->assertSeeText('Formales');
    }

    public function test_administrator_can_update_product_without_its_sku_conflicting_with_itself(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create([
            'sku' => 'BOT-001',
            'name' => 'Bota original',
        ]);

        $this->actingAs($administrator)
            ->put(route('productos.update', $product), [
                'category_id' => $category->id,
                'sku' => 'BOT-001',
                'name' => 'Bota actualizada',
                'description' => null,
                'price' => '725.00',
                'is_active' => true,
            ])
            ->assertRedirectToRoute('productos.show', $product);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'BOT-001',
            'name' => 'Bota actualizada',
            'price' => 725.00,
        ]);
    }

    public function test_sku_must_be_unique(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['sku' => 'DUP-001']);

        $this->actingAs($administrator)
            ->post(route('productos.store'), [
                'category_id' => $category->id,
                'sku' => 'DUP-001',
                'name' => 'Producto duplicado',
                'description' => null,
                'price' => '100.00',
                'is_active' => true,
            ])
            ->assertSessionHasErrors([
                'sku' => 'Ya existe un producto con este SKU.',
            ]);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_category_is_required_for_product(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->post(route('productos.store'), [
                'sku' => 'SIN-CAT-001',
                'name' => 'Producto sin categoría',
                'description' => null,
                'price' => '100.00',
                'is_active' => true,
            ])
            ->assertSessionHasErrors([
                'category_id' => 'La categoría es obligatoria.',
            ]);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_negative_price_is_rejected(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create();

        $this->actingAs($administrator)
            ->post(route('productos.store'), [
                'category_id' => $category->id,
                'sku' => 'NEG-001',
                'name' => 'Producto inválido',
                'description' => null,
                'price' => '-0.01',
                'is_active' => true,
            ])
            ->assertSessionHasErrors([
                'price' => 'El precio no puede ser negativo.',
            ]);

        $this->assertDatabaseCount('products', 0);
    }

    #[DataProvider('statusTransitions')]
    public function test_product_status_can_be_toggled(bool $initialStatus, bool $expectedStatus): void
    {
        $administrator = $this->administrator();
        $product = Product::factory()->create(['is_active' => $initialStatus]);

        $this->actingAs($administrator)
            ->patch(route('productos.status', $product))
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => $expectedStatus,
        ]);
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function statusTransitions(): array
    {
        return [
            'active to inactive' => [true, false],
            'inactive to active' => [false, true],
        ];
    }

    private function administrator(): User
    {
        $role = Role::factory()->create(['name' => Role::ADMINISTRATOR]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
