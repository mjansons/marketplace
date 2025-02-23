<?php

namespace App\Service;

class ProductConstants
{
    public static function getConditions(): array
    {
        return [
            "new" => "new",
            "used" => "used",
            "refurbished" => "refurbished"
        ];
    }
}