<?php

namespace Database\Seeders;

use App\Models\DrivingCategory;
use Illuminate\Database\Seeder;

class DrivingCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'AM', 'name' => 'Motorower, czterokołowiec lekki'],
            ['code' => 'A1', 'name' => 'Motocykle do 125 cm³'],
            ['code' => 'A2', 'name' => 'Motocykle o mocy do 35 kW'],
            ['code' => 'A', 'name' => 'Motocykle'],
            ['code' => 'B1', 'name' => 'Czterokołowce'],
            ['code' => 'B', 'name' => 'Samochody osobowe do 3,5 t'],
            ['code' => 'C1', 'name' => 'Samochody ciężarowe 3,5–7,5 t'],
            ['code' => 'C', 'name' => 'Samochody ciężarowe powyżej 3,5 t'],
            ['code' => 'D1', 'name' => 'Autobusy do 17 osób'],
            ['code' => 'D', 'name' => 'Autobusy'],
            ['code' => 'T', 'name' => 'Ciągnik rolniczy'],
            ['code' => 'PT', 'name' => 'Tramwaj'],
            ['code' => 'BE', 'name' => 'Samochód osobowy z przyczepą'],
        ];

        foreach ($categories as $i => $category) {
            DrivingCategory::updateOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name'], 'sort_order' => $i, 'is_active' => true],
            );
        }
    }
}
