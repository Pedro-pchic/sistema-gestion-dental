<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoProductCatalogSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, existing_names: list<string>, description: string}>
     */
    private const CATEGORIES = [
        'casuals' => [
            'name' => 'Casuales y deportivos',
            'existing_names' => ['Casuales y deportivos', 'Zapatos deportivos'],
            'description' => 'Calzado casual y deportivo para uso diario y actividades físicas.',
        ],
        'formal' => [
            'name' => 'Formales',
            'existing_names' => ['Formales', 'Zapatos formales'],
            'description' => 'Calzado elegante para ocasiones formales y profesionales.',
        ],
        'dama' => [
            'name' => 'Dama',
            'existing_names' => ['Dama', 'Zapatos de dama', 'Calzado de dama'],
            'description' => 'Calzado para dama con diseños cómodos y versátiles.',
        ],
        'infantiles' => [
            'name' => 'Infantiles',
            'existing_names' => ['Infantiles', 'Zapatos infantiles', 'Calzado infantil'],
            'description' => 'Calzado infantil para la escuela y las actividades diarias.',
        ],
    ];

    /**
     * @var list<array{category: string, sku: string, name: string, description: string, price: string, stock: int}>
     */
    private const PRODUCTS = [
        [
            'category' => 'casuals',
            'sku' => 'SIGEC-CAS-001',
            'name' => 'Tenis Urban Classic',
            'description' => 'Estilo urbano versátil y cómodo para acompañarte todos los días.',
            'price' => '249.00',
            'stock' => 12,
        ],
        [
            'category' => 'casuals',
            'sku' => 'SIGEC-CAS-002',
            'name' => 'Tenis Street Flex',
            'description' => 'Diseño casual flexible para caminar con comodidad durante el día.',
            'price' => '299.00',
            'stock' => 10,
        ],
        [
            'category' => 'casuals',
            'sku' => 'SIGEC-CAS-003',
            'name' => 'Tenis Running Active',
            'description' => 'Tenis ligero con soporte cómodo para correr y entrenar.',
            'price' => '349.00',
            'stock' => 8,
        ],
        [
            'category' => 'casuals',
            'sku' => 'SIGEC-CAS-004',
            'name' => 'Tenis Sport Motion',
            'description' => 'Calzado deportivo cómodo para entrenamientos y movimiento diario.',
            'price' => '379.00',
            'stock' => 8,
        ],
        [
            'category' => 'casuals',
            'sku' => 'SIGEC-CAS-005',
            'name' => 'Zapato Casual Comfort',
            'description' => 'Zapato casual de acabado sobrio pensado para el uso cotidiano.',
            'price' => '289.00',
            'stock' => 10,
        ],
        [
            'category' => 'formal',
            'sku' => 'SIGEC-FOR-001',
            'name' => 'Oxford Elegance Café',
            'description' => 'Diseño Oxford en tono café para ocasiones especiales y profesionales.',
            'price' => '425.00',
            'stock' => 8,
        ],
        [
            'category' => 'formal',
            'sku' => 'SIGEC-FOR-002',
            'name' => 'Derby Executive Negro',
            'description' => 'Zapato Derby negro de estilo ejecutivo y acabado clásico.',
            'price' => '449.00',
            'stock' => 7,
        ],
        [
            'category' => 'formal',
            'sku' => 'SIGEC-FOR-003',
            'name' => 'Mocasín Classic Brown',
            'description' => 'Mocasín café de diseño clásico para combinar con atuendos formales.',
            'price' => '365.00',
            'stock' => 6,
        ],
        [
            'category' => 'formal',
            'sku' => 'SIGEC-FOR-004',
            'name' => 'Zapato Formal Premium',
            'description' => 'Zapato formal de líneas refinadas para una presentación impecable.',
            'price' => '495.00',
            'stock' => 7,
        ],
        [
            'category' => 'dama',
            'sku' => 'SIGEC-DAM-001',
            'name' => 'Tenis Urban Lady',
            'description' => 'Tenis urbano ligero con estilo versátil para todos los días.',
            'price' => '279.00',
            'stock' => 10,
        ],
        [
            'category' => 'dama',
            'sku' => 'SIGEC-DAM-002',
            'name' => 'Balerina Soft Beige',
            'description' => 'Balerina beige de tacto suave y diseño cómodo para uso diario.',
            'price' => '225.00',
            'stock' => 8,
        ],
        [
            'category' => 'dama',
            'sku' => 'SIGEC-DAM-003',
            'name' => 'Sandalia Summer Comfort',
            'description' => 'Sandalia ligera y cómoda para disfrutar los días cálidos.',
            'price' => '199.00',
            'stock' => 9,
        ],
        [
            'category' => 'dama',
            'sku' => 'SIGEC-DAM-004',
            'name' => 'Botín Elegance',
            'description' => 'Botín de estilo elegante que combina comodidad y versatilidad.',
            'price' => '425.00',
            'stock' => 6,
        ],
        [
            'category' => 'infantiles',
            'sku' => 'SIGEC-INF-001',
            'name' => 'Tenis Kids Adventure',
            'description' => 'Tenis resistente y cómodo para acompañar las aventuras infantiles.',
            'price' => '189.00',
            'stock' => 12,
        ],
        [
            'category' => 'infantiles',
            'sku' => 'SIGEC-INF-002',
            'name' => 'Zapato Escolar Classic',
            'description' => 'Zapato escolar clásico, cómodo y práctico para cada jornada.',
            'price' => '215.00',
            'stock' => 8,
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = $this->resolveCategories();

            foreach (self::PRODUCTS as $productData) {
                $this->createProductIfMissing($productData, $categories[$productData['category']]);
            }
        });
    }

    /**
     * @return array<string, Category>
     */
    private function resolveCategories(): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $key => $categoryData) {
            $existingCategories = Category::query()
                ->whereIn('name', $categoryData['existing_names'])
                ->get()
                ->keyBy('name');

            foreach ($categoryData['existing_names'] as $existingName) {
                if ($existingCategories->has($existingName)) {
                    $categories[$key] = $existingCategories->get($existingName);

                    break;
                }
            }

            $categories[$key] ??= Category::query()->firstOrCreate(
                ['name' => $categoryData['name']],
                [
                    'description' => $categoryData['description'],
                    'is_active' => true,
                ],
            );
        }

        return $categories;
    }

    /**
     * @param  array{category: string, sku: string, name: string, description: string, price: string, stock: int}  $productData
     */
    private function createProductIfMissing(array $productData, Category $category): void
    {
        $product = Product::query()->firstOrCreate(
            ['sku' => $productData['sku']],
            [
                'category_id' => $category->getKey(),
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'is_active' => true,
            ],
        );

        if (! $product->wasRecentlyCreated) {
            return;
        }

        $inventory = $product->inventory()->firstOrCreate([], [
            'stock' => 0,
            'minimum_stock' => 3,
        ]);

        $inventory->recordMovement(
            InventoryMovementType::Entry,
            $productData['stock'],
            'Existencia inicial del catálogo de demostración',
            null,
        );
    }
}
