<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_create_category(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->post(route('categorias.store'), [
                'name' => 'Calzado deportivo',
                'description' => 'Productos para actividades deportivas.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Calzado deportivo',
            'description' => 'Productos para actividades deportivas.',
            'is_active' => true,
        ]);
    }

    public function test_administrator_can_view_category(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create([
            'name' => 'Calzado casual',
        ]);

        $this->actingAs($administrator)
            ->get(route('categorias.show', $category))
            ->assertOk()
            ->assertSeeText('Calzado casual');
    }

    public function test_administrator_can_update_category_without_its_name_conflicting_with_itself(): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create([
            'name' => 'Botas',
            'description' => null,
        ]);

        $this->actingAs($administrator)
            ->put(route('categorias.update', $category), [
                'name' => 'Botas',
                'description' => 'Botas para toda ocasión.',
                'is_active' => true,
            ])
            ->assertRedirectToRoute('categorias.show', $category);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Botas',
            'description' => 'Botas para toda ocasión.',
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $administrator = $this->administrator();
        Category::factory()->create(['name' => 'Sandalias']);

        $this->actingAs($administrator)
            ->post(route('categorias.store'), [
                'name' => 'Sandalias',
                'description' => null,
                'is_active' => true,
            ])
            ->assertSessionHasErrors([
                'name' => 'Ya existe una categoría con este nombre.',
            ]);

        $this->assertDatabaseCount('categories', 1);
    }

    #[DataProvider('statusTransitions')]
    public function test_category_status_can_be_toggled_without_removing_its_products(bool $initialStatus, bool $expectedStatus): void
    {
        $administrator = $this->administrator();
        $category = Category::factory()->create(['is_active' => $initialStatus]);
        $product = Product::factory()->for($category)->create();

        $this->actingAs($administrator)
            ->patch(route('categorias.status', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => $expectedStatus,
        ]);
        $this->assertModelExists($product);
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
