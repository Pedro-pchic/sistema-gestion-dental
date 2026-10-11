<?php

namespace Tests\Feature\Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoProductCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DemoProductCatalogSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_fifteen_products_with_expected_prices_categories_and_stock(): void
    {
        $sportsCategory = Category::factory()->create(['name' => 'Zapatos deportivos']);
        $formalCategory = Category::factory()->create(['name' => 'Zapatos formales']);

        $this->seed(DemoProductCatalogSeeder::class);

        $damaCategory = Category::query()->where('name', 'Dama')->firstOrFail();
        $childrenCategory = Category::query()->where('name', 'Infantiles')->firstOrFail();
        $expectedProducts = [
            ['SIGEC-CAS-001', 'Tenis Urban Classic', '249.00', $sportsCategory->id],
            ['SIGEC-CAS-002', 'Tenis Street Flex', '299.00', $sportsCategory->id],
            ['SIGEC-CAS-003', 'Tenis Running Active', '349.00', $sportsCategory->id],
            ['SIGEC-CAS-004', 'Tenis Sport Motion', '379.00', $sportsCategory->id],
            ['SIGEC-CAS-005', 'Zapato Casual Comfort', '289.00', $sportsCategory->id],
            ['SIGEC-FOR-001', 'Oxford Elegance Café', '425.00', $formalCategory->id],
            ['SIGEC-FOR-002', 'Derby Executive Negro', '449.00', $formalCategory->id],
            ['SIGEC-FOR-003', 'Mocasín Classic Brown', '365.00', $formalCategory->id],
            ['SIGEC-FOR-004', 'Zapato Formal Premium', '495.00', $formalCategory->id],
            ['SIGEC-DAM-001', 'Tenis Urban Lady', '279.00', $damaCategory->id],
            ['SIGEC-DAM-002', 'Balerina Soft Beige', '225.00', $damaCategory->id],
            ['SIGEC-DAM-003', 'Sandalia Summer Comfort', '199.00', $damaCategory->id],
            ['SIGEC-DAM-004', 'Botín Elegance', '425.00', $damaCategory->id],
            ['SIGEC-INF-001', 'Tenis Kids Adventure', '189.00', $childrenCategory->id],
            ['SIGEC-INF-002', 'Zapato Escolar Classic', '215.00', $childrenCategory->id],
        ];

        foreach ($expectedProducts as [$sku, $name, $price, $categoryId]) {
            $this->assertDatabaseHas('products', [
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'category_id' => $categoryId,
                'is_active' => true,
            ]);
        }

        $this->assertDatabaseCount('products', 15);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('inventories', 15);
        $this->assertSame(15, Product::query()->distinct()->count('sku'));
        $this->assertSame(15, Inventory::query()->where('stock', '>', 0)->count());
        $this->assertSame(15, InventoryMovement::query()
            ->where('type', InventoryMovementType::Entry->value)
            ->where('reason', 'Existencia inicial del catálogo de demostración')
            ->count());

        $urbanClassic = Product::query()->where('sku', 'SIGEC-CAS-001')->firstOrFail();

        $this->assertDatabaseHas('inventories', [
            'product_id' => $urbanClassic->id,
            'stock' => 12,
            'minimum_stock' => 3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $urbanClassic->inventory->id,
            'type' => InventoryMovementType::Entry->value,
            'quantity' => 12,
            'previous_stock' => 0,
            'resulting_stock' => 12,
        ]);
    }

    public function test_repeated_seeding_does_not_duplicate_or_change_existing_products(): void
    {
        $sportsCategory = Category::factory()->create(['name' => 'Zapatos deportivos']);
        $formalCategory = Category::factory()->create(['name' => 'Zapatos formales']);
        $existingRunner = Product::factory()->for($sportsCategory)->create([
            'sku' => 'DEP-001',
            'name' => 'Tenis Runner Pro',
            'price' => '450.00',
        ]);
        $existingOxford = Product::factory()->for($formalCategory)->create([
            'sku' => 'FOR-001',
            'name' => 'Oxford Clásico',
            'price' => '575.00',
        ]);
        $runnerInventory = Inventory::factory()->for($existingRunner)->create(['stock' => 0]);
        $oxfordInventory = Inventory::factory()->for($existingOxford)->create(['stock' => 6]);

        $this->seed(DemoProductCatalogSeeder::class);

        $seededProduct = Product::query()->where('sku', 'SIGEC-CAS-001')->firstOrFail();
        $seededProduct->update([
            'name' => 'Nombre editado por el administrador',
            'price' => '999.00',
        ]);
        $seededProduct->inventory->recordMovement(
            InventoryMovementType::Exit,
            1,
            'Salida de prueba',
            null,
        );

        $this->seed(DemoProductCatalogSeeder::class);

        $this->assertDatabaseCount('products', 17);
        $this->assertDatabaseCount('inventories', 17);
        $this->assertDatabaseCount('inventory_movements', 16);
        $this->assertDatabaseHas('products', [
            'id' => $existingRunner->id,
            'sku' => 'DEP-001',
            'name' => 'Tenis Runner Pro',
            'price' => '450.00',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $existingOxford->id,
            'sku' => 'FOR-001',
            'name' => 'Oxford Clásico',
            'price' => '575.00',
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $runnerInventory->id,
            'stock' => 0,
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $oxfordInventory->id,
            'stock' => 6,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $seededProduct->id,
            'name' => 'Nombre editado por el administrador',
            'price' => '999.00',
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $seededProduct->id,
            'stock' => 11,
        ]);
    }

    public function test_seeded_product_is_visible_on_landing_and_in_the_public_catalog_with_its_default_image(): void
    {
        $sportsCategory = Category::factory()->create(['name' => 'Zapatos deportivos']);

        $this->seed(DemoProductCatalogSeeder::class);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSeeText('Zapato Escolar Classic')
            ->assertSee('>ZA</span>', false);

        $this->get(route('catalogo.index', ['category_id' => $sportsCategory->id]))
            ->assertOk()
            ->assertSeeText('Tenis Urban Classic')
            ->assertSee('>TE</span>', false);
    }

    public function test_seeded_product_is_visible_in_product_administration(): void
    {
        $administrator = User::factory()
            ->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))
            ->create(['is_active' => true]);

        $this->seed(DemoProductCatalogSeeder::class);

        $this->actingAs($administrator)
            ->get(route('productos.index'))
            ->assertOk()
            ->assertSeeText('SIGEC-CAS-001')
            ->assertSeeText('Tenis Urban Classic')
            ->assertSeeText('249.00');
    }
}
