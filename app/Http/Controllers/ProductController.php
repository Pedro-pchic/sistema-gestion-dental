<?php

namespace App\Http\Controllers;

use App\Exceptions\Services\ProductImageStorageException;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    private const IMAGE_UPLOAD_DIAGNOSTIC_CODE = 'CLOUDINARY_PRODUCT_IMAGE_UPLOAD_FAILED';

    public function index(): View
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request, ProductImageStorage $imageStorage): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data['is_active'] = $data['is_active'] ?? true;
        $uploadedImage = null;

        if ($request->hasFile('image')) {
            try {
                $uploadedImage = $imageStorage->upload($request->file('image'));
            } catch (ProductImageStorageException $exception) {
                Log::warning('Cloudinary product image upload failed.', [
                    ...$this->imageUploadFailureContext($exception),
                ]);

                return back()->withInput()->withErrors([
                    'image' => 'No se pudo cargar la fotografía. Inténtalo de nuevo.',
                ]);
            }

            $data['image_url'] = $uploadedImage['url'];
            $data['image_public_id'] = $uploadedImage['public_id'];
        }

        try {
            $product = DB::transaction(function () use ($data): Product {
                $product = Product::create($data);

                $product->inventory()->firstOrCreate([], [
                    'stock' => 0,
                    'minimum_stock' => 0,
                ]);

                return $product;
            });
        } catch (Throwable $exception) {
            if ($uploadedImage !== null) {
                $this->cleanUpUploadedImage($imageStorage, $uploadedImage['public_id']);

                Log::error('Product creation failed after its image was uploaded.', [
                    'exception_type' => $exception::class,
                    'image_public_id' => $uploadedImage['public_id'],
                ]);

                return back()->withInput()->withErrors([
                    'image' => 'No se pudo guardar el producto. Inténtalo de nuevo.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('productos.show', $product)->with('status', 'Producto creado correctamente.');
    }

    public function show(Product $product): View
    {
        $product->load('category');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('categories', 'product'));
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductImageStorage $imageStorage,
    ): RedirectResponse {
        $data = $request->safe()->except(['image', 'remove_image']);
        $uploadedImage = null;
        $oldPublicId = $product->image_public_id;
        $shouldReplaceOrRemove = false;

        if ($request->hasFile('image')) {
            try {
                $uploadedImage = $imageStorage->upload($request->file('image'));
            } catch (ProductImageStorageException $exception) {
                Log::warning('Cloudinary product image upload failed.', [
                    ...$this->imageUploadFailureContext($exception),
                    'product_id' => $product->id,
                ]);

                return back()->withInput()->withErrors([
                    'image' => 'No se pudo cargar la fotografía. La imagen actual se conservó.',
                ]);
            }

            $data['image_url'] = $uploadedImage['url'];
            $data['image_public_id'] = $uploadedImage['public_id'];
            $shouldReplaceOrRemove = true;
        } elseif ($request->boolean('remove_image')) {
            $data['image_url'] = null;
            $data['image_public_id'] = null;
            $shouldReplaceOrRemove = $oldPublicId !== null;
        }

        try {
            DB::transaction(fn (): bool => $product->update($data));
        } catch (Throwable $exception) {
            if ($uploadedImage !== null) {
                $this->cleanUpUploadedImage($imageStorage, $uploadedImage['public_id']);

                Log::error('Product update failed after its replacement image was uploaded.', [
                    'exception_type' => $exception::class,
                    'product_id' => $product->id,
                    'image_public_id' => $uploadedImage['public_id'],
                ]);

                return back()->withInput()->withErrors([
                    'image' => 'No se pudo guardar el producto. La imagen anterior se conservó.',
                ]);
            }

            throw $exception;
        }

        $cleanupFailed = false;

        if ($shouldReplaceOrRemove
            && $oldPublicId !== null
            && ($uploadedImage === null || $uploadedImage['public_id'] !== $oldPublicId)) {
            try {
                $imageStorage->delete($oldPublicId);
            } catch (Throwable $exception) {
                $cleanupFailed = true;

                Log::warning('Previous Cloudinary product image could not be deleted.', [
                    'exception_type' => $exception::class,
                    'product_id' => $product->id,
                    'image_public_id' => $oldPublicId,
                ]);
            }
        }

        $response = redirect()->route('productos.show', $product)
            ->with('status', 'Producto actualizado correctamente.');

        if ($cleanupFailed) {
            $response->with('warning', 'La fotografía anterior no se pudo eliminar y requiere limpieza en Cloudinary.');
        }

        return $response;
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', 'Estado del producto actualizado.');
    }

    private function cleanUpUploadedImage(ProductImageStorage $imageStorage, string $publicId): void
    {
        try {
            $imageStorage->delete($publicId);
        } catch (Throwable $exception) {
            Log::warning('Orphaned Cloudinary product image could not be deleted.', [
                'exception_type' => $exception::class,
                'image_public_id' => $publicId,
            ]);
        }
    }

    /**
     * @return array{diagnostic_code: string, exception_type: class-string<Throwable>, exception_code: int}
     */
    private function imageUploadFailureContext(ProductImageStorageException $exception): array
    {
        $cause = $exception->getPrevious() ?? $exception;

        return [
            'diagnostic_code' => self::IMAGE_UPLOAD_DIAGNOSTIC_CODE,
            'exception_type' => $cause::class,
            'exception_code' => $cause->getCode(),
        ];
    }
}
