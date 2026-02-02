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
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            MenuModuleSeeder::class,
            MenuCategorySeeder::class,
            MenuBodySeeder::class,
            MenuChildSeeder::class,
            RoleAccessSeeder::class,
            CogSeeder::class,
            CoaClasificationSeeder::class,
            ArrangementsSeeder::class
        ]);
    }
}
