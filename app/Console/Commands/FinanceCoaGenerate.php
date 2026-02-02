<?php

namespace App\Console\Commands;

use App\Models\finance\Coa;
use App\Models\finance\CoaBody;
use App\Models\finance\CoaHead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class FinanceCoaGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:coa-seeder {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ini merupakan command untuk generate coa finance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        $this->info("Truncate Coa Seeder");

        Schema::connection('finance')->disableForeignKeyConstraints();

        if (CoaHead::exists()) {
            CoaHead::truncate();
        }

        if (CoaBody::exists()) {
            CoaBody::truncate();
        }

        if (Coa::exists()) {
            Coa::truncate();
        }

        Schema::connection('finance')->enableForeignKeyConstraints();

        $this->info("Generate Coa Seeder for {$type}");
        if ($type == 'iup') {
            $this->iup();
        } else if ($type == 'iujp') {
            $this->iujp();
        }

        $this->info("Generate Coa Seeder for {$type} Success");
    }

    protected function iup()
    {
        $coa_head = [
            [
                'id_coa_head' => 1,
                'name'        => 'ASET LANCAR',
                'coa'         => 110000,
            ],
            [
                'id_coa_head' => 2,
                'name'        => 'ASET TETAP',
                'coa'         => 120000,
            ],
            [
                'id_coa_head' => 3,
                'name'        => 'ASET TAK BERWUJUD',
                'coa'         => 130000,
            ],
            [
                'id_coa_head' => 4,
                'name'        => 'LIABILITAS DAN EKUITAS',
                'coa'         => 210000,
            ],
            [
                'id_coa_head' => 5,
                'name'        => 'PENDAPATAN',
                'coa'         => 310000,
            ],
            [
                'id_coa_head' => 6,
                'name'        => 'BIAYA-BIAYA',
                'coa'         => 410000,
            ],
            [
                'id_coa_head' => 7,
                'name'        => 'PENDAPATAN DAN BIAYA LAIN-LAIN',
                'coa'         => 510000,
            ],
        ];

        $coa_body = [
            [
                'id_coa_body' => 1,
                'id_coa_head' => 1,
                'name'        => 'KAS & BANK',
                'coa'         => 110100,
            ],
            [
                'id_coa_body' => 2,
                'id_coa_head' => 1,
                'name'        => 'INVESTASI JANGKA PENDEK',
                'coa'         => 110200,
            ],
            [
                'id_coa_body' => 3,
                'id_coa_head' => 1,
                'name'        => 'PIUTANG USAHA',
                'coa'         => 110300,
            ],
            [
                'id_coa_body' => 4,
                'id_coa_head' => 1,
                'name'        => 'PIUTANG USAHA LAINNYA',
                'coa'         => 110400,
            ],
            [
                'id_coa_body' => 5,
                'id_coa_head' => 1,
                'name'        => 'PERSEDIAAN',
                'coa'         => 110500,
            ],
            [
                'id_coa_body' => 6,
                'id_coa_head' => 1,
                'name'        => 'PERSEDIAAN SUPPLIES',
                'coa'         => 110600,
            ],
            [
                'id_coa_body' => 7,
                'id_coa_head' => 1,
                'name'        => 'BIAYA DIBAYAR DIMUKA',
                'coa'         => 110700,
            ],
            [
                'id_coa_body' => 8,
                'id_coa_head' => 1,
                'name'        => 'PAJAK DIBAYAR DIMUKA',
                'coa'         => 110800,
            ],
            [
                'id_coa_body' => 9,
                'id_coa_head' => 2,
                'name'        => 'ASET TETAP',
                'coa'         => 120100,
            ],
            [
                'id_coa_body' => 10,
                'id_coa_head' => 2,
                'name'        => 'AKUMULASI DEPRESIASI ASET TETAP',
                'coa'         => 120200,
            ],
            [
                'id_coa_body' => 11,
                'id_coa_head' => 3,
                'name'        => 'HAK PENGELOLAAN LAHAN',
                'coa'         => 130100,
            ],
            [
                'id_coa_body' => 12,
                'id_coa_head' => 3,
                'name'        => 'ASET LAINNYA',
                'coa'         => 130200,
            ],
            [
                'id_coa_body' => 13,
                'id_coa_head' => 4,
                'name'        => 'UTANG USAHA',
                'coa'         => 210100,
            ],
            [
                'id_coa_body' => 14,
                'id_coa_head' => 4,
                'name'        => 'UTANG USAHA??',
                'coa'         => 210200,
            ],
            [
                'id_coa_body' => 15,
                'id_coa_head' => 4,
                'name'        => 'BIAYA YANG MASIH HARUS DIBAYAR',
                'coa'         => 210300,
            ],
            [
                'id_coa_body' => 16,
                'id_coa_head' => 4,
                'name'        => 'UTANG LAINNYA',
                'coa'         => 210400,
            ],
            [
                'id_coa_body' => 17,
                'id_coa_head' => 4,
                'name'        => 'UTANG JANGKA PANJANG',
                'coa'         => 210500,
            ],
            [
                'id_coa_body' => 18,
                'id_coa_head' => 4,
                'name'        => 'UTANG JANGKA PANJANG??',
                'coa'         => 210600,
            ],
            [
                'id_coa_body' => 19,
                'id_coa_head' => 4,
                'name'        => 'EKUITAS',
                'coa'         => 210700,
            ],
            [
                'id_coa_body' => 20,
                'id_coa_head' => 5,
                'name'        => 'PENDAPATAN USAHA',
                'coa'         => 310100,
            ],
            [
                'id_coa_body' => 21,
                'id_coa_head' => 6,
                'name'        => 'HPP',
                'coa'         => 410100,
            ],
            [
                'id_coa_body' => 22,
                'id_coa_head' => 6,
                'name'        => 'BIAYA PENJUALAN',
                'coa'         => 410200,
            ],
            [
                'id_coa_body' => 23,
                'id_coa_head' => 6,
                'name'        => 'BEBAN UMUM & ADMINISTRASI',
                'coa'         => 410300,
            ],
            [
                'id_coa_body' => 24,
                'id_coa_head' => 7,
                'name'        => 'PENDAPATAN LAIN-LAIN',
                'coa'         => 510100,
            ],
            [
                'id_coa_body' => 25,
                'id_coa_head' => 7,
                'name'        => 'BIAYA LAIN-LAIN',
                'coa'         => 510200,
            ],
        ];

        $coa = [
            [
                'id_coa'      => 1,
                'id_coa_body' => 1,
                'name'        => 'Kas Kecil',
                'coa'         => 110101,
            ],
            [
                'id_coa'      => 2,
                'id_coa_body' => 1,
                'name'        => 'Kas Setara Kas Lainnya',
                'coa'         => 110102,
            ],
            [
                'id_coa'      => 3,
                'id_coa_body' => 1,
                'name'        => 'Bank Mandiri ',
                'coa'         => 110103,
            ],
            [
                'id_coa'      => 4,
                'id_coa_body' => 1,
                'name'        => 'Bank Bank Central Asia',
                'coa'         => 110104,
            ],
            [
                'id_coa'      => 5,
                'id_coa_body' => 1,
                'name'        => 'Bank Rakyat Indonesia',
                'coa'         => 110105,
            ],
            [
                'id_coa'      => 6,
                'id_coa_body' => 1,
                'name'        => 'Bank Negara Indonesia',
                'coa'         => 110106,
            ],
            [
                'id_coa'      => 7,
                'id_coa_body' => 1,
                'name'        => 'Bank Syariah Indonesia',
                'coa'         => 110107,
            ],
            [
                'id_coa'      => 8,
                'id_coa_body' => 2,
                'name'        => 'Deposito Bank',
                'coa'         => 110201,
            ],
            [
                'id_coa'      => 9,
                'id_coa_body' => 3,
                'name'        => 'Piutang Usaha',
                'coa'         => 110301,
            ],
            [
                'id_coa'      => 10,
                'id_coa_body' => 3,
                'name'        => 'Uang Muka Pembelian',
                'coa'         => 110302,
            ],
            [
                'id_coa'      => 11,
                'id_coa_body' => 4,
                'name'        => 'Piutang Pihak Berelasi',
                'coa'         => 110401,
            ],
            [
                'id_coa'      => 12,
                'id_coa_body' => 4,
                'name'        => 'Piutang Pemegang Saham',
                'coa'         => 110402,
            ],
            [
                'id_coa'      => 13,
                'id_coa_body' => 4,
                'name'        => 'Piutang Lain-Lain',
                'coa'         => 110403,
            ],
            [
                'id_coa'      => 14,
                'id_coa_body' => 5,
                'name'        => 'Persediaan Ore',
                'coa'         => 110501,
            ],
            [
                'id_coa'      => 15,
                'id_coa_body' => 6,
                'name'        => 'Persediaan Solar',
                'coa'         => 110601,
            ],
            [
                'id_coa'      => 16,
                'id_coa_body' => 6,
                'name'        => 'Persediaan Lainnya',
                'coa'         => 110602,
            ],
            [
                'id_coa'      => 17,
                'id_coa_body' => 7,
                'name'        => 'Uang Muka Biaya',
                'coa'         => 110701,
            ],
            [
                'id_coa'      => 18,
                'id_coa_body' => 7,
                'name'        => 'Sewa Dibayar Dimuka',
                'coa'         => 110702,
            ],
            [
                'id_coa'      => 19,
                'id_coa_body' => 8,
                'name'        => 'Pajak Dibayar Dimuka - PPh 22',
                'coa'         => 110801,
            ],
            [
                'id_coa'      => 20,
                'id_coa_body' => 8,
                'name'        => 'Pajak Dibayar Dimuka - PPh 23',
                'coa'         => 110802,
            ],
            [
                'id_coa'      => 21,
                'id_coa_body' => 8,
                'name'        => 'PPN Masukan',
                'coa'         => 110803,
            ],
            [
                'id_coa'      => 22,
                'id_coa_body' => 9,
                'name'        => 'Tanah',
                'coa'         => 120101,
            ],
            [
                'id_coa'      => 23,
                'id_coa_body' => 9,
                'name'        => 'Bangunan & Infrastruktur',
                'coa'         => 120102,
            ],
            [
                'id_coa'      => 24,
                'id_coa_body' => 9,
                'name'        => 'Hak Atas Tanah ',
                'coa'         => 120103,
            ],
            [
                'id_coa'      => 25,
                'id_coa_body' => 9,
                'name'        => 'Penyimpanan Ore',
                'coa'         => 120104,
            ],
            [
                'id_coa'      => 26,
                'id_coa_body' => 9,
                'name'        => 'Jalan ',
                'coa'         => 120105,
            ],
            [
                'id_coa'      => 27,
                'id_coa_body' => 9,
                'name'        => 'Dermaga/Jeti',
                'coa'         => 120106,
            ],
            [
                'id_coa'      => 28,
                'id_coa_body' => 9,
                'name'        => 'Mesin & Peralatan',
                'coa'         => 120107,
            ],
            [
                'id_coa'      => 29,
                'id_coa_body' => 9,
                'name'        => 'Kendaraan',
                'coa'         => 120108,
            ],
            [
                'id_coa'      => 30,
                'id_coa_body' => 9,
                'name'        => 'Inventaris Kantor',
                'coa'         => 120109,
            ],
            [
                'id_coa'      => 31,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Tanah',
                'coa'         => 120201,
            ],
            [
                'id_coa'      => 32,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Bangunan & Infrastruktur',
                'coa'         => 120202,
            ],
            [
                'id_coa'      => 33,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Hak Atas Tanah',
                'coa'         => 120203,
            ],
            [
                'id_coa'      => 34,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Penyimpanan Ore',
                'coa'         => 120204,
            ],
            [
                'id_coa'      => 35,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Dermaga/Jeti',
                'coa'         => 120205,
            ],
            [
                'id_coa'      => 36,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Mesin & Peralatan',
                'coa'         => 120206,
            ],
            [
                'id_coa'      => 37,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Kendaraan',
                'coa'         => 120207,
            ],
            [
                'id_coa'      => 38,
                'id_coa_body' => 10,
                'name'        => 'Akumulasi Penyusutan Inventaris Kantor',
                'coa'         => 120208,
            ],
            [
                'id_coa'      => 39,
                'id_coa_body' => 11,
                'name'        => 'Hak Pengelolaan Lahan',
                'coa'         => 130101,
            ],
            [
                'id_coa'      => 40,
                'id_coa_body' => 11,
                'name'        => 'Akumulasi Amortisasi Hak Pengelolaan Lahan',
                'coa'         => 130102,
            ],
            [
                'id_coa'      => 41,
                'id_coa_body' => 12,
                'name'        => 'Izin Usaha Pertambangan (IUP) & Izin Lingkungan',
                'coa'         => 130201,
            ],
            [
                'id_coa'      => 42,
                'id_coa_body' => 12,
                'name'        => 'Akumulasi Amortisasi ',
                'coa'         => 130202,
            ],
            [
                'id_coa'      => 43,
                'id_coa_body' => 13,
                'name'        => 'Utang Usaha',
                'coa'         => 210101,
            ],
            [
                'id_coa'      => 44,
                'id_coa_body' => 13,
                'name'        => 'Utang Pihak Berelasi',
                'coa'         => 210102,
            ],
            [
                'id_coa'      => 45,
                'id_coa_body' => 13,
                'name'        => 'Uang Muka Penjualan',
                'coa'         => 210103,
            ],
            [
                'id_coa'      => 46,
                'id_coa_body' => 14,
                'name'        => 'Utang PPh Pasal 15',
                'coa'         => 210201,
            ],
            [
                'id_coa'      => 47,
                'id_coa_body' => 14,
                'name'        => 'Utang PPh Pasal 21',
                'coa'         => 210202,
            ],
            [
                'id_coa'      => 48,
                'id_coa_body' => 14,
                'name'        => 'Utang PPh Pasal 23',
                'coa'         => 210203,
            ],
            [
                'id_coa'      => 49,
                'id_coa_body' => 14,
                'name'        => 'Utang PPh Pasal 25',
                'coa'         => 210204,
            ],
            [
                'id_coa'      => 50,
                'id_coa_body' => 14,
                'name'        => 'Utang PPh Pasal 29',
                'coa'         => 210205,
            ],
            [
                'id_coa'      => 51,
                'id_coa_body' => 14,
                'name'        => 'Utang PPh Pasal 4 (2)',
                'coa'         => 210206,
            ],
            [
                'id_coa'      => 52,
                'id_coa_body' => 14,
                'name'        => 'PPN Keluaran',
                'coa'         => 210207,
            ],
            [
                'id_coa'      => 53,
                'id_coa_body' => 15,
                'name'        => 'Biaya Gaji',
                'coa'         => 210301,
            ],
            [
                'id_coa'      => 54,
                'id_coa_body' => 15,
                'name'        => 'Iuran BPJS',
                'coa'         => 210302,
            ],
            [
                'id_coa'      => 55,
                'id_coa_body' => 15,
                'name'        => 'Biaya Listrik',
                'coa'         => 210303,
            ],
            [
                'id_coa'      => 56,
                'id_coa_body' => 15,
                'name'        => 'Beban Lainnya',
                'coa'         => 210304,
            ],
            [
                'id_coa'      => 57,
                'id_coa_body' => 16,
                'name'        => 'Utang (Dana Operasional Site)',
                'coa'         => 210401,
            ],
            [
                'id_coa'      => 58,
                'id_coa_body' => 16,
                'name'        => 'Utang Lainnya Pihak Ketiga',
                'coa'         => 210402,
            ],
            [
                'id_coa'      => 59,
                'id_coa_body' => 16,
                'name'        => 'Uang Muka Penjualan Lainnya',
                'coa'         => 210403,
            ],
            [
                'id_coa'      => 60,
                'id_coa_body' => 17,
                'name'        => 'Utang Sewa',
                'coa'         => 210501,
            ],
            [
                'id_coa'      => 61,
                'id_coa_body' => 17,
                'name'        => 'Utang Pembelian Aset ',
                'coa'         => 210502,
            ],
            [
                'id_coa'      => 62,
                'id_coa_body' => 17,
                'name'        => 'Utang Bank ',
                'coa'         => 210503,
            ],
            [
                'id_coa'      => 63,
                'id_coa_body' => 17,
                'name'        => 'Utang Pihak Berelasi',
                'coa'         => 210504,
            ],
            [
                'id_coa'      => 64,
                'id_coa_body' => 18,
                'name'        => 'Pinjaman Dana ',
                'coa'         => 210601,
            ],
            [
                'id_coa'      => 65,
                'id_coa_body' => 18,
                'name'        => 'Operasional ',
                'coa'         => 210602,
            ],
            [
                'id_coa'      => 66,
                'id_coa_body' => 18,
                'name'        => 'Utang Lainnya',
                'coa'         => 210603,
            ],
            [
                'id_coa'      => 67,
                'id_coa_body' => 19,
                'name'        => 'Modal Saham',
                'coa'         => 210701,
            ],
            [
                'id_coa'      => 68,
                'id_coa_body' => 19,
                'name'        => 'Tambahan Modal Disetor',
                'coa'         => 210702,
            ],
            [
                'id_coa'      => 69,
                'id_coa_body' => 19,
                'name'        => 'Laba Ditahan',
                'coa'         => 210703,
            ],
            [
                'id_coa'      => 70,
                'id_coa_body' => 19,
                'name'        => 'Laba (Rugi) Tahun Berjalan',
                'coa'         => 210704,
            ],
            [
                'id_coa'      => 71,
                'id_coa_body' => 20,
                'name'        => 'Penjualan Ore',
                'coa'         => 310101,
            ],
            [
                'id_coa'      => 72,
                'id_coa_body' => 21,
                'name'        => 'Biaya Jasa Kontraktor',
                'coa'         => 410101,
            ],
            [
                'id_coa'      => 73,
                'id_coa_body' => 21,
                'name'        => 'Biaya Barging ',
                'coa'         => 410102,
            ],
            [
                'id_coa'      => 74,
                'id_coa_body' => 21,
                'name'        => 'Biaya Land Rent',
                'coa'         => 410103,
            ],
            [
                'id_coa'      => 75,
                'id_coa_body' => 21,
                'name'        => 'Gaji Tenaga Kerja Langsung',
                'coa'         => 410104,
            ],
            [
                'id_coa'      => 76,
                'id_coa_body' => 21,
                'name'        => 'Biaya Solar Produksi',
                'coa'         => 410105,
            ],
            [
                'id_coa'      => 77,
                'id_coa_body' => 21,
                'name'        => 'Biaya Sewa Alat berat',
                'coa'         => 410106,
            ],
            [
                'id_coa'      => 78,
                'id_coa_body' => 21,
                'name'        => 'Biaya PNBP Tambang',
                'coa'         => 410107,
            ],
            [
                'id_coa'      => 79,
                'id_coa_body' => 21,
                'name'        => 'Biaya IPPKH Tambang',
                'coa'         => 410108,
            ],
            [
                'id_coa'      => 80,
                'id_coa_body' => 21,
                'name'        => 'Biaya Outsourching Pengamanan ',
                'coa'         => 410109,
            ],
            [
                'id_coa'      => 81,
                'id_coa_body' => 21,
                'name'        => 'Biaya Operasional Tambang',
                'coa'         => 410110,
            ],
            [
                'id_coa'      => 82,
                'id_coa_body' => 21,
                'name'        => 'Beban Penyusutan Tanah',
                'coa'         => 410111,
            ],
            [
                'id_coa'      => 83,
                'id_coa_body' => 21,
                'name'        => 'Beban Penyusutan Bangunan & Infrastruktur',
                'coa'         => 410112,
            ],
            [
                'id_coa'      => 84,
                'id_coa_body' => 21,
                'name'        => 'Beban Penyusutan Hak Atas Tanah',
                'coa'         => 410113,
            ],
            [
                'id_coa'      => 85,
                'id_coa_body' => 21,
                'name'        => 'Beban Penyusutan Penyimpanan Ore',
                'coa'         => 410114,
            ],
            [
                'id_coa'      => 86,
                'id_coa_body' => 21,
                'name'        => 'Beban Penyusutan Dermaga/Jeti',
                'coa'         => 410115,
            ],
            [
                'id_coa'      => 87,
                'id_coa_body' => 21,
                'name'        => 'Beban Amortisasi - Pengelolahan Lahan',
                'coa'         => 410116,
            ],
            [
                'id_coa'      => 88,
                'id_coa_body' => 21,
                'name'        => 'Beban Amortisasi - IUP',
                'coa'         => 410117,
            ],
            [
                'id_coa'      => 89,
                'id_coa_body' => 22,
                'name'        => 'Biaya Sewa Kendaraan',
                'coa'         => 410201,
            ],
            [
                'id_coa'      => 90,
                'id_coa_body' => 22,
                'name'        => 'Biaya Perjalanan Dinas',
                'coa'         => 410202,
            ],
            [
                'id_coa'      => 91,
                'id_coa_body' => 22,
                'name'        => 'Biaya Perizinan & Pengurusan Dokumen',
                'coa'         => 410203,
            ],
            [
                'id_coa'      => 92,
                'id_coa_body' => 22,
                'name'        => 'Biaya Laboratorium/Analisis (Certificate of Analysis)',
                'coa'         => 410204,
            ],
            [
                'id_coa'      => 93,
                'id_coa_body' => 22,
                'name'        => 'Biaya Angkut - Tongkang',
                'coa'         => 410205,
            ],
            [
                'id_coa'      => 94,
                'id_coa_body' => 22,
                'name'        => 'Biaya PBM (Bongkar Muat)',
                'coa'         => 410206,
            ],
            [
                'id_coa'      => 95,
                'id_coa_body' => 22,
                'name'        => 'Biaya Kelebihan Waktu Berlabuh (Demmurage)',
                'coa'         => 410207,
            ],
            [
                'id_coa'      => 96,
                'id_coa_body' => 22,
                'name'        => 'Biaya Loading Master',
                'coa'         => 410208,
            ],
            [
                'id_coa'      => 97,
                'id_coa_body' => 22,
                'name'        => 'Biaya Sewa Peralatan',
                'coa'         => 410209,
            ],
            [
                'id_coa'      => 98,
                'id_coa_body' => 22,
                'name'        => 'Biaya Sewa Kantor dan Mess',
                'coa'         => 410210,
            ],
            [
                'id_coa'      => 99,
                'id_coa_body' => 22,
                'name'        => 'Biaya Logistik dan Kebutuhan Mess',
                'coa'         => 410211,
            ],
            [
                'id_coa'      => 100,
                'id_coa_body' => 22,
                'name'        => 'Biaya Surveyor',
                'coa'         => 410212,
            ],
            [
                'id_coa'      => 101,
                'id_coa_body' => 22,
                'name'        => 'Biaya Pemeliharaan Peralatan Tambang',
                'coa'         => 410213,
            ],
            [
                'id_coa'      => 102,
                'id_coa_body' => 22,
                'name'        => 'Biaya Pemeliharaan Kendaraan',
                'coa'         => 410214,
            ],
            [
                'id_coa'      => 103,
                'id_coa_body' => 22,
                'name'        => 'Biaya Pemeliharaan Bangunan/Infrastruktur',
                'coa'         => 410215,
            ],
            [
                'id_coa'      => 104,
                'id_coa_body' => 22,
                'name'        => 'Biaya Perlengkapan Site',
                'coa'         => 410216,
            ],
            [
                'id_coa'      => 105,
                'id_coa_body' => 22,
                'name'        => 'Biaya Entertainment',
                'coa'         => 410217,
            ],
            [
                'id_coa'      => 106,
                'id_coa_body' => 22,
                'name'        => 'Biaya Perlengkapan K3/HSE',
                'coa'         => 410218,
            ],
            [
                'id_coa'      => 107,
                'id_coa_body' => 22,
                'name'        => 'Biaya CSR',
                'coa'         => 410219,
            ],
            [
                'id_coa'      => 108,
                'id_coa_body' => 22,
                'name'        => 'Biaya Potongan Penalty',
                'coa'         => 410220,
            ],
            [
                'id_coa'      => 109,
                'id_coa_body' => 22,
                'name'        => 'Biaya Konsumsi Site',
                'coa'         => 410221,
            ],
            [
                'id_coa'      => 110,
                'id_coa_body' => 22,
                'name'        => 'Beban Penyusutan Mesin & Peralatan',
                'coa'         => 410222,
            ],
            [
                'id_coa'      => 111,
                'id_coa_body' => 22,
                'name'        => 'Beban Penyusutan Kendaraan',
                'coa'         => 410223,
            ],
            [
                'id_coa'      => 112,
                'id_coa_body' => 23,
                'name'        => 'Biaya Gaji Karyawan & Direksi',
                'coa'         => 410301,
            ],
            [
                'id_coa'      => 113,
                'id_coa_body' => 23,
                'name'        => 'Biaya BPJS ',
                'coa'         => 410302,
            ],
            [
                'id_coa'      => 114,
                'id_coa_body' => 23,
                'name'        => 'Biaya Sumbangan',
                'coa'         => 410303,
            ],
            [
                'id_coa'      => 115,
                'id_coa_body' => 23,
                'name'        => 'Biaya Solar Operasional Kantor',
                'coa'         => 410304,
            ],
            [
                'id_coa'      => 116,
                'id_coa_body' => 23,
                'name'        => 'Biaya Perlengkapan Kantor',
                'coa'         => 410305,
            ],
            [
                'id_coa'      => 117,
                'id_coa_body' => 23,
                'name'        => 'Biaya Pajak PBB',
                'coa'         => 410306,
            ],
            [
                'id_coa'      => 118,
                'id_coa_body' => 23,
                'name'        => 'Biaya Toll, & Parkir',
                'coa'         => 410307,
            ],
            [
                'id_coa'      => 119,
                'id_coa_body' => 23,
                'name'        => 'Biaya Jasa Profesional',
                'coa'         => 410308,
            ],
            [
                'id_coa'      => 120,
                'id_coa_body' => 23,
                'name'        => 'Biaya Pengiriman, Materai, dan Pos',
                'coa'         => 410309,
            ],
            [
                'id_coa'      => 121,
                'id_coa_body' => 23,
                'name'        => 'Beban Penyusutan Inventaris Kantor',
                'coa'         => 410310,
            ],
            [
                'id_coa'      => 122,
                'id_coa_body' => 23,
                'name'        => 'Biaya Listrik, Air & Telepon',
                'coa'         => 410311,
            ],
            [
                'id_coa'      => 123,
                'id_coa_body' => 23,
                'name'        => 'Biaya Entertainment',
                'coa'         => 410312,
            ],
            [
                'id_coa'      => 124,
                'id_coa_body' => 23,
                'name'        => 'Biaya Perjalanan Dinas',
                'coa'         => 410313,
            ],
            [
                'id_coa'      => 125,
                'id_coa_body' => 23,
                'name'        => 'Beban Pajak (STNK, KIR, & Pajak Kendaraan Lainnya)',
                'coa'         => 410314,
            ],
            [
                'id_coa'      => 126,
                'id_coa_body' => 23,
                'name'        => 'Biaya Sewa Tanah & Bangunan',
                'coa'         => 410315,
            ],
            [
                'id_coa'      => 127,
                'id_coa_body' => 23,
                'name'        => 'Biaya Cetak, Fotokopi, RKAP, dan Dokumen Lainnya',
                'coa'         => 410316,
            ],
            [
                'id_coa'      => 128,
                'id_coa_body' => 23,
                'name'        => 'Biaya Pendidikan dan Pelatihan',
                'coa'         => 410317,
            ],
            [
                'id_coa'      => 129,
                'id_coa_body' => 23,
                'name'        => 'Biaya Pemeliharaan Kantor ',
                'coa'         => 410318,
            ],
            [
                'id_coa'      => 130,
                'id_coa_body' => 23,
                'name'        => 'Biaya Perjalanan Dinas',
                'coa'         => 410319,
            ],
            [
                'id_coa'      => 131,
                'id_coa_body' => 23,
                'name'        => 'Biaya Retribusi',
                'coa'         => 410320,
            ],
            [
                'id_coa'      => 132,
                'id_coa_body' => 23,
                'name'        => 'Biaya Konsumsi Kantor',
                'coa'         => 410321,
            ],
            [
                'id_coa'      => 133,
                'id_coa_body' => 23,
                'name'        => 'Biaya Lainnya',
                'coa'         => 410322,
            ],
            [
                'id_coa'      => 134,
                'id_coa_body' => 24,
                'name'        => 'Pendapatan Jasa Giro',
                'coa'         => 510101,
            ],
            [
                'id_coa'      => 135,
                'id_coa_body' => 24,
                'name'        => 'Pendapatan Lainnya',
                'coa'         => 510102,
            ],
            [
                'id_coa'      => 136,
                'id_coa_body' => 25,
                'name'        => 'Biaya Bunga Pinjaman',
                'coa'         => 510201,
            ],
            [
                'id_coa'      => 137,
                'id_coa_body' => 25,
                'name'        => 'Biaya Bunga Bank',
                'coa'         => 510202,
            ],
            [
                'id_coa'      => 138,
                'id_coa_body' => 25,
                'name'        => 'Biaya Administrasi Bank',
                'coa'         => 510203,
            ],
            [
                'id_coa'      => 139,
                'id_coa_body' => 25,
                'name'        => 'Biaya Lainnya',
                'coa'         => 510204,
            ],
        ];

        CoaHead::insert($coa_head);
        CoaBody::insert($coa_body);
        Coa::insert($coa);
    }

    protected function iujp()
    {
        $coa_head = [
            [
                'name' => 'Assets',
                'coa'  => 1
            ],
            [
                'name' => 'Liabilitas dan Ekuitas',
                'coa'  => 2
            ],
            [
                'name' => 'Pendapatan',
                'coa'  => 3
            ],
            [
                'name' => 'Biaya',
                'coa'  => 4
            ],
            [
                'name' => 'Pendapatan Lain-lain',
                'coa'  => 5
            ],
            [
                'name' => 'Biaya Lain-lain',
                'coa'  => 6
            ]
        ];

        $coa_body = [
            [
                'id_coa_body' => 1,
                'id_coa_head' => 1,
                'name'        => 'Asset Lancar',
                'coa'         => 11
            ],
            [
                'id_coa_body' => 2,
                'id_coa_head' => 1,
                'name'        => 'Asset Tetap',
                'coa'         => 12
            ],
            [
                'id_coa_body' => 3,
                'id_coa_head' => 1,
                'name'        => 'Asset Tidak Berwujud',
                'coa'         => 13
            ],
            [
                'id_coa_body' => 4,
                'id_coa_head' => 2,
                'name'        => 'Liabilitas',
                'coa'         => 21
            ],
            [
                'id_coa_body' => 5,
                'id_coa_head' => 2,
                'name'        => 'Ekuitas',
                'coa'         => 22
            ],
            [
                'id_coa_body' => 6,
                'id_coa_head' => 3,
                'name'        => 'Pendapatan',
                'coa'         => 31
            ],
            [
                'id_coa_body' => 7,
                'id_coa_head' => 4,
                'name'        => 'Biaya Operasional',
                'coa'         => 41
            ],
            [
                'id_coa_body' => 8,
                'id_coa_head' => 4,
                'name'        => 'Biaya Administrasi dan Umum',
                'coa'         => 42
            ],
            [
                'id_coa_body' => 9,
                'id_coa_head' => 5,
                'name'        => 'Pendapatan Lain-lain',
                'coa'         => 51
            ],
            [
                'id_coa_body' => 10,
                'id_coa_head' => 6,
                'name'        => 'Biaya Lain-lain',
                'coa'         => 61
            ],
        ];

        $coa = [
            [
                'id_coa'      => 1,
                'id_coa_body' => 1,
                'name'        => 'Kas & Setara Kas',
                'coa'         => 1100
            ],
            [
                'id_coa'      => 2,
                'id_coa_body' => 1,
                'name'        => 'Piutang Usaha',
                'coa'         => 1110
            ],
            [
                'id_coa'      => 3,
                'id_coa_body' => 1,
                'name'        => 'Persediaan Barang Tambng Nikel',
                'coa'         => 1120
            ],
            [
                'id_coa'      => 4,
                'id_coa_body' => 1,
                'name'        => 'Biaya Dibayar Muka',
                'coa'         => 1130
            ],
            [
                'id_coa'      => 5,
                'id_coa_body' => 2,
                'name'        => 'Peralatan Tambang Nikel',
                'coa'         => 1200
            ],
            [
                'id_coa'      => 6,
                'id_coa_body' => 2,
                'name'        => 'Mesin Tambang',
                'coa'         => 1210
            ],
            [
                'id_coa'      => 7,
                'id_coa_body' => 2,
                'name'        => 'Inventaris Peralatan',
                'coa'         => 1220
            ],
            [
                'id_coa'      => 8,
                'id_coa_body' => 3,
                'name'        => 'Hak Galian Nikel',
                'coa'         => 1300
            ],
            [
                'id_coa'      => 9,
                'id_coa_body' => 3,
                'name'        => 'Lisensi & Kontrak',
                'coa'         => 1310
            ],
            [
                'id_coa'      => 10,
                'id_coa_body' => 3,
                'name'        => 'Hak atas Tanah & Lahan',
                'coa'         => 1320
            ],
            [
                'id_coa'      => 11,
                'id_coa_body' => 3,
                'name'        => 'Sofware & Lisensi',
                'coa'         => 1330
            ],
            [
                'id_coa'      => 12,
                'id_coa_body' => 4,
                'name'        => 'Hutang Usaha',
                'coa'         => 2100
            ],
            [
                'id_coa'      => 13,
                'id_coa_body' => 4,
                'name'        => 'Hutang Bank',
                'coa'         => 2110
            ],
            [
                'id_coa'      => 14,
                'id_coa_body' => 4,
                'name'        => 'Hutang Pajak',
                'coa'         => 2120
            ],
            [
                'id_coa'      => 15,
                'id_coa_body' => 4,
                'name'        => 'Kewajiban Lingkungan',
                'coa'         => 2130
            ],
            [
                'id_coa'      => 16,
                'id_coa_body' => 5,
                'name'        => 'Modal',
                'coa'         => 2200
            ],
            [
                'id_coa'      => 17,
                'id_coa_body' => 5,
                'name'        => 'Laba Ditahan',
                'coa'         => 2210
            ],
            [
                'id_coa'      => 18,
                'id_coa_body' => 5,
                'name'        => 'Cadangan Pembayaran Galian Nikel',
                'coa'         => 2220
            ],
            [
                'id_coa'      => 19,
                'id_coa_body' => 6,
                'name'        => 'Pendapatan Penjualan Nikel',
                'coa'         => 3100
            ],
            [
                'id_coa'      => 20,
                'id_coa_body' => 6,
                'name'        => 'Pendapatan Penjualan ORE',
                'coa'         => 3110
            ],
            [
                'id_coa'      => 21,
                'id_coa_body' => 6,
                'name'        => 'Pendapatan Lainnya dari Penambangan Nikel',
                'coa'         => 3120
            ],
            [
                'id_coa'      => 22,
                'id_coa_body' => 7,
                'name'        => 'Biaya Man Power',
                'coa'         => 4100
            ],
            [
                'id_coa'      => 23,
                'id_coa_body' => 7,
                'name'        => 'Biaya Bahan Bakar dan Pelumas',
                'coa'         => 4110
            ],
            [
                'id_coa'      => 24,
                'id_coa_body' => 7,
                'name'        => 'Biaya Maintenance Equipment',
                'coa'         => 4120
            ],
            [
                'id_coa'      => 25,
                'id_coa_body' => 7,
                'name'        => 'Biaya Konsumsi Energi',
                'coa'         => 4130
            ],
            [
                'id_coa'      => 26,
                'id_coa_body' => 7,
                'name'        => 'Biaya Jasa Pertambangan',
                'coa'         => 4140
            ],
            [
                'id_coa'      => 27,
                'id_coa_body' => 8,
                'name'        => 'Biaya Administrasi',
                'coa'         => 4200
            ],
            [
                'id_coa'      => 28,
                'id_coa_body' => 8,
                'name'        => 'Biaya Umum',
                'coa'         => 4210
            ],
            [
                'id_coa'      => 29,
                'id_coa_body' => 8,
                'name'        => 'Biaya Penyusutan',
                'coa'         => 4220
            ],
            [
                'id_coa'      => 30,
                'id_coa_body' => 9,
                'name'        => 'Pendapatan Lain-lain dari Penambangan ',
                'coa'         => 5100
            ],
            [
                'id_coa'      => 31,
                'id_coa_body' => 10,
                'name'        => 'Biaya Lain-lain dari Penambangan',
                'coa'         => 6100
            ],
        ];

        CoaHead::insert($coa_head);
        CoaBody::insert($coa_body);
        Coa::insert($coa);
    }
}
