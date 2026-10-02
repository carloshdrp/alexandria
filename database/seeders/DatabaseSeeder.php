<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(2026);

        $this->call([
            UsuariosSeeder::class,
            InventarioSeeder::class,
            EmprestimosSeeder::class,
        ]);
    }
}
