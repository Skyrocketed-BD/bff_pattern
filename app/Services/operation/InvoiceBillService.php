<?php

namespace App\Services\operation;

use App\Models\finance\BankNCash;
use App\Models\finance\TransactionTerm;
use App\Models\main\Kontak;
use App\Models\main\User;
use App\Models\operation\InvoiceFob;
use Carbon\Carbon;

class InvoiceBillService
{
    public function generateData($request)
    {
        $isCallback = false;
        $query = TransactionTerm::with([
            'toTransaction.toInvoiceBill.toInvoiceFob.toPlanBarging.toShippingInstruction.toProvision.toProvisionCoa',
            'toTransaction.toReceipts',
            'toTransaction.toTransactionTerm'
        ])->where('invoice_number', $request->inv_number)->first();

        if (!$query) {
            return ['success' => false, 'message' => 'Invoice Number Not Found!', 'code' => 422];
        }

        $config = $this->getLocaleConfig($request->lang);
        $fields = $this->getFieldTranslations($config['locale']);
        Carbon::setLocale($config['locale']);

        $barging = $query->toTransaction->toInvoiceBill->toInvoiceFob;
        $shipping = $barging->toPlanBarging->toShippingInstruction;
        $shippingStatus = $shipping->status ?? 0;

        $isFinal = ($shippingStatus == 5 || $shippingStatus == 6) && !$request->has('termin');

        // ambil data-data pendukung
        $buyer = $this->getBuyerData($barging->id_kontak);
        $terminData = $this->calculateTermins($query, $request->termin, $config, $isFinal, $isCallback);
        $receipts = $this->getReceiptsData($query, $config);

        // dto sederhana
        $data = $this->formattedData($query, $request, $isFinal, $config, $buyer, $shipping, $terminData, $receipts, $fields);

        return ['success' => true, 'data' => $data];
    }

    public function generateDraftData($request)
    {
        $query = InvoiceFob::with(['toInvoiceBill', 'toPlanBarging.toShippingInstruction.toProvision.toProvisionCoa'])->where('transaction_number', $request->inv_number)->first();

        if (!$query) {
            return ['success' => false, 'message' => 'Invoice Number Not Found!', 'code' => 404];
        }

        $config = $this->getLocaleConfig($request->lang);
        $fields = $this->getFieldTranslations($config['locale']);
        Carbon::setLocale($config['locale']);

        $barging = $query;
        $shipping = $barging->toPlanBarging->toShippingInstruction;
        $shippingStatus = $shipping->status ?? 0;

        $isFinal = ($shippingStatus == 5 || $shippingStatus == 6) && !$request->has('termin');

        // ambil data-data pendukung
        $buyer = $this->getBuyerData($barging->id_kontak);
        $terminData = $this->calculateDraft($query, $config);
        $receipts = [];

        // dto sederhana
        $data = $this->formattedDraftData($query, $request, $isFinal, $config, $buyer, $shipping, $terminData, $receipts, $fields);

        return ['success' => true, 'data' => $data];
    }

    public function getLocaleConfig($lang)
    {
        if ($lang == 'us') {
            return [
                'locale' => 'en',
                'currency' => 'USD',
                'currency_hpm' => 'IDR',
                'adv' => 'Advance Payment',
                'dp' => 'Down Payment'
            ];
        }
        return [
            'locale' => 'id',
            'currency' => 'ID',
            'currency_hpm' => 'ID',
            'adv' => 'Deposit',
            'dp' => 'Uang Muka'
        ];
    }

