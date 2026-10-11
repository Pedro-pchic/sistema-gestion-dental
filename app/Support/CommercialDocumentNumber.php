<?php

namespace App\Support;

use Illuminate\Support\Str;

class CommercialDocumentNumber
{
    public static function invoice(): string
    {
        return self::generate('FAC');
    }

    public static function payment(): string
    {
        return self::generate('REC');
    }

    public static function creditNote(): string
    {
        return self::generate('NDC');
    }

    private static function generate(string $prefix): string
    {
        return $prefix.'-'.Str::upper((string) Str::ulid());
    }
}
