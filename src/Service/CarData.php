<?php

namespace App\Service;

class CarData
{
    public static function getCarBrands(): array
    {
        return [
            'Volkswagen' => 'Volkswagen',
            'Audi'       => 'Audi',
            'Mercedes'   => 'Mercedes',
            'Bmw'        => 'Bmw',
            'Skoda'      => 'Skoda',
        ];
    }

    public static function getVolume(): array
    {
        return [
            '0.1' => 0.1,
            '0.6' => 0.6,
            '0.9' => 0.9,
            '1.2' => 1.2,
            '1.4' => 1.4,
            '1.5' => 1.5,
            '2.0' => 2.0,
            '2.2' => 2.2,
            '3.0' => 3.0,
            '3.2' => 3.2,
            '3.5' => 3.5,
            '4.0' => 4.0,
            '4.2' => 4.2
        ];
    }


    public static function getYear(): array
    {
        $currentYear = (int) date('Y');
        $years = range($currentYear,1938); // ascending

        return array_combine($years, $years);
    }

    public static function getModelsByBrand(string $brand): array
    {
        return match ($brand) {
            'Volkswagen' => ['Golf', 'Passat', 'Tiguan'],
            'Audi'       => ['A1', 'A2', 'A3', 'A4', 'A5', 'A6'],
            'Mercedes'   => ['C-Class', 'E-Class', 'S-Class'],
            'Bmw'        => ['3 Series', '5 Series', '7 Series'],
            'Skoda'      => ['Fabia', 'Octavia', 'Superb'],
            default      => [],
        };
    }
}
