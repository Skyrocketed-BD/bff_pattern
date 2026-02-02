<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu_category = [
            [
                'id_menu_category'  => 1,
                'id_menu_module'    => 1,
                'name'              => 'DASHBOARD',
            ],
            [
                'id_menu_category'  => 2,
                'id_menu_module'    => 1,
                'name'              => 'ADMINISTRATOR',
            ],
            [
                'id_menu_category'  => 3,
                'id_menu_module'    => 1,
                'name'              => 'MASTER',
            ],
            [
                'id_menu_category'  => 4,
                'id_menu_module'    => 2,
                'name'              => 'DASHBOARD',
            ],
            [
                'id_menu_category'  => 5,
                'id_menu_module'    => 2,
                'name'              => 'MINING PLAN',
            ],
            [
                'id_menu_category'  => 6,
                'id_menu_module'    => 2,
                'name'              => 'INVENTORY',
            ],
            [
                'id_menu_category'  => 7,
                'id_menu_module'    => 2,
                'name'              => 'REPORT',
            ],
            [
                'id_menu_category'  => 8,
                'id_menu_module'    => 3,
                'name'              => 'DASHBOARD',
            ],
            [
                'id_menu_category'  => 9,
                'id_menu_module'    => 3,
                'name'              => 'MASTER',
            ],
            [
                'id_menu_category'  => 10,
                'id_menu_module'    => 3,
                'name'              => 'ACCOUNTING',
            ],
            [
                'id_menu_category'  => 11,
                'id_menu_module'    => 3,
                'name'              => 'TRANSACTION',
            ],
            [
                'id_menu_category'  => 12,
                'id_menu_module'    => 3,
                'name'              => 'REPORT',
            ],
        ];

        foreach ($menu_category as $key => $value) {
            DB::table('menu_category')->insert($value);
        }
    }
}
