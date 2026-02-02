<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu_module = [
            [
                'id_menu_module' => 1,
                'name'    => 'ADMINISTRATOR',
            ],
            [
                'id_menu_module' => 2,
                'name'    => 'OPERATION',
            ],
            [
                'id_menu_module' => 3,
                'name'    => 'FINANCE',
            ],
        ];

        foreach ($menu_module as $key => $value) {
            DB::table('menu_module')->insert($value);
        }
    }
}
