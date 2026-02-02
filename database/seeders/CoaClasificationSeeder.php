<?php

namespace Database\Seeders;

use App\Models\finance\CoaClasification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoaClasificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name'                 => 'Kas & Bank',
                'slug'                 => Str::slug('Kas & Bank'),
                'normal_balance'       => 'D',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Akun Piutang',
                'slug'                 => Str::slug('Akun Piutang'),
                'normal_balance'       => 'D',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Persediaan',
                'slug'                 => Str::slug('Persediaan'),
                'normal_balance'       => 'D',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Aktiva Lancar Lainnya',
                'slug'                 => Str::slug('Aktiva Lancar Lainnya'),
                'normal_balance'       => 'D',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Aktiva Tetap',
                'slug'                 => Str::slug('Aktiva Tetap'),
                'normal_balance'       => 'D',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Aktiva Lainnya',
                'slug'                 => Str::slug('Aktiva Lainnya'),
                'normal_balance'       => 'D',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Akumulasi Penyusutan',
                'slug'                 => Str::slug('Akumulasi Penyusutan'),
                'normal_balance'       => 'K',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Akumulasi Amortisasi',
                'slug'                 => Str::slug('Akumulasi Amortisasi'),
                'normal_balance'       => 'K',
                'group'                => 'harta',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Akun Hutang',
                'slug'                 => Str::slug('Akun Hutang'),
                'normal_balance'       => 'K',
                'group'                => 'utang',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Kewajiban Lancar Lainnya',
                'slug'                 => Str::slug('Kewajiban Lancar Lainnya'),
                'normal_balance'       => 'K',
                'group'                => 'utang',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Kewajiban Jangka Panjang',
                'slug'                 => Str::slug('Kewajiban Jangka Panjang'),
                'normal_balance'       => 'K',
                'group'                => 'utang',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Ekuitas',
                'slug'                 => Str::slug('Ekuitas'),
                'normal_balance'       => 'K',
                'group'                => 'modal',
                'accrual'              => 'accumulated',
            ],
            [
                'name'                 => 'Pendapatan Usaha',
                'slug'                 => Str::slug('Pendapatan Usaha'),
                'normal_balance'       => 'K',
                'group'                => 'pendapatan',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Pendapatan Lainnya',
                'slug'                 => Str::slug('Pendapatan Lainnya'),
                'normal_balance'       => 'K',
                'group'                => 'pendapatan',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Harga Pokok Penjualan',
                'slug'                 => Str::slug('Harga Pokok Penjualan'),
                'normal_balance'       => 'D',
                'group'                => 'beban',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Beban Penjualan',
                'slug'                 => Str::slug('Beban Penjualan'),
                'normal_balance'       => 'D',
                'group'                => 'beban',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Beban Umum dan Administrasi',
                'slug'                 => Str::slug('Beban Umum dan Administrasi'),
                'normal_balance'       => 'D',
                'group'                => 'beban',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Beban Depresiasi dan Amortisasi',
                'slug'                 => Str::slug('Beban Depresiasi dan Amortisasi'),
                'normal_balance'       => 'D',
                'group'                => 'beban',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Pendapatan Dari Luar Usaha',
                'slug'                 => Str::slug('Pendapatan Dari Luar Usaha'),
                'normal_balance'       => 'K',
                'group'                => 'pendapatan',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Beban Dari Luar Usaha',
                'slug'                 => Str::slug('Beban Dari Luar Usaha'),
                'normal_balance'       => 'D',
                'group'                => 'beban',
                'accrual'              => 'closed',
            ],
            [
                'name'                 => 'Prive/Dividen',
                'slug'                 => Str::slug('Prive/Dividen'),
                'normal_balance'       => 'D',
                'group'                => 'prive',
                'accrual'              => 'closed',
            ],
        ];

        foreach ($data as $key => $value) {
            CoaClasification::insert($value);
        }
    }
}
