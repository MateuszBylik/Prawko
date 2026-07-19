<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Uwaga: świadomie NIE używamy WithoutModelEvents — dzięki temu obserwator
     * User (utworzenie portfela + bonus powitalny) działa także podczas seedowania.
     */
    public function run(): void
    {
        // Uwaga: pytania NIE pochodzą już z QuestionSeeder (50 ręcznych) — realną
        // bazę (~3,5 tys. pytań) wgrywa komenda `php artisan prawko:import-questions`
        // z oficjalnego pliku XLSX. DemoSeeder losuje sesje z zaimportowanych pytań,
        // więc uruchamiaj go PO imporcie (albo przez `migrate:fresh` + import + seed).
        $this->call([
            DrivingCategorySeeder::class,
            DemoSeeder::class,
        ]);
    }
}
