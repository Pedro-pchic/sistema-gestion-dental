<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

abstract class ProductImageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $image = $this->file('image');

        if (! $image instanceof UploadedFile) {
            return;
        }

        $uploadErrorCode = $image->getError();

        if ($uploadErrorCode === UPLOAD_ERR_OK) {
            return;
        }

        Log::warning('PHP rejected a product image upload.', [
            'diagnostic_id' => 'SIGEC-PHP-UPLOAD-001',
            'upload_error_code' => $uploadErrorCode,
        ]);
    }
}