    public function getFieldTranslations($locale)
    {
        $dictionary = [
            'id' => [
                'to' => 'Kepada',
                'invoice_date' => 'Tanggal Invoice',
                'invoice_no' => 'No Invoice',
                'contract_no' => 'No Kontrak',
                'si_no' => 'No SI',
                'quantity' => 'Kuantitas',
                'price' => 'Harga',
                'rate' => 'Kurs',
                'idr_price' => 'Harga IDR',
                'description' => 'Keterangan',
                'tonage' => 'Tonase',
                'rp_mt' => 'Rp/MT',
                'amount' => 'Jumlah',
                'total_invoice_dpp' => 'Jumlah Tagihan (DPP)',
                'dpp_nilai_lain' => 'DPP Nilai Lain',
                'ppn_dibebaskan' => 'PPN 12% (dibebaskan)',
                'potongan_pph' => 'Potongan PPh Pasal 22 (1,5%)',
                'total_invoice' => 'Total Tagihan',
                'payment_history' => 'Riwayat Pembayaran',
                'date' => 'Tanggal',
                'transaction_no' => 'No Transaksi',
                'payment_via' => 'Pembayaran transfer ke Rekening:',
                'regards' => 'Hormat kami',
                'progress_invoice' => 'DRAF INVOICE',
                'bank_not_set' => 'Bank Belum Diatur!',
                'currency'  => ' Rupiah'
            ],
            'en' => [
                'to' => 'To',
                'invoice_date' => 'Invoice Date',
                'invoice_no' => 'Invoice No',
                'contract_no' => 'Contract No',
                'si_no' => 'SI No',
                'quantity' => 'Quantity',
                'price' => 'Price',
                'rate' => 'Rate',
                'idr_price' => 'IDR Price',
                'description' => 'Description',
                'tonage' => 'Tonage',
                'rp_mt' => 'IDR/MT',
                'amount' => 'Amount',
                'total_invoice_dpp' => 'Total Invoice Amount (Tax Base / DPP)',
                'dpp_nilai_lain' => 'DPP Other Value',
                'ppn_dibebaskan' => 'VAT 12% (exempted)',
                'potongan_pph' => 'Witholding Tax Article 22 (1.5%)',
                'total_invoice' => 'Total Invoice',
                'payment_history' => 'Payment History',
                'date' => 'Date',
                'transaction_no' => 'Transaction No',
                'payment_via' => 'Payment via Bank Transfer to:',
                'regards' => 'Sincerely',
                'progress_invoice' => 'DRAFT INVOICE',
                'bank_not_set' => 'Bank Not Set!',
                'currency'  => ' Dollar'
            ]
        ];

        // default ke id jika local tidak ada di dictionary
        return $dictionary[$locale] ?? $dictionary['id'];
    }

    public function getBuyerData($id_kontak)
    {
        $get_buyer = Kontak::with('toKontrak:id_kontrak,no_kontrak')
            ->select('id_kontak', 'id_kontrak', 'name', 'address')
            ->where('id_kontak', $id_kontak)
            ->first();

        return [
            'name' => $get_buyer->name ?? null,
            'address' => $get_buyer->address ?? null,
            'contract' => $get_buyer->toKontrak->no_kontrak ?? null,
        ];
    }

    public function getReceiptsData($query, $config)
    {
        $receipts = [];
        $rawReceipts = $query->toTransaction->toReceipts->where('status', 'valid') ?? [];

        foreach ($rawReceipts as $receipt) {
            $receipts[] = [
                'date' => $receipt->date,
                'pnm_number' => $receipt->transaction_number,
                'value' => locale_currency($receipt->value, $config['currency']),
                'termin' => $receipt->description,
            ];
        }

        return $receipts;
    }

    public function calculateTermins($query, $req_termin, $config, $isFinal, $isCallback)
    {
        $termins = $query->toTransaction->toTransactionTerm;
        $total_termin = 0;
        $termin_details = [];
        $pre_payment = 0;
        // todo mau check ini
        // $shipping_status = $query->toTransaction->toInvoiceBill->toInvoiceFob->toPlanBarging->toShippingInstruction->status ?? 0;
        $shipping_status = $query->toPlanBarging->toShippingInstruction->status ?? 0;
        $loop_counter = 1;

        foreach ($termins as $termin) {

            $pre_payment_type = null;

            $termin_details[] = [
                'name' => $termin->nama,
                'value_percent' => locale_currency($termin->value_percent, $config['currency']),
                'value_int' => $termin->value_percent,
            ];


            // cek penggunaan prepayment
            if ($termin->deposit) {
                $pre_payment_type = ($termin->deposit == 'advance_payment') ? $config['adv'] : $config['dp'];
                $pre_payment = $termin->value_deposit;
            }

            // stop ketika sudah mencapai termin yang diminta
            if ($req_termin && $loop_counter == $req_termin) break;

            //stop termin terakhir untuk cetakan invoice final
            // if ($isFinal && !isset($req_termin)) { //kayaknya ndak perlu !isset($req_termin),
            if ($isFinal && !$isCallback) {
                if ($loop_counter == count($termins) - 1) break;
            }

            $total_termin += $termin->value_percent;

            $loop_counter++;
        }

        // dd($termin_details, $pre_payment_type, $pre_payment);

        // hitung dpp
        if ($isFinal) {
            $total_invoice = $query->toTransaction->value;
            $dpp = $total_invoice - $total_termin;
        } else {
            $index = ($req_termin ?? 1) - 1;
            // fallback kalo index tidak ada
            $dpp = $termin_details[$index]['value_int'] ?? 0;
        }

        // hitung pajak-pajak
        $dpp_lain = round($dpp * 11 / 12);
        $ppn_dibebaskan = ($dpp * 11 / 100);
        $pph = floor($dpp * 1.5 / 100);
        $sisa_tagihan = ($dpp - $pph);

        // kurangi sisa tagihan dengan prepayment kalau ada
        if ($pre_payment_type) {
            $sisa_tagihan = ($dpp - $pph) - $pre_payment;
        }

        return [
            'pre_payment' => locale_currency($pre_payment, $config['currency']),
            'pre_payment_type' => $pre_payment_type,
            'summary' => [
                'details' => $termin_details,
                'dpp' => locale_currency($dpp, $config['currency'], 0),
                'dpp_lain' => locale_currency($dpp_lain, $config['currency'], 0),
                'ppn_dibebaskan' => locale_currency($ppn_dibebaskan, $config['currency'], 0),
                'pph' => locale_currency($pph, $config['currency'], 0),
                'sisa_tagihan' => locale_currency($sisa_tagihan, $config['currency'], 0),
                'sisa_tagihan_raw' => $sisa_tagihan // Untuk kebutuhan _numberToWords
            ]
        ];
    }

