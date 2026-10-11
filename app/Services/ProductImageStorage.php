<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

interface ProductImageStorage
{
    /**
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $image): array;

    public function delete(string $publicId): void;
}
