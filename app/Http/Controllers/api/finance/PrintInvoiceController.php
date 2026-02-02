<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Classes\PdfClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\finance\BankNCash;
use App\Models\finance\DownPayment;
use App\Models\finance\DownPaymentDetails;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Journal;
use App\Models\finance\JournalSet;
use App\Models\finance\Liability;
use App\Models\finance\LiabilityDetail;
use App\Models\finance\Receipts;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use App\Models\main\Kontak;
use App\Models\main\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class PrintInvoiceController extends Controller
{

    public function fullReceive(Request $request)
    {
        // if(isset($request->lang)) {
        //     if ($request->lang == 'id') {
        //         return $this->fullReceiveID($request);
        //     } else {
        //         return $this->fullReceiveEN($request);
        //     }
        // } else {
        return $this->fullReceiveID($request);
        // }
    }

    public function fullReceiveID(Request $request)
    {
        $transaction_number = $request->transaction_number;

        $data           = [];
        $amount         = [];
        $total_paid     = 0;

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString(); // Output dalam GMT+8
        $paid_to        = 'Unknown Destination Payment';
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = TransactionFull::where('transaction_number', $transaction_number)->where('status', 'valid')->first();
        $company        = get_arrangement('company_name');

        if ($query) {
            switch ($query->record_type) {
                case 'cash':
                    $record_type = 'kas';
                    $paid_to = 'Kas';
                    break;
                case 'petty_cash':
                    $record_type = 'kas kecil';
                    $paid_to = 'Kas Kecil';
                    break;
                default:
                    $record_type = $query->record_type;
                    break;
            }

            $title          = 'PENERIMAAN' . ' ' . Str::upper($record_type);
            $get_receipent  = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $query->id_kontak)
                ->first();;

            if ($get_receipent) {
                $receipent_name     = $get_receipent->name;
                $receipent_address  = $get_receipent->address;
            }

            $id_journal         = GeneralLedger::where('transaction_number', $query->transaction_number)->where('type', 'K')->where('phase', 'opr')->first()->id_journal;
            $journal_set        = JournalSet::where('id_journal', $id_journal)->where('type', 'K')->orderBy('serial_number', 'desc')->get();
            $bank_cash          = BankNCash::all();
            $transaction_name   = Journal::find($id_journal)->name;

            $general_ledger     = GeneralLedger::where('transaction_number', $query->transaction_number)->where('phase', 'opr')->get();

            foreach ($journal_set as $journal) {
                $coa = $journal->toCoa->coa;
                foreach ($general_ledger as $gl) {
                    if ($gl->type == 'K') {
                        if ($gl->coa == $coa) {
                            if ($journal->id_tax_rate == null) { //artinya bukan pajak
                                $amount['amount'] = $gl->value;
                            } else {
                                $amount['ppn'] = $gl->value;
                            }
                        }
                    }
                }
            }

            foreach ($bank_cash as $bank) {
                $coa = $bank->toCoa->coa;
                foreach ($general_ledger as $gl) {
                    if ($gl->type == 'D') {
                        if ($gl->coa == $coa) {
                            $paid_to = $gl->toCoa->name;
                        }
                    }
                }
            }

            $total_paid = $query->value;

            $data = [
                'title'             => $title,
                'company'           => $company,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'transaction_number' => $transaction_number,
                'receipent'         => $receipent_name,
                'receipent_address' => $receipent_address,
                'transaction_name'  => $transaction_name,
                'description'       => $query->description,
                'total'             => rupiah($total_paid),
                'terbilang'         => _numberToWords('id', $total_paid) . ' Rupiah',
                'paid_to'           => $paid_to,
                'printed_by'        => $username,
                'printed_date'      => $printed_date,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'finance.invoice.full-receive-id', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'finance.invoice.full-receive-id', 'A4', 'potrait', $data);

            $fileName = $data['transaction_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:print_invoice', 1, [
                'finance:transaction_number' => $transaction_number,
                'title'                      => $data['title'],
                'printed_date'               => $printed_date,
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            ActivityLogHelper::log('finance:print_invoice', 0, ['message' => 'Transaction Number Not Found!']);
            return ApiResponseClass::throw('Transaction Number Not Found!', 400);
        }
    }

    public function fullExpense(Request $request)
    {
        // if(isset($request->lang)) {
        //     if ($request->lang == 'id') {
        //         return $this->fullExpenseID($request);
        //     } else {
        //         return $this->fullExpenseEN($request);
        //     }
        // } else {
        return $this->fullExpenseID($request);
        // }
    }

    public function fullExpenseID(Request $request)
    {
        $transaction_number = $request->transaction_number;

        $data           = [];
        $amount         = [];
        $total_paid     = 0;

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString(); // Output dalam GMT+8
        $paid_to        = 'Unknown Destination Payment';
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = TransactionFull::where('transaction_number', $transaction_number)->where('status', 'valid')->first();
        $company        = get_arrangement('company_name');

        if ($query) {
            switch ($query->record_type) {
                case 'cash':
                    $record_type = 'kas';
                    $paid_to = 'Kas';
                    break;
                case 'petty_cash':
                    $record_type = 'kas kecil';
                    $paid_to = 'Kas Kecil';
                    break;
                default:
                    $record_type = $query->record_type;
                    break;
            }

            $title          = 'PENGELUARAN' . ' ' . Str::upper($record_type) . ' ';
            $get_receipent  = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $query->id_kontak)
                ->first();;

            if ($get_receipent) {
                $receipent_name     = $get_receipent->name;
                $receipent_address  = $get_receipent->address;
            }

            $id_journal         = GeneralLedger::where('transaction_number', $query->transaction_number)->where('type', 'K')->where('phase', 'opr')->first()->id_journal;
            $journal_set        = JournalSet::where('id_journal', $id_journal)->where('type', 'K')->orderBy('serial_number', 'desc')->get();
            $bank_cash          = BankNCash::all();
            $transaction_name   = Journal::find($id_journal)->name;

            $general_ledger     = GeneralLedger::where('transaction_number', $query->transaction_number)->where('phase', 'opr')->get();

            foreach ($journal_set as $journal) {
                $coa = $journal->toCoa->coa;
                foreach ($general_ledger as $gl) {
                    if ($gl->type == 'K') {
                        if ($gl->coa == $coa) {
                            if ($journal->id_tax_rate == null) { //artinya bukan pajak
                                $amount['amount'] = $gl->value;
                            } else {
                                $amount['ppn'] = $gl->value;
                            }
                        }
                    }
                }
            }

            foreach ($bank_cash as $bank) {
                $coa = $bank->toCoa->coa;
                foreach ($general_ledger as $gl) {
                    if ($gl->type == 'K') {
                        if ($gl->coa == $coa) {
                            $paid_to = $gl->toCoa->name;
                        }
                    }
                }
            }

            $total_paid = $query->value;

            $data = [
                'title'             => $title,
                'company'           => $company,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'transaction_number' => $transaction_number,
                'receipent'         => $receipent_name,
                'receipent_address' => $receipent_address,
                'transaction_name'  => $transaction_name,
                'description'       => $query->description,
                'total'             => rupiah($total_paid),
                'terbilang'         => _numberToWords('id', $total_paid) . ' Rupiah',
                'paid_to'           => $paid_to,
                'printed_by'        => $username,
                'printed_date'      => $printed_date,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'finance.invoice.full-expense-id', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'finance.invoice.full-receive', 'A4', 'potrait', $data);

            $fileName = $data['transaction_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:print_invoice', 1, [
                'finance:transaction_number' => $transaction_number,
                'title'                      => $data['title'],
                'printed_date'               => $printed_date,
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            ActivityLogHelper::log('finance:print_invoice', 0, ['message' => 'Transaction Number Not Found!']);
            return ApiResponseClass::throw('Transaction Number Not Found!', 400);
        }
    }

    public function outstanding_invoice_receive(Request $request)
    {
        $transaction_number = $request->transaction_number;

        $data       = [];
        $details    = [];
        $detailsRaw = [];
        $amount     = [];
        $total_paid = 0;

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();

        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = Transaction::with('toJournal')->where('transaction_number', $transaction_number)->where('status', 'valid')->first();
        $company        = get_arrangement('company_name');
        $receipent_name     = NULL;
        $receipent_address  = NULL;
        $transaction_name   = NULL;
        $tax_name           = NULL;
        $paid_to            = 'Unknown Destination Payment';


        if ($query) {
            // $title          = 'OUTSTANDING RECEIVE';
            $title          = 'OUTSTANDING INVOICE';
            $get_receipent  = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $query->id_kontak)
                ->first();;

            if ($get_receipent) {
                $receipent_name     = $get_receipent->name;
                $receipent_address  = $get_receipent->address;
            }

            $id_journal         = GeneralLedger::where('transaction_number', $transaction_number)->where('type', 'K')->where('phase', 'opr')->first()->id_journal;
            $journal_set        = JournalSet::where('id_journal', $id_journal)->where('type', 'K')->orderBy('serial_number', 'desc')->get();
            $bank_cash          = BankNCash::all();
            $transaction_name   = Journal::find($id_journal)->name;
            $general_ledger     = GeneralLedger::with(['toCoa'])->where('transaction_number', $query->transaction_number)->where('phase', 'opr')->get();

            foreach ($query->toJournal->toJournalSet as $tax) {
                if ($tax->id_tax_rate) {
                    if ($tax->toTaxRate->toTax->category == 'ppn') {
                        $tax_name = $tax->toTaxRate->name;
                    }
                    //  else {
                    //     $test[] = $z->toTaxRate->toTax->name;
                    // }
                }
            }

            $total_paid = $query->value;

            $data = [
                'title'             => $title,
                'company'           => $company,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'transaction_number' => $transaction_number,
                'receipent'         => $receipent_name,
                'receipent_address' => $receipent_address,
                'transaction_name'  => $transaction_name,
                'description'       => $query->description,
                'tax_name'          => $tax_name,
                'total'             => locale_currency($total_paid, 'ID'),
                'terbilang'         => _numberToWords('id', $total_paid) . ' Rupiah',
                'printed_by'        => $username,
                'printed_date'      => $printed_date,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'finance.invoice.outstanding-invoice-receive-id', 'A4', 'potrait', $data);
            // return;
            $pdfOutput = PdfClass::print($data['title'], 'finance.invoice.outstanding-invoice-receive-id', 'A4', 'potrait', $data);

            $fileName = $data['transaction_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:print_invoice', 1, [
                'finance:transaction_number' => $transaction_number,
                'title'                      => $data['title'],
                'printed_date'               => $printed_date,
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            ActivityLogHelper::log('finance:print_invoice', 0, ['message' => 'Transaction Number Not Found!']);
            return ApiResponseClass::throw('Transaction Number Not Found!', 400);
        }
    }

    public function outstanding_receive(Request $request)
    {
        $transaction_number = $request->transaction_number;

        $data       = [];
        $details    = [];
        $detailsRaw = [];
        $amount     = [];
        $total_paid = 0;

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();

        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = Receipts::with(['toJournal',])->where('transaction_number', $transaction_number)->where('status', 'valid')->first();
        $company        = get_arrangement('company_name');
        $receipent_name     = NULL;
        $receipent_address  = NULL;
        $transaction_name   = NULL;
        $tax_name           = NULL;
        $paid_to            = 'Unknown Destination Payment';


        if ($query) {
            switch ($query->record_type) {
                case 'cash':
                    $record_type = 'kas';
                    $paid_to = 'Kas';
                    break;
                case 'petty_cash':
                    $record_type = 'kas kecil';
                    $paid_to = 'Kas Kecil';
                    break;
                default:
                    $record_type = $query->record_type;
                    break;
            }

            // $title          = Str::upper($query->record_type) . ' ' . 'RECEIVE';
            $title          = 'PENERIMAAN' . ' ' . Str::upper($record_type);

            $get_receipent  = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $query->id_kontak)
                ->first();;

            if ($get_receipent) {
                $receipent_name     = $get_receipent->name;
                $receipent_address  = $get_receipent->address;
            }

            $id_journal         = GeneralLedger::where('transaction_number', $query->transaction_number)->where('type', 'K')->where('phase', 'opr')->first()->id_journal;
            $journal_set        = JournalSet::where('id_journal', $id_journal)->where('type', 'K')->orderBy('serial_number', 'desc')->get();
            $bank_cash          = BankNCash::all();
            $transaction_name   = Journal::find($id_journal)->name;
            $general_ledger     = GeneralLedger::with(['toCoa'])->where('transaction_number', $query->transaction_number)->where('phase', 'opr')->get();

            //get  transaction
            foreach ($general_ledger as $gl) {
                //sisi kredit
                if ($gl->type == 'K') {
                    $detailsRaw[$gl->toCoa->name] = $gl->value;
                }
                //sisi debit untuk bank/kas
                if ($gl->type == 'D') {
                    if ($gl->toCoa->toCoaBody->toCoaClasification->slug == 'kas-bank') {
                        $paid_to = $gl->toCoa->name;
                    }
                }
            }

            arsort($detailsRaw);
            $total_paid = array_sum($detailsRaw);

            $details = array_map(function ($nilai_mentah) {
                return locale_currency($nilai_mentah, 'ID');
            }, $detailsRaw);

            // ganti array key pertama dengan nama transaksi
            $detailKeys = array_keys($details);
            $detailValues = array_values($details);
            $detailKeys[0] = 'Nilai Transaksi'; //$transaction_name;
            $details = array_combine($detailKeys, $detailValues);

            $data = [
                'title'             => $title,
                'company'           => $company,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'transaction_number' => $transaction_number,
                'receipent'         => $receipent_name,
                'receipent_address' => $receipent_address,
                'transaction_name'  => $transaction_name,
                'description'       => $query->description,
                'paid_to'           => $paid_to,
                'details'           => $details,
                'total_paid'        => locale_currency($total_paid, 'ID'),
                'terbilang'         => _numberToWords('id', $total_paid) . ' Rupiah',
                'printed_by'        => $username,
                'printed_date'      => $printed_date,
                'transaction'       => [
                    'date'                  => $query->toTransaction->date,
                    'transaction_number'    => $query->toTransaction->transaction_number,
                    'reference_number'      => $query->reference_number,
                    'total'                 => locale_currency($query->toTransaction->value, 'ID'),
                    'description'           => $query->toTransaction->description,
                ],
            ];

            // $pdfOutput = PdfClass::view($data['title'], 'finance.invoice.outstanding-receive-id', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'finance.invoice.outstanding-receive-id', 'A4', 'potrait', $data);

            $fileName = $data['transaction_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:print_invoice', 1, [
                'finance:transaction_number' => $transaction_number,
                'title'                      => $data['title'],
                'printed_date'               => $printed_date,
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return ApiResponseClass::throw('Transaction Number Not Found!', 400);
        }
    }

    public function advancePayment(Request $request)
    {
        // if(isset($request->lang)) {
        //     if ($request->lang == 'id') {
        //         return $this->advancePaymentID($request);
        //     } else {
        //         return $this->advancePaymentEN($request);
        //     }
        // } else {
        return $this->advancePaymentID($request);
        // }
    }

    public function advancePaymentID(Request $request)
    {
        $transaction_number = $request->transaction_number;
        $id_kontak          = $request->id_kontak;

        $history            = [];
        $data               = [];
        $amount             = [];
        $saldo              = 0;
        $saldo_akhir        = 0;


        $nowUtc             = Carbon::now();
        $nowGmt8            = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date       = $nowGmt8->toDateTimeString();
        $company            = get_arrangement('company_name');
        $title              = 'TANDA TERIMA DEPOSIT';
        $username           = User::find(auth()->id())->name ?? 'invalid user';
        // $query          = LiabilityDetail::where('transaction_number', $transaction_number)->first();
        $query              = LiabilityDetail::with(['toLiability'])->where('transaction_number', $transaction_number)->first();
        // dd($query);

        if ($query) {

            Carbon::setLocale('id');
            //hitung dpp;
            $dpp = 999; // $total_invoice - $total_termin;
            $dpp_lain = round($dpp * 11 / 12);
            $ppn_dibebaskan = ($dpp * 11 / 100);
            $pph = floor($dpp * 1.5 / 100);
            $sisa_tagihan = ($dpp - $pph);


            //ambil history transaksi
            if (empty($id_kontak)) {
                return Response::json(['success' => false, 'message' => 'ID Kontak Not Found!'], 400);
            }

            // ndak bisa pasang limit di sini
            // $query_history = Liability::with(['toLiabilityDetail' => function ($query) use ($transaction_number) {
            //     $query->where('transaction_number', '!=', $transaction_number)->where('status', 'valid');
            // }])->where('id_kontak', $id_kontak)->get();

            // limit
            $query_history = Liability::where('id_kontak', $id_kontak)->get();
            $query_history->each(function ($item) use ($transaction_number) {
                $item->load(['toLiabilityDetail' => function ($query) use ($transaction_number) {
                    $query->where('transaction_number', '!=', $transaction_number)
                        ->where('status', 'valid')
                        ->limit(15);
                }]);
            });

            foreach ($query_history as $liability) {
                foreach ($liability->toLiabilityDetail as $detail) {
                    $value = (float) $detail->value;
                    $category = $detail->category;

                    // Hitung saldo
                    if ($category === 'penerimaan') {
                        $saldo += $value;
                    } else {
                        $saldo -= $value;
                    }

                    $history[] = [
                        'category'          => $category,
                        'transaction_number' => $detail->transaction_number,
                        'date'              => $detail->date ?? $liability->date ?? null,
                        'value'             => locale_currency($value, 'ID'),
                        'description'       => $detail->description ?? '-',
                        'saldo'             => locale_currency($saldo, 'ID')
                    ];
                }
            }

            $saldo_akhir = $saldo + $query->value;

            // ambil rek tujuan deposit
            $paid_to    = null;
            $id_journal = get_arrangement('advance_payment_deposit_journal');
            $query_journal = JournalSet::where('id_journal', $id_journal)->where('type', 'D')->first();

            if ($query_journal) {
                $paid_to = $query_journal->toCoa->name;
            }

            $data = [
                'title'             => $title,
                'company'           => $company,
                'transaction_number' => $transaction_number,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'receipent'         => $query->toLiability->toKontak->name,
                'receipent_address' => $query->toLiability->toKontak->address,
                'paid_to'           => $paid_to,

                'description'       => $query->description,
                'total'             => locale_currency($query->value, 'ID'),
                'terbilang'         => _numberToWords('id', $query->value) . ' Rupiah',

                'history'           => [], //$history,
                'saldo_akhir'       => locale_currency($saldo_akhir, 'ID'),

                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'finance.invoice.advance-payment-id', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'finance.invoice.advance-payment-id', 'A4', 'potrait', $data);

            $fileName = $data['transaction_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:advance_payment_print', 1, [
                'finance:transaction_number' => $data['transaction_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'Transaction Number Not Found!'], 404);
        }
    }

    public function downPayment(Request $request)
    {
        // if(isset($request->lang)) {
        //     if ($request->lang == 'id') {
        //         return $this->downPaymentID($request);
        //     } else {
        //         return $this->downPaymentEN($request);
        //     }
        // } else {
        return $this->downPaymentID($request);
        // }
    }

    public function downPaymentID(Request $request)
    {
        $transaction_number = $request->transaction_number;
        $id_kontak          = $request->id_kontak;

        $history            = [];
        $data               = [];
        $amount             = [];
        $saldo              = 0;
        $saldo_akhir        = 0;


        $nowUtc             = Carbon::now();
        $nowGmt8            = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date       = $nowGmt8->toDateTimeString();
        $company            = get_arrangement('company_name');
        $title              = 'TANDA TERIMA UANG MUKA';
        $username           = User::find(auth()->id())->name ?? 'invalid user';
        // $query          = LiabilityDetail::where('transaction_number', $transaction_number)->first();
        $query              = DownPaymentDetails::with(['toDownPayment'])->where('transaction_number', $transaction_number)->first();
        // dd($query);

        if ($query) {

            Carbon::setLocale('id');
            //hitung dpp;
            $dpp = 999; // $total_invoice - $total_termin;
            $dpp_lain = round($dpp * 11 / 12);
            $ppn_dibebaskan = ($dpp * 11 / 100);
            $pph = floor($dpp * 1.5 / 100);
            $sisa_tagihan = ($dpp - $pph);


            // limit
            $query_history = DownPayment::where('id_kontak', $id_kontak)->get();
            $query_history->each(function ($item) use ($transaction_number) {
                $item->load(['toDownPaymentDetail' => function ($query) use ($transaction_number) {
                    $query->where('transaction_number', '!=', $transaction_number)
                        ->where('status', 'valid')
                        ->limit(15);
                }]);
            });

            foreach ($query_history as $downpayment) {
                foreach ($downpayment->toDownPaymentDetail as $detail) {
                    $value = (float) $detail->value;
                    $category = $detail->category;

                    // Hitung saldo
                    if ($category === 'penerimaan') {
                        $saldo += $value;
                    } else {
                        $saldo -= $value;
                    }

                    $history[] = [
                        'category'          => $category,
                        'transaction_number' => $detail->transaction_number,
                        'date'              => $detail->date ?? $liability->date ?? null,
                        'value'             => locale_currency($value, 'ID'),
                        'description'       => $detail->description ?? '-',
                        'saldo'             => locale_currency($saldo, 'ID')
                    ];
                }
            }

            $saldo_akhir = $saldo + $query->value;

            // ambil rek tujuan deposit
            $paid_to    = null;
            $id_journal = get_arrangement('advance_payment_deposit_journal');
            $query_journal = JournalSet::where('id_journal', $id_journal)->where('type', 'D')->first();

            if ($query_journal) {
                $paid_to = $query_journal->toCoa->name;
            }

            $data = [
                'title'             => $title,
                'company'           => $company,
                'transaction_number' => $transaction_number,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'receipent'         => $query->toDownPayment->toKontak->name,
                'receipent_address' => $query->toDownPayment->toKontak->address,
                'paid_to'           => $paid_to,

                'description'       => $query->description,
                'total'             => locale_currency($query->value, 'ID'),
                'terbilang'         => _numberToWords('id', $query->value) . ' Rupiah',

                'history'           => [], //$history,
                'saldo_akhir'       => locale_currency($saldo_akhir, 'ID'),

                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'finance.invoice.advance-payment-id', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'finance.invoice.advance-payment-id', 'A4', 'potrait', $data);

            $fileName = $data['transaction_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:advance_payment_print', 1, [
                'finance:transaction_number' => $data['transaction_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'Transaction Number Not Found!'], 404);
        }
    }
}
