<?php

namespace App\Services;

use App\Exceptions\Services\ProductImageStorageException;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Throwable;

class CloudinaryProductImageStorage implements ProductImageStorage
{
    /**
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $image): array
    {
        try {
            $response = $this->client()->uploadApi()->upload($image->getRealPath(), [
                'folder' => 'sigec/producto',
                'resource_type' => 'image',
                'use_filename' => false,
                'unique_filename' => true,
                'overwrite' => false,
            ]);

            $url = $response['secure_url'] ?? null;
            $publicId = $response['public_id'] ?? null;

            if (! is_string($url)
                || parse_url($url, PHP_URL_SCHEME) !== 'https'
                || ! is_string($publicId)
                || $publicId === '') {
                throw new ProductImageStorageException('La respuesta de Cloudinary no contiene una imagen segura.');
            }

            return [
                'url' => $url,
                'public_id' => $publicId,
            ];
        } catch (Throwable $exception) {
            if ($exception instanceof ProductImageStorageException) {
                throw $exception;
            }

            throw new ProductImageStorageException('No se pudo cargar la imagen del producto.', 0, $exception);
        }
    }

    public function delete(string $publicId): void
    {
        try {
            $response = $this->client()->uploadApi()->destroy($publicId, [
                'invalidate' => true,
                'resource_type' => 'image',
            ]);

            if (! in_array($response['result'] ?? null, ['ok', 'not found'], true)) {
                throw new ProductImageStorageException('No se pudo eliminar la imagen del producto.');
            }
        } catch (Throwable $exception) {
            if ($exception instanceof ProductImageStorageException) {
                throw $exception;
            }

            throw new ProductImageStorageException('No se pudo eliminar la imagen del producto.', 0, $exception);
        }
    }

    private function client(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }
}
