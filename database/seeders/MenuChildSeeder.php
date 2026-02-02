<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuChildSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu_child = [
            [
                'id_menu_child'     => 1,
                'id_menu_body'      => 4,
                'name'              => 'Users',
                'url'               => '/master-data/users',
            ],
            [
                'id_menu_child'     => 2,
                'id_menu_body'      => 4,
                'name'              => 'User Roles',
                'url'               => '/master-data/user-roles',
            ],
            [
                'id_menu_child'     => 3,
                'id_menu_body'      => 6,
                'name'              => 'Modules',
                'url'               => '/master-data/manajemen-menu/modul',
            ],
            [
                'id_menu_child'     => 4,
                'id_menu_body'      => 6,
                'name'              => 'Items',
                'url'               => '/master-data/manajemen-menu/item',
            ],
            [
                'id_menu_child'     => 5,
                'id_menu_body'      => 6,
                'name'              => 'Access Roles',
                'url'               => '/master-data/manajemen-menu/role-access',
            ],
            [
                'id_menu_child'     => 6,
                'id_menu_body'      => 21,
                'name'              => 'Receives',
                'url'               => '/transaction/bank-receipts',
            ],
            [
                'id_menu_child'     => 7,
                'id_menu_body'      => 21,
                'name'              => 'Expenditures',
                'url'               => '/transaction/bank-expenditures',
            ],
            [
                'id_menu_child'     => 8,
                'id_menu_body'      => 22,
                'name'              => 'Receives',
                'url'               => '/transaction/cash-receipts',
            ],
            [
                'id_menu_child'     => 9,
                'id_menu_body'      => 22,
                'name'              => 'Expenditures',
                'url'               => '/transaction/cash-expenditures',
            ],
            [
                'id_menu_child'     => 10,
                'id_menu_body'      => 23,
                'name'              => 'Receives',
                'url'               => '/transaction/pettycash-receipts',
            ],
            [
                'id_menu_child'     => 11,
                'id_menu_body'      => 19,
                'name'              => 'Expenditures',
                'url'               => '/transaction/pettycash-expenditures',
            ],
        ];

        foreach ($menu_child as $key => $value) {
            DB::table('menu_child')->insert($value);
        }
    }
}
