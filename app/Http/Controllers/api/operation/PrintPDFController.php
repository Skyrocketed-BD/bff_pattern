<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\PdfClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\finance\BankNCash;
use App\Models\finance\TransactionTerm;
use App\Models\main\Kontak;
use App\Models\main\User;
use App\Models\operation\InvoiceFob;
use App\Models\operation\Kontraktor;
use App\Models\operation\ProvisionCoa;
use App\Models\operation\ShippingInstruction;
use App\Services\operation\InvoiceBillService;
use App\Services\operation\InvoiceBillCallbackService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class PrintPDFController extends Controller
{
    public function shipping_instruction(Request $request)
    {
        $si_number  = $request->si_number;

        $data       = [];
        $amount     = [];

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = ShippingInstruction::with(['toSlot', 'toKontraktor'])->where('number_si', $si_number)->first();

        if ($query) {
            $data = [
                'title'             => 'SHIPPING INSTRUCTION (SI)',
                'company'           => $company,
                'receipent'         => $query->toKontraktor->company,
                'surveyor'          => $query->surveyor,
                'kontraktor'        => $query->toKontraktor->company,
                'si_number'         => $query->number_si,
                'date'              => $query->created_at,
                'additional_note'   => $query->information,
                'details'           => [
                    'shipper'       => $company,
                    'consignee'     => $query->consignee,
                    'notify_party'  => $query->notify_party,
                    'commodity'     => 'Nickel Ore',
                    'transport_mode' => $query->tug_boat . ' - ' . $query->barge,
                    'loading_port'  => $query->loading_port,
                    'discharge_port' => $query->unloading_port,
                    'cargo_quantity' => $query->load_amount . ' MT ' . html_entity_decode("&plusmn;") . "10%",
                    'start_date'    => Carbon::parse($query->load_date_start)->format('d-M-Y'),
                    'end_date'      => Carbon::parse($query->load_date_finish)->format('d-M-Y'),
                ],
                'mining_inspector'  => $query->mining_inspector,
                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];

            // $pdfOutput = PdfClass::view($data['title'], 'operation.shipping-instruction', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'operation.shipping-instruction', 'A4', 'potrait', $data);

            $fileName = $data['si_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('operation:shipping_instruction_print', 1, [
                'operation:shipping_instruction_number'     => $data['si_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'SI Number Not Found!'], 404);
        }
    }

    public function oriInvoiceBill(Request $request)
    {
        try {
            $request->validate([
                'inv_number'    => 'required|string',
                'termin'        => 'integer',
                'lang'          => 'string',
            ], [
                'inv_number.required'   => 'Inv Number is required',
                'inv_number.string'     => 'Inv Number must be a string',
                'termin.integer'        => 'Termin must be an integer',
                'lang.string'           => 'Lang  must be a string',
            ]);
        } catch (ValidationException $e) {
            // return ApiResponseClass::throw($e->validator->errors()->first(), 422);
            return Response::json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
        }

        $inv_number     = $request->inv_number;
        $req_termin     = $request->termin;

        // terkait lang
        $locale = 'id';
        $currency   = 'ID';
        $currency_hpm = 'ID';
        $str_advance_payment = 'Deposit';
        $str_down_payment = 'Uang Muka';

        if (isset($request->lang)) {
            if ($request->lang == 'us') {
                $locale = 'en';
                $currency = 'USD';
                $currency_hpm = 'IDR';
                $str_advance_payment = 'Advance Payment';
                $str_down_payment = 'Down Payment';
            }
        }

        Carbon::setLocale($locale);

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $title          = 'INVOICE';
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $banks          = BankNCash::where('show', 'y')->get();

        $query          = TransactionTerm::with(['toTransaction.toInvoiceBill.toInvoiceFob.toPlanBarging.toShippingInstruction.toProvision.toProvisionCoa'])->where('invoice_number', $inv_number)->first();

        if ($query) {
            $data           = [];
            $amount         = [];

            // informasi terkait barging
            $barging_details    = [];
            $buyer_name         = NULL;
            $buyer_address      = NULL;
            $buyer_contract     = NULL;

            $transport_vessel   = NULL;
            $shipping_instruction = NULL;
            $shipping_status = 0;

            $barging = $query->toTransaction->toInvoiceBill->toInvoiceFob;

            $get_buyer = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $barging->id_kontak)
                ->first();;

            if ($get_buyer) {
                $buyer_name     = $get_buyer->name;
                $buyer_address  = $get_buyer->address;
                $buyer_contract = $get_buyer->toKontrak->no_kontrak ?? NULL;
            }

            if ($barging) {
                $transport_vessel   = $barging->toPlanBarging->toShippingInstruction->tug_boat . ' - ' . $barging->toPlanBarging->toShippingInstruction->barge;
                $shipping_number    = $barging->toPlanBarging->toShippingInstruction->number_si;
                $shipping_status    = $barging->toPlanBarging->toShippingInstruction->status;
            }

            // terkait finance
            $invoice_date   = Carbon::parse($query->date)->translatedFormat('d F Y');
            $invoice_number = $query->invoice_number;
            $total_invoice  = $query->toTransaction->value;

            /// informasi terkait pembayaran/termin
            $termin_details = [];
            $total_termin   = 0;
            $termins        = $query->toTransaction->toTransactionTerm;
            $dpp            = 0;
            $dpp_lain       = 0;
            $pph            = 0;
            $ppn_dibebaskan = 0;
            $sisa_tagihan   = 0;

            $pre_payment_type   = null;
            $pre_payment        = 0;

            // cek termin nya
            if ($termins) {
                $loop_counter   = 1;

                //loop terminnya untuk ambil total dan detail setiap termin
                foreach ($termins as $termin) {
                    //simpan di array nanti dipake
                    $termin_details[] = [
                        'name'          => $termin->nama,
                        'value_percent' => locale_currency($termin->value_percent, $currency),
                        'value_int'     => $termin->value_percent,
                    ];

                    $total_termin += $termin->value_percent;

                    // cek penggunaan prepayment
                    if ($termin->deposit) {
                        if ($termin->deposit == 'advance_payment') {
                            $pre_payment_type = $str_advance_payment;
                        } else {
                            $pre_payment_type = $str_down_payment;
                        }
                        $pre_payment        = $termin->value_deposit;
                    }

                    // stop ketika sudah mencapai termin yang diminta
                    if ($loop_counter == $req_termin) {
                        break;
                    }

                    //stop termin terakhir untuk cetakan invoice final
                    if (($shipping_status == 5 || $shipping_status == 6) && !isset($req_termin)) {
                        if ($loop_counter == count($termins) - 1) {
                            break;
                        }
                    }

                    $loop_counter++;
                }
            }


            if (($shipping_status == 5 || $shipping_status == 6) && !isset($req_termin)) {
                $title = 'FINAL INVOICE';

                // todo tambah pemisah antara fob/cif
                // kalau ini statis pilih array 0 kalau fob, bagiamana kalau cif?
                $provision    = $query->toTransaction->toInvoiceBill->toInvoiceFob->toPlanBarging->toShippingInstruction->toProvision->toProvisionCoa[0];

                $details = [
                    'ni'            => $provision->ni_final,
                    'mc'            => $provision->mc_final,
                    'cf'            => ($provision->ni_final * 10) + 1,
                    'cargo'         => locale_number($provision->tonage_final),
                    'hma'           => locale_currency($provision->hma, 'USD', 2),
                    'hma_date'      => Carbon::parse($query->date)->translatedFormat('F Y'),
                    'hpm'           => locale_currency($provision->hpm, 'USD', 2),
                    'kurs'          => locale_currency($provision->kurs, $currency),
                    'hpm_idr'       => locale_currency($provision->kurs * $provision->hpm, $currency_hpm),
                    'price'         => locale_currency($provision->price, $currency),
                ];

                //hitung dpp;
                $dpp            = $total_invoice - $total_termin;
                $dpp_lain       = round($dpp * 11 / 12);
                $ppn_dibebaskan = ($dpp * 11 / 100);
                $pph            = floor($dpp * 1.5 / 100);
                $sisa_tagihan   = ($dpp - $pph);

                $termins = [
                    'details'           => $termin_details,
                    'dpp'               => locale_currency($dpp, $currency, 0),
                    'dpp_lain'          => locale_currency($dpp_lain, $currency, 0),
                    'ppn_dibebaskan'    => locale_currency($ppn_dibebaskan, $currency, 0),
                    'pph'               => locale_currency($pph, $currency, 0),
                    'sisa_tagihan'      => locale_currency($sisa_tagihan, $currency, 0)
                ];
            } else {
                $count = count($termin_details);
                if ($req_termin > $count) {
                    $req_termin = $count;
                }

                $title = '#' . $req_termin . ' INVOICE';

                $details = [
                    'ni'            => $barging->ni,
                    'mc'            => $barging->mc,
                    'cf'            => ($barging->ni * 10) + 1,
                    'cargo'         => locale_number($barging->tonage),
                    'hma'           => locale_currency($barging->hma, 'USD', 2),
                    'hma_date'      => Carbon::parse($barging->date)->translatedFormat('F Y'),
                    'hpm'           => locale_currency($barging->hpm, 'USD', 2),
                    'kurs'          => locale_currency($barging->kurs, $currency),
                    'hpm_idr'       => locale_currency($barging->kurs * $barging->hpm, $currency),
                    'price'         => locale_currency($barging->price, $currency),
                ];

                //hitung dpp
                $dpp            = $termin_details[$req_termin - 1]['value_int'];
                $dpp_lain       = round($dpp * 11 / 12);
                $ppn_dibebaskan = ($dpp * 11 / 100);
                $pph            = floor($dpp * 1.5 / 100);
                $sisa_tagihan   = ($dpp - $pph);

                $termins = [
                    'details'           => $termin_details,
                    'dpp'               => locale_currency($dpp, $currency, 0),
                    'dpp_lain'          => locale_currency($dpp_lain, $currency, 0),
                    'ppn_dibebaskan'    => locale_currency($ppn_dibebaskan, $currency, 0),
                    'pph'               => locale_currency($pph, $currency, 0),
                    'sisa_tagihan'      => locale_currency($sisa_tagihan, $currency, 0)
                ];
            }


            // cek sudah ada penerimaan atau belum
            $receipts       = [];
            if ($query->toTransaction->toReceipts) {
                foreach ($query->toTransaction->toReceipts->where('status', 'valid') as $key => $receipt) {
                    // kayaknya ini ndak dipakeji
                    // $index = $key;
                    // $termin_name = '';

                    // if ($key > count($termin_details) - 1) {
                    //     $termin_name = 'FINALzz INVOICE';
                    // } else {
                    //     $termin_name = $termin_details[$index]['name'] . 'ssss';
                    // }

                    $receipts[] = [
                        'date'       => $receipt->date,
                        'pnm_number' => $receipt->transaction_number,
                        'value'      => locale_currency($receipt->value, 'ID'),
                        'termin'     => $receipt->description,
                    ];
                }
            }


            // final output
            $data = [
                // 'fields'                => $fields,
                'title'                 => $title,
                'company'               => $company,
                'receipent'             => $buyer_name,
                'receipent_address'     => $buyer_address,
                'receipent_contract'    => $buyer_contract,
                'date'                  => $invoice_date,
                'invoice_number'        => $invoice_number,
                'transport_vessel'      => $transport_vessel,
                'shipping_number'       => $shipping_number,
                'details'               => $details,

                'receipts'              => $receipts,
                'termins'               => $termins,
                'terbilang'             => _numberToWords($locale, $sisa_tagihan) . ' Rupiah',

                'pre_payment_type'      => $pre_payment_type,
                'pre_payment'           => locale_currency($pre_payment, $currency),

                'banks'                 => $banks,
                'printed_date'          => $printed_date,
                'printed_by'            => $username,
            ];

            $pdfOutput = PdfClass::view($data['title'], 'operation.invoice-bill', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'operation.invoice-bill', 'A4', 'potrait', $data);

            $fileName = $data['invoice_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:invoice_bill_print', 1, [
                'finance:invoice_number' => $data['invoice_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'Invoice Number Not Found!'], 404);
        }
    }

    public function invoiceBill(Request $request)
    {
        // nanti mau pake FormRequest
        $validated = $request->validate([
            'inv_number' => 'required|string',
            'termin'     => 'integer',
            'lang'       => 'string',
            'draft'      => 'string',
        ]);

        // semua logika dihandle sama service
        $service    = new InvoiceBillService(); //new transaction
        $result     = $service->generateData($request);

        if (!$result['success']) {
            $callbackService = new InvoiceBillCallbackService(); //fallback transaction
            $result = $callbackService->generateData($request);
        }

        if (!$result['success']) {
            return Response::json(['success' => false, 'message' => $result['message']], $result['code']);
        }

        $data = $result['data'];

        // tinggal generate pdf
        // $pdfOutput = PdfClass::view($data['title'], 'operation.invoice-bill', 'A4', 'potrait', $data);
        $pdfOutput = PdfClass::print($data['title'], 'operation.invoice-bill', 'A4', 'potrait', $data);
        $fileName = $data['invoice_number'] . '-' . now()->format('YmdHis') . '.pdf';

        ActivityLogHelper::log('finance:invoice_bill_print', 1, ['finance:invoice_number' => $data['invoice_number']]);

        return response($pdfOutput, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename=' . $fileName);
    }

    public function invoiceBillDraft(Request $request)
    {
        // nanti mau pake FormRequest
        $validated = $request->validate([
            'inv_number' => 'required|string',
            'lang'       => 'string',
        ]);

        // semua logika dihandle sama service
        $service    = new InvoiceBillService();
        $result     = $service->generateDraftData($request);

        if (!$result['success']) {
            return Response::json(['success' => false, 'message' => $result['message']], $result['code']);
        }

        $data = $result['data'];

        // tinggal generate pdf
        // $pdfOutput = PdfClass::view($data['title'], 'operation.invoice-bill', 'A4', 'potrait', $data);
        $pdfOutput = PdfClass::print($data['title'], 'operation.invoice-bill', 'A4', 'potrait', $data);
        $fileName = $data['invoice_number'] . '-' . now()->format('YmdHis') . '.pdf';

        ActivityLogHelper::log('finance:invoice_bill_print', 1, ['finance:invoice_number' => $data['invoice_number']]);

        return response($pdfOutput, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename=' . $fileName);
    }
}
