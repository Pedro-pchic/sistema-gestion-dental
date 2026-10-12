<?php

namespace App\Support;

use Illuminate\Support\Str;

class DentalDocumentNumber
{
    public static function invoice(): string
    {
        return 'DFA-'.Str::upper((string) Str::ulid());
    }

    public static function payment(): string
    {
        return 'DREC-'.Str::upper((string) Str::ulid());
    }
}
