<?php

namespace App\Services\operation;

use App\Models\finance\BankNCash;
use App\Models\finance\TransactionTerm;
use App\Models\main\Kontak;
use App\Models\main\User;
use App\Models\operation\InvoiceFob;
use Carbon\Carbon;

class InvoiceBillCallbackService extends InvoiceBillService
{
    public function generateData($request)
    {
        $isCallback = true;
        $query      = InvoiceFob::with(['toTransaction', 'toPlanBarging.toShippingInstruction.toProvision.toProvisionCoa'])->where('transaction_number', $request->inv_number)->first();

        if (!$query) {
            return ['success' => false, 'message' => 'Invoice Number Not Found!', 'code' => 404];
        }

        $config = $this->getLocaleConfig($request->lang);
        $fields = $this->getFieldTranslations($config['locale']);
        Carbon::setLocale($config['locale']);

        $barging        = $query;
        $shipping       = $barging->toPlanBarging->toShippingInstruction;
        $shippingStatus = $shipping->status ?? 0;

        $isFinal = ($shippingStatus == 5 || $shippingStatus == 6) && !$request->has('termin');

        // ambil data-data pendukung
        $buyer      = $this->getBuyerData($barging->id_kontak);
        $terminData = $this->calculateTermins($query, $request->termin, $config, $isFinal, $isCallback);
        $receipts   = $this->getReceiptsData($query, $config);

        // dto sederhana
        $data = [
            'title'              => $isFinal ? 'FINAL INVOICE' : '#' . ($request->termin ?? 1) . ' INVOICE',
            'company'            => get_arrangement('company_name'),
            'receipent'          => $buyer['name'],
            'receipent_address'  => $buyer['address'],
            'receipent_contract' => $buyer['contract'],
            'date'               => Carbon::parse($query->date)->translatedFormat('d F Y'),
            'invoice_number'     => $query->transaction_number,
            'transport_vessel'   => $shipping->tug_boat . ' - ' . $shipping->barge,
            'shipping_number'    => $shipping->number_si,
            'details'            => $this->getInvoiceDetails($query, $isFinal, $config),
            'receipts'           => $receipts,
            'termins'            => $terminData['summary'],
            'terbilang'          => _numberToWords($config['locale'], $terminData['summary']['sisa_tagihan_raw']) . ' Rupiah',
            'pre_payment_type'   => $terminData['pre_payment_type'],
            'pre_payment'        => $terminData['pre_payment'],
            'banks'              => BankNCash::where('show', 'y')->get(),
            'printed_date'       => Carbon::now()->setTimezone('Asia/Singapore')->toDateTimeString(),
            'printed_by'         => User::find(auth()->id())->name ?? 'invalid user',
            'fields'             => $fields,
        ];

        return ['success' => true, 'data' => $data];
    }

    private function getInvoiceDetails($query, $isFinal, $config)
    {
        $barging = $query;

        if ($isFinal) {
            // ambil dari provision (final)
            // todo tambah pemisah antara fob/cif
            // kalau ini statis pilih array 0 kalau fob, bagiamana kalau cif?
            $provision = $barging->toPlanBarging->toShippingInstruction->toProvision->toProvisionCoa[0];

            return [
                'ni'       => $provision->ni_final,
                'mc'       => $provision->mc_final,
                'cf'       => ($provision->ni_final * 10) + 1,
                'cargo'    => locale_number($provision->tonage_final),
                'hma'      => locale_currency($provision->hma, 'USD', 2),
                'hma_date' => Carbon::parse($query->date)->translatedFormat('F Y'),
                'hpm'      => locale_currency($provision->hpm, 'USD', 2),
                'kurs'     => locale_currency($provision->kurs, $config['currency_hpm']),
                'hpm_idr'  => locale_currency($provision->kurs * $provision->hpm, $config['currency_hpm']),
                'price'    => locale_currency($provision->price, $config['currency']),
            ];
        }

        // ambil dari barging/biasa (termin)
        return [
            'ni'       => $barging->ni,
            'mc'       => $barging->mc,
            'cf'       => ($barging->ni * 10) + 1,
            'cargo'    => locale_number($barging->tonage),
            'hma'      => locale_currency($barging->hma, 'USD', 2),
            'hma_date' => Carbon::parse($barging->date)->translatedFormat('F Y'),
            'hpm'      => locale_currency($barging->hpm, 'USD', 2),
            'kurs'     => locale_currency($barging->kurs, $config['currency']),
            'hpm_idr'  => locale_currency($barging->kurs * $barging->hpm, $config['currency']),
            'price'    => locale_currency($barging->price, $config['currency']),
        ];
    }
}