    public function calculateDraft($query, $config)
    {
        $pre_payment = 0;
        // todo mau check ini
        $shipping_status = $query->toTransaction->toInvoiceBill->toInvoiceFob->toPlanBarging->toShippingInstruction->status ?? 0;
        $loop_counter = 1;

        // hitung dpp
        $total_invoice = $query->toInvoiceBill->total ?? $query->toTransaction->value;
        $dpp = $total_invoice;

        // hitung pajak-pajak
        $dpp_lain = round($dpp * 11 / 12);
        $ppn_dibebaskan = ($dpp * 11 / 100);
        $pph = floor($dpp * 1.5 / 100);
        $sisa_tagihan = ($dpp - $pph);

        return [
            'pre_payment' => 0,
            'pre_payment_type' => null,
            'summary' => [
                'details' => [],
                'dpp' => locale_currency($dpp, $config['currency'], 0),
                'dpp_lain' => locale_currency($dpp_lain, $config['currency'], 0),
                'ppn_dibebaskan' => locale_currency($ppn_dibebaskan, $config['currency'], 0),
                'pph' => locale_currency($pph, $config['currency'], 0),
                'sisa_tagihan' => locale_currency($sisa_tagihan, $config['currency'], 0),
                'sisa_tagihan_raw' => $sisa_tagihan // Untuk kebutuhan _numberToWords
            ]
        ];
    }

    private function getInvoiceDetails($query, $isFinal, $config)
    {
        $barging = $query->toTransaction->toInvoiceBill->toInvoiceFob;

        if ($isFinal) {
            // ambil dari provision (final)
            // todo tambah pemisah antara fob/cif
            // kalau ini statis pilih array 0 kalau fob, bagiamana kalau cif?
            $provision = $barging->toPlanBarging->toShippingInstruction->toProvision->toProvisionCoa[0];

            return [
                'ni'        => $provision->ni_final,
                'mc'        => $provision->mc_final,
                'cf'        => ($provision->ni_final * 10) + 1,
                'cargo'     => locale_number($provision->tonage_final),
                'hma'       => locale_currency($provision->hma, 'USD', 2),
                'hma_date'  => Carbon::parse($query->date)->translatedFormat('F Y'),
                'hpm'       => locale_currency($provision->hpm, 'USD', 2),
                'kurs'      => locale_currency($provision->kurs, $config['currency_hpm']),
                'hpm_idr'   => locale_currency($provision->kurs * $provision->hpm, $config['currency_hpm']),
                'price'     => locale_currency($provision->price, $config['currency']),
            ];
        }

        // ambil dari barging/biasa (termin)
        return [
            'ni' => $barging->ni,
            'mc' => $barging->mc,
            'cf' => ($barging->ni * 10) + 1,
            'cargo' => locale_number($barging->tonage),
            'hma' => locale_currency($barging->hma, 'USD', 2),
            'hma_date' => Carbon::parse($barging->date)->translatedFormat('F Y'),
            'hpm' => locale_currency($barging->hpm, 'USD', 2),
            'kurs' => locale_currency($barging->kurs, $config['currency_hpm']),
            'hpm_idr' => locale_currency($barging->kurs * $barging->hpm, $config['currency_hpm']),
            'price' => locale_currency($barging->price, $config['currency']),
        ];
    }

