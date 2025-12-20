<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Aqui chamamos nossos seeders específicos
        $this->call([
            EmailTemplateSeeder::class,
            // AdminUserSeeder::class, // Se quiser criar um admin padrão depois
        ]);
    }
}