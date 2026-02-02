<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuBodySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu_body = [
            [
                'id_menu_body'      => 1,
                'id_menu_category'  => 1,
                'name'              => 'Admin Dashboard',
                'icon'              => 'LayoutDashboard',
                'url'               => '/dashboard-overview-4',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 2,
                'id_menu_category'  => 8,
                'name'              => 'Accounting Dashboard',
                'icon'              => 'LayoutDashboard',
                'url'               => '/dashboard-overview-5',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 3,
                'id_menu_category'  => 4,
                'name'              => 'Data Entry Dashboard',
                'icon'              => 'LayoutDashboard',
                'url'               => '/dashboard-overview-3',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 4,
                'id_menu_category'  => 2,
                'name'              => 'User Management',
                'icon'              => 'User',
                'url'               => '#',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 5,
                'id_menu_category'  => 3,
                'name'              => 'Contractors',
                'icon'              => 'Boxes',
                'url'               => '/master-data/contractors',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 6,
                'id_menu_category'  => 2,
                'name'              => 'Menu Management',
                'icon'              => 'Rows4',
                'url'               => '#',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 7,
                'id_menu_category'  => 5,
                'name'              => 'Area of Interest',
                'icon'              => 'Map',
                'url'               => '/master-data/area-of-interest',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 8,
                'id_menu_category'  => 5,
                'name'              => 'COG',
                'icon'              => 'Layers3',
                'url'               => '/mining-plan/cog',
                'is_enabled'        => '1',
                'position'          => 2
            ],
            [
                'id_menu_body'      => 9,
                'id_menu_category'  => 5,
                'name'              => 'Dome',
                'icon'              => 'ConciergeBell',
                'url'               => '/mining-plan/dome',
                'is_enabled'        => '1',
                'position'          => 3
            ],
            [
                'id_menu_body'      => 10,
                'id_menu_category'  => 6,
                'name'              => 'Stockpile ETO',
                'icon'              => 'Boxes',
                'url'               => '/inventory/stockpile-eto',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 11,
                'id_menu_category'  => 6,
                'name'              => 'Stockpile EFO',
                'icon'              => 'Boxes',
                'url'               => '/inventory/stockpile-efo',
                'is_enabled'        => '1',
                'position'          => 2
            ],
            [
                'id_menu_body'      => 12,
                'id_menu_category'  => 6,
                'name'              => 'Stockpile Global',
                'icon'              => 'Boxes',
                'url'               => '/inventory/stockpile-global',
                'is_enabled'        => '1',
                'position'          => 3
            ],
            [
                'id_menu_body'      => 13,
                'id_menu_category'  => 9,
                'name'              => 'Banks',
                'icon'              => 'Banknote',
                'url'               => '/master-data/bank',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 14,
                'id_menu_category'  => 9,
                'name'              => 'COA',
                'icon'              => 'CreditCard',
                'url'               => '/master-coa',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 15,
                'id_menu_category'  => 9,
                'name'              => 'Transaction Names',
                'icon'              => 'CreditCard',
                'url'               => '/master-data/transaction-names',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 16,
                'id_menu_category'  => 9,
                'name'              => 'Taxes',
                'icon'              => 'Newspaper',
                'url'               => '/master-data/taxes',
                'is_enabled'        => '0',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 17,
                'id_menu_category'  => 10,
                'name'              => 'Journal',
                'icon'              => 'Sheet',
                'url'               => '/finance/journal',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 18,
                'id_menu_category'  => 10,
                'name'              => 'Journal Interface',
                'icon'              => 'Sheet',
                'url'               => '/finance/journal-interface',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 19,
                'id_menu_category'  => 10,
                'name'              => 'General Ledger',
                'icon'              => 'BookText',
                'url'               => '/finance/general-ledger',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 20,
                'id_menu_category'  => 10,
                'name'              => 'Balance Sheet',
                'icon'              => 'Scale',
                'url'               => '/finance/balance-sheet',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 21,
                'id_menu_category'  => 11,
                'name'              => 'Bank',
                'icon'              => 'Banknote',
                'url'               => '#',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 22,
                'id_menu_category'  => 11,
                'name'              => 'Cash',
                'icon'              => 'CircleDollarSign',
                'url'               => '#',
                'is_enabled'        => '1',
                'position'          => 1
            ],
            [
                'id_menu_body'      => 23,
                'id_menu_category'  => 11,
                'name'              => 'PettyCash',
                'icon'              => 'InspectionPanel',
                'url'               => '#',
                'is_enabled'        => '1',
                'position'          => 1
            ],
        ];

        foreach ($menu_body as $key => $value) {
            DB::table('menu_body')->insert($value);
        }
    }
}