    private function getDraftInvoiceDetails($query, $isFinal, $config)
    {
        $barging = $query;

        // ambil dari barging/biasa (termin)
        return [
            'ni' => $barging->ni,
            'mc' => $barging->mc,
            'cf' => ($barging->ni * 10) + 1,
            'cargo' => locale_number($barging->tonage),
            'hma' => locale_currency($barging->hma, 'USD', 2),
            'hma_date' => Carbon::parse($barging->date)->translatedFormat('F Y'),
            'hpm' => locale_currency($barging->hpm, 'USD', 2),
            'kurs' => locale_currency($barging->kurs, $config['currency_hpm']),
            'hpm_idr' => locale_currency($barging->kurs * $barging->hpm, $config['currency_hpm']),
            'price' => locale_currency($barging->price, $config['currency']),
        ];
    }

    protected function formattedData($query, $request, $isFinal, $config, $buyer, $shipping, $terminData, $receipts, $fields)
    {
        return [
            'title'              => $isFinal ? 'FINAL INVOICE' : '#' . ($request->termin ?? 1) . ' INVOICE',
            'company'            => get_arrangement('company_name'),
            'receipent'          => $buyer['name'],
            'receipent_address'  => $buyer['address'],
            'receipent_contract' => $buyer['contract'],
            'date'               => Carbon::parse($query->date)->translatedFormat('d F Y'),

            // fallback invoice_number vs transaction_number
            'invoice_number'     => $query->invoice_number ?? $query->transaction_number,

            'transport_vessel'   => ($shipping->tug_boat ?? '') . ' - ' . ($shipping->barge ?? ''),
            'shipping_number'    => $shipping->number_si ?? null,
            'details'            => $this->getInvoiceDetails($query, $isFinal, $config),
            'receipts'           => $receipts,
            'termins'            => $terminData['summary'],
            'terbilang'          => _numberToWords($config['locale'], $terminData['summary']['sisa_tagihan_raw']) . $fields['currency'],
            'pre_payment_type'   => $terminData['pre_payment_type'],
            'pre_payment'        => $terminData['pre_payment'],
            'banks'              => BankNCash::where('show', 'y')->get(),
            'printed_date'       => Carbon::now()->setTimezone('Asia/Singapore')->toDateTimeString(),
            'printed_by'         => User::find(auth()->id())->name ?? 'invalid user',
            'fields'             => $fields,
            'trans'              => $this->getFieldTranslations($config['locale']),
        ];
    }

    protected function formattedDraftData($query, $request, $isFinal, $config, $buyer, $shipping, $terminData, $receipts, $fields)
    {
        return [
            'title'              => $fields['progress_invoice'],
            'company'            => get_arrangement('company_name'),
            'receipent'          => $buyer['name'],
            'receipent_address'  => $buyer['address'],
            'receipent_contract' => $buyer['contract'],
            'date'               => Carbon::parse($query->date)->translatedFormat('d F Y'),

            // fallback invoice_number vs transaction_number
            'invoice_number'     => $query->invoice_number ?? $query->transaction_number,

            'transport_vessel'   => ($shipping->tug_boat ?? '') . ' - ' . ($shipping->barge ?? ''),
            'shipping_number'    => $shipping->number_si ?? null,
            'details'            => $this->getDraftInvoiceDetails($query, $isFinal, $config),
            'receipts'           => $receipts,
            'termins'            => $terminData['summary'],
            'terbilang'          => _numberToWords($config['locale'], $terminData['summary']['sisa_tagihan_raw']) . $fields['currency'],
            'pre_payment_type'   => $terminData['pre_payment_type'],
            'pre_payment'        => $terminData['pre_payment'],
            'banks'              => BankNCash::where('show', 'y')->get(),
            'printed_date'       => Carbon::now()->setTimezone('Asia/Singapore')->toDateTimeString(),
            'printed_by'         => User::find(auth()->id())->name ?? 'invalid user',
            'fields'             => $fields,
            'trans'              => $this->getFieldTranslations($config['locale']),
        ];
    }
}
