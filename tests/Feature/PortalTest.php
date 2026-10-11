<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_shows_available_products_with_a_placeholder_image(): void
    {
        $category = Category::factory()->create(['name' => 'Calzado urbano']);
        $product = Product::factory()->for($category)->create([
            'name' => 'Bota Aurora',
            'price' => '279.00',
        ]);
        Inventory::factory()->for($product)->create(['stock' => 5]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSeeText('Encuentra el par para tu próximo paso.')
            ->assertSeeText($category->name)
            ->assertSeeText($product->name)
            ->assertSeeText('Q 279.00')
            ->assertSee('>BO</span>', false)
            ->assertSee(route('catalogo.index'), false)
            ->assertSee(route('catalogo.show', $product), false)
            ->assertSee(route('carrito.index'), false)
            ->assertSee(route('contacto.create'), false);

        $this->get(route('catalogo.index'))
            ->assertSeeText($product->name);
    }

    public function test_landing_uses_an_existing_cloudinary_photo_for_the_hero_when_available(): void
    {
        $category = Category::factory()->create();
        $productWithPhoto = Product::factory()->for($category)->create([
            'name' => 'Oxford con fotografía',
            'image_url' => 'https://res.cloudinary.com/sigec/image/upload/v1/sigec/producto/oxford.png',
            'image_public_id' => 'sigec/producto/oxford',
        ]);
        Inventory::factory()->for($productWithPhoto)->create(['stock' => 5]);

        $latestProduct = Product::factory()->for($category)->create(['name' => 'Tenis sin fotografía']);
        Inventory::factory()->for($latestProduct)->create(['stock' => 5]);

        $this->get(route('inicio'))
            ->assertSee('src="https://res.cloudinary.com/sigec/image/upload/f_auto,q_auto,w_960,c_fill/v1/sigec/producto/oxford.png"', false)
            ->assertSeeText('Oxford con fotografía');
    }

    public function test_landing_hides_products_that_are_not_available_for_purchase(): void
    {
        $activeCategory = Category::factory()->create(['name' => 'Calzado activo']);
        $inactiveCategory = Category::factory()->create(['name' => 'Calzado oculto', 'is_active' => false]);

        $outOfStockProduct = Product::factory()->for($activeCategory)->create(['name' => 'Bota sin existencias']);
        Inventory::factory()->for($outOfStockProduct)->create(['stock' => 0]);

        $inactiveProduct = Product::factory()->for($activeCategory)->create([
            'name' => 'Tenis inactivo',
            'is_active' => false,
        ]);
        Inventory::factory()->for($inactiveProduct)->create(['stock' => 5]);

        $inactiveCategoryProduct = Product::factory()->for($inactiveCategory)->create(['name' => 'Zapato de categoría oculta']);
        Inventory::factory()->for($inactiveCategoryProduct)->create(['stock' => 5]);

        $this->get(route('inicio'))
            ->assertDontSeeText('Bota sin existencias')
            ->assertDontSeeText('Tenis inactivo')
            ->assertDontSeeText('Zapato de categoría oculta');
    }

    public function test_existing_ecommerce_entry_points_remain_accessible(): void
    {
        $this->get(route('catalogo.index'))->assertOk();
        $this->get(route('carrito.index'))->assertOk();

        $this->get(route('checkout.create'))
            ->assertRedirectToRoute('carrito.index');
    }
}
