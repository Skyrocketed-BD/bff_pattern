<?php

namespace Database\Seeders;

use App\Models\main\Arrangement;
use Illuminate\Database\Seeder;

class ArrangementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // begin:: main
            [
                'key'   => 'company_name',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'company_initial',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'company_category',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'est_date',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'email',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'phone',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'address',
                'type'  => 'textarea',
                'value' => null
            ],
            [
                'key'   => 'address_opt',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'city',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'province',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'zip',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'pic',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'pic_phone',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'pic2',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'phone_pic2',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'logo',
                'type'  => 'file',
                'value' => null
            ],
            [
                'key'   => 'is_setup',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'npwp',
                'type'  => 'text',
                'value' => null
            ],
            [
                'key'   => 'pkp',
                'type'  => 'text',
                'value' => 'pkp'
            ],
            // end:: main
            // begin:: finance
            [
                'key'   => 'coa_digit',
                'type'  => 'integer',
                'value' => 6
            ],
            [
                'key'   => 'receive_coa_discount',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'expense_coa_discount',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'equity_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'retained_earnings_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'income_summary_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'cutoff_date',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'lifespan',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'bank_fee_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'bank_interest_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'bank_interest_tax_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'down_payment_deposit_journal',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'down_payment_adjustment_journal',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'advance_payment_deposit_journal',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'advance_payment_adjustment_coa',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'coa_vat',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'coa_pph_badan',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'coa_pph_pasal_22',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'coa_pph_pasal_23',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'coa_pph_pasal_25',
                'type'  => 'integer',
                'value' => 0
            ],
            [
                'key'   => 'coa_utang_pajak_29',
                'type'  => 'integer',
                'value' => 0
            ],
            // end:: finance
        ];

        foreach ($data as $key => $value) {
            Arrangement::insert($value);
        }
    }
}
