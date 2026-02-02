<?php

namespace App\Http\Controllers\finance;

use App\Classes\PdfClass;
use App\Exports\AssetDepreciationExport;
use App\Exports\JournalInterface\JournalInterfaceExport;
use App\Exports\GeneralLedger\GeneralLedgerExport;
use App\Exports\JournalEntryExport;
use App\Exports\JournalUmumExport;
use App\Http\Controllers\Controller;
use App\Models\finance\AssetCoa;
use App\Models\finance\ClosingDepreciation;
use App\Models\finance\ClosingEntry;
use App\Models\finance\CoaGroup;
use App\Models\finance\GeneralLedger;
use App\Models\finance\ReportMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrialBalanceExport;
use App\Models\finance\Coa;
use App\Models\finance\CoaBody;

class ReportController extends Controller
{
    public function generate_pdf($id, Request $request)
    {
        $begin = Carbon::now();
        $end   = Carbon::now();

        $start_date = $request->startDate ?? $begin->firstOfMonth()->format('Y-m-d');
        $end_date   = $request->endDate ?? $end->format('Y-m-d');
        $year       = Carbon::parse($end_date)->format('Y');

        $report_menu = ReportMenu::with(['toReportTitle'])->findOrFail($id);

        $report_title = $report_menu->toReportTitle;

        $data = [];
        $body = [];
        $cek_sudah_closing = ClosingEntry::where('year', $year)->first();
        $coa_labarugi_berjalan = Coa::find(get_arrangement('equity_coa'))->coa;

        foreach ($report_title as $key => $value) {
            if ($value->toReportBody) {
                $cek_coa_name = '';
                foreach ($value->toReportBody as $key => $value2) {
                    $balance = 0;
                    if ($value2->method === 'range') {
                        $coa_name     = $value2->toCoa->name . ', ' . 'Awal';
                        $cek_coa_name = $value2->toCoa->name;
                        $balance      = _sum_account_saldo($value2, $start_date, $start_date, ['opr', 'int', 'acm', 'tax']);
                    } else if ($value2->method === 'report') {
                        $coa_name     = ReportMenu::find($value2->id_report_menu)->first()->name;
                        $cek_coa_name = '-';
                        $balance = _count_report_menu_total($value2->id_report_menu, $start_date, $end_date);
                    } else if ($value2->method === 'subcoa') {
                        $coa_name = ucwords(strtolower(CoaBody::where('id_coa_body', $value2->id_coa_body)->first()->name));
                        $balance = _count_coa_body($value2->id_coa_body, $start_date, $end_date);
                    } else {
                        $coa_name = $value2->toCoa->name;
                        if ($cek_coa_name == $coa_name) {
                            $coa_name = $value2->toCoa->name . ', ' . 'Akhir';
                        }
                        //jika coanya laba/rugi tahun berjalan dan belum closing
                        if ($value2->toCoa->coa == $coa_labarugi_berjalan) {
                            $balance = _sum_pendapatan_beban($start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                        } else {
                            $balance = _sum_account_saldo($value2, $start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                        }
                    }

                    $body[$value->id_report_title][] = [
                        'name'      => $coa_name,
                        'operation' => $value2->operation,
                        'total'     => $balance
                    ];
                }
            }

            $menu_body = $body[$value->id_report_title] ?? [];

            $data[] = [
                'name'  => $value->name,
                'body'  => $menu_body,
                'type'  => $value->type,
                'total' => $value->type === 'formula' ? _count_report_title_formula($value->id_report_title, $start_date, $end_date) : _count_report_title_total($value->id_report_title, $start_date, $end_date)
            ];
        }

        $result = [
            'data'     => $data,
            'title'    => $report_menu->name,
            'subtitle' => Carbon::parse($end_date)->format('d M Y'),
        ];
        // return view('finance.report.generate-pdf', $result);
        $pdfOutput = PdfClass::view($report_menu->name, 'finance.report.generate-pdf', 'A4', 'potrait', $result);
    }


    public function asset_depreciation(Request $request)
    {
        $year   = Carbon::parse($request->period)->format('Y');
        $month  = Carbon::parse($request->period)->format('m');
        $day    = Carbon::parse($request->period)->endOfMonth()->format('d');

        // $current_date  = Carbon::parse($request->period)->endOfMonth()->format('Y-m-d');

        $lifespan           = (int) get_arrangement('lifespan');

        $asset_coa = AssetCoa::with(['toAssetHead.toAssetItem'])
            ->whereHas('toAssetHead.toAssetItem', function ($query) {
                $query->where('disposal', '0');
            })
            ->get()
            ->map(function ($item) {
                    $item->toAssetHead = $item->toAssetHead->map(function ($head) {
                        $qty = 0;
                        $price = 0;

                        $qty = $head->toAssetItem->sum('qty');
                        $price = $head->toAssetItem->sum('price');

                        $head->qty = $qty;
                        $head->price = $price;

                        unset($head->toAssetItem);

                        return $head;
                    });

                return $item;
            });

        // dd($asset_coa->toArray());
        $data = [];
        foreach ($asset_coa as $value) {
            $items = [];
            if ($value->toAssetHead) {
                foreach ($value->toAssetHead as $head) {
                    $name               = $head->name;
                    $acquisition_date   = $head->tgl;
                    $price              = $head->price;
                    $qty                = $head->qty;
                    $total              = $qty * $price;
                    $rate               = $head->toAssetGroup->rate;
                    $group              = $head->toAssetGroup->name;

                    $start_month        = Carbon::parse($acquisition_date)->startOfMonth();

                    $monthly_data = [];
                    $remaining_value = 0;
                    $depreciation = 0;
                    $total_depreciation = 0;

                    // dd($monthDifference);
                    for ($current_month = 1; $current_month <= $month; $current_month++) {

                        $current_date       = Carbon::parse($year.'-'.$current_month)->endOfMonth()->format('Y-m-d');
                        $dayDifference      = Carbon::parse($acquisition_date)->diffInDays(Carbon::parse($current_date));
                        $monthDifference    = count_cut_off($acquisition_date, $current_date);
                        // $monthDifference    = count_month_by_cut_off($acquisition_date, $current_date);

                        if($current_month >= $monthDifference){
                            // Penyusutan bulanan
                            $depreciation = ($head->price * $rate/100) * (1 / 12);
                            $total_depreciation += $depreciation;
                            $remaining_value = $head->price - $total_depreciation;

                            if ($lifespan > 0) {
                                $depreciation       = ($dayDifference < $lifespan ? 0 : $depreciation);
                                $total_depreciation = ($dayDifference < $lifespan ? 0 : $total_depreciation);
                                $remaining_value = ($dayDifference < $lifespan ? 0 : $remaining_value);
                            }
                            if ($dayDifference < 0) {
                                $depreciation       = 0;
                                $total_depreciation = 0;
                                $remaining_value = 0;
                            }
                        }

                        // Data bulanan
                        $monthly_data[] = [
                            'penyusutan' => round($depreciation, 0),
                            'akum_penyusutan' => round($total_depreciation, 0),
                            'nilai_buku' => round($remaining_value, 0),
                        ];
                    }

                    $items[] = [
                        'name'  => $name,
                        'date'  => $acquisition_date,
                        'qty'   => $qty,
                        'price' => $price,
                        'nilai_perolehan' => $total,
                        'group' => $group,
                        'rate' => $rate/100,
                        'data_penyusutan' => $monthly_data,
                    ];
                }
            }

            $data[] = [
                'asset_coa' => $value->name,
                'items' => $items,
            ];
        }

        $result = [
            'data' => $data,
            'title' => 'Asset Depreciation Yearly',
            'subtitle' => Carbon::parse($request->period)->format('Y'),
        ];

        // $period = '2025-05';
        return
        $exportExcel = new AssetDepreciationExport($result, $request->period);
        $exportExcel->setAllBorder(true);
        $exportExcel->setTitle('Daftar Aset Tetap dan Penyusutan');

        return Excel::download($exportExcel, "Asset_Depreciation_{$year}.xlsx");
    }

    public function balance(Request $request)
    {
        $year         = Carbon::parse($request->period)->format('Y');
        $month        = Carbon::parse($request->period)->format('m');

        $prev_year    = Carbon::parse($request->period)->subMonth()->format('Y');
        $prev_month   = Carbon::parse($request->period)->subMonth()->format('m');

        $start_date   = Carbon::create($year, $month, 1)->startOfDay()->format('Y-m-d');
        $end_date     = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()->format('Y-m-d');

        $periode      = Carbon::parse($request->period)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        $closing_entry = ClosingEntry::with(['toClosingEntryDetail'])->where('month', $prev_month)->where('year', $prev_year)->first();

        // saldo awal
        $saldo_awal = [];
        if ($closing_entry) {
            foreach ($closing_entry->toClosingEntryDetail as $key => $value) {
                if ($value->toCoa->toCoaBody->toCoaClasification->normal_balance == 'D') {
                    $saldo_awal[$value->coa] = [
                        'debit'  => $value->debit - $value->credit,
                        'credit' => 0
                    ];
                } else {
                    $saldo_awal[$value->coa] = [
                        'debit'  => 0,
                        'credit' => $value->credit - $value->debit
                    ];
                }
            }
        } else {
            $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $end_date])->where('phase', 'int')->get();
            foreach ($initial_balance as $key => $value) {
                if ($value->type === 'K') {
                    $saldo_awal[$value->coa] = [
                        'debit'  => 0,
                        'credit' => $value->value
                    ];
                } else {
                    $saldo_awal[$value->coa] = [
                        'debit'  => $value->value,
                        'credit' => 0
                    ];
                }
            }
        }

        $get = CoaGroup::with(['toCoaHead.toCoaBody.toCoa'])->get();

        $data = [];
        foreach ($get as $key => $value) {
            $coa_heads = [];

            if ($value->toCoaHead) {
                foreach ($value->toCoaHead as $key => $value2) {
                    $coa_bodys = [];

                    if ($value2->toCoaBody) {
                        foreach ($value2->toCoaBody as $key => $value3) {
                            $coas              = [];
                            $sum_debit_awal    = [];
                            $sum_credit_awal   = [];
                            $sum_debit_mutasi  = [];
                            $sum_credit_mutasi = [];
                            $sum_debit_akhir   = [];
                            $sum_credit_akhir  = [];

                            if ($value3->toCoa) {
                                foreach ($value3->toCoa as $key => $value4) {
                                    $val_debit  = 0;
                                    $val_credit = 0;

                                    $debit_akhir  =  0;
                                    $credit_akhir =  0;

                                    // saldo awal
                                    $debit_awal  =  $saldo_awal[$value4->coa]['debit'] ?? 0;
                                    $credit_awal =  $saldo_awal[$value4->coa]['credit'] ?? 0;

                                    // mutasi
                                    $general_ledger = $value4->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr','acm']);
                                    foreach ($general_ledger as $key => $value5) {
                                        if ($value5->type === 'D') {
                                            $val_debit = $val_debit + $value5->value;
                                        } else {
                                            $val_credit = $val_credit + $value5->value;
                                        }
                                    }

                                    // saldo akhir
                                    if ($value4->toCoaBody->toCoaClasification->normal_balance == 'D') {
                                        $default = 'D';
                                    } else {
                                        $default = 'K';
                                    }

                                    if ($default == 'D') {
                                        $balance = $debit_awal + ($val_debit - $val_credit);

                                        $debit_akhir  = $balance;
                                        $credit_akhir = 0;
                                    } else {
                                        $balance = $credit_awal + ($val_credit - $val_debit);

                                        $debit_akhir  = 0;
                                        $credit_akhir = $balance;
                                    }

                                    $sum_debit_awal[]    = $debit_awal;
                                    $sum_credit_awal[]   = $credit_awal;
                                    $sum_debit_mutasi[]  = $val_debit;
                                    $sum_credit_mutasi[] = $val_credit;
                                    $sum_debit_akhir[]   = $debit_akhir;
                                    $sum_credit_akhir[]  = $credit_akhir;

                                    if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                                        continue;
                                    }

                                    $coas[] = [
                                        'name' => $value4->name,
                                        'coa'  => $value4->coa,

                                        'debit_awal'  => $debit_awal,
                                        'credit_awal' => $credit_awal,

                                        'debit_mutasi'  => $val_debit,
                                        'credit_mutasi' => $val_credit,

                                        'debit_akhir'  => $debit_akhir,
                                        'credit_akhir' => $credit_akhir
                                    ];
                                }
                            }

                            if (empty($coas)) {
                                continue;
                            }

                            $coa_bodys[] = [
                                'name_body' => $value3->name,
                                'coa_body'  => $value3->coa,

                                'debit_awal'  => array_sum($sum_debit_awal),
                                'credit_awal' => array_sum($sum_credit_awal),

                                'debit_mutasi'  => array_sum($sum_debit_mutasi),
                                'credit_mutasi' => array_sum($sum_credit_mutasi),

                                'debit_akhir'  => array_sum($sum_debit_akhir),
                                'credit_akhir' => array_sum($sum_credit_akhir),

                                'coa'          => $coas
                            ];
                        }
                    }

                    $coa_heads[] = [
                        'name_head' => $value2->name,
                        'coa_head'  => $value2->coa,
                        'coa_body'  => $coa_bodys
                    ];
                }
            }

            $data[] = [
                'name_group'  => $value->name,
                'coa_head'    => $coa_heads
            ];
        }
        return $data;
        // $export = new TrialBalanceExport($data);
        // $export->setPeriode($periode);
        // $export->setTitle($periode);

        // return Excel::download($export, 'TrialBalance-'.$current.'.xlsx');
    }

    public function journal_interface(Request $request)
    {
        $start_date =  $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode      = Carbon::parse($request->end_date)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        $general_ledger = GeneralLedger::with(['toJournal','toTransactionFull'])->where('id_journal', '!=', null)->whereBetween('date', [$start_date, $end_date])->get();

        $data = $general_ledger->groupBy('id_journal')->map(function ($items, $id_journal) {
            // Ambil data yang sama untuk setiap kelompok
            $firstItem = $items->first();

            // Hitung total debit dan kredit
            // $total_debit = $items->where('type', 'D')->sum('value');
            // $total_kredit = $items->where('type', 'K')->sum('value');

            // Looping untuk setiap item dalam jurnal
            return [
                'id_journal' => $id_journal,
                'journal_name' => optional($firstItem->toJournal)->name,
                // 'date' => $firstItem->date,
                'description' => $firstItem->description,
                // 'total_debit' => $total_debit,
                // 'total_kredit' => $total_kredit,
                'transactions' => $items->groupBy('transaction_number')->map(function ($transactions, $transaction_number) {
                    $firstTransaction = $transactions->first();
                    // dd('first',$firstTransaction);
                    return [
                        'transaction_number' => $transaction_number,
                        'date' => $firstTransaction->date,
                        'description' => $firstTransaction->description,
                        'invoice_number' => $firstTransaction->toTransactionFull->invoice_number,
                        'efaktur_number' => $firstTransaction->toTransactionFull->efaktur_number,
                        'from_or_to' => $firstTransaction->toTransactionFull->from_or_to,
                        'value' => $firstTransaction->value,
                        'journals' => $transactions->map(function ($item) {
                            return [
                                'coa' => $item->coa,
                                'name' => $item->toCoa->name,
                                'debit' => $item->type === 'D' ? $item->value : 0,
                                'credit' => $item->type === 'K' ? $item->value : 0,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values()->sortBy('journal_name');

        // dd($data);
        if (empty($data->toArray()) || count($data->toArray()) === 0) {
            return Response::json(['success' => false, 'message' => 'Data masih kosong. Tidak dapat melakukan export!'], 400);
        }

        $export = new JournalInterfaceExport($data);
        $export->setPeriode($periode);
        $response = Excel::download($export, 'JournalInterface-'.$current.'.xlsx');

        return $response;
    }

    public function journal_umum(Request $request)
    {
        $start_date =  $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode      = Carbon::parse($request->end_date)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        $general_ledger = GeneralLedger::with(['toJournalEntry'])
            ->where('id_journal', '=', null)
            ->whereBetween('date', [$start_date, $end_date])
            ->whereHas('toJournalEntry', function ($query) {
                $query->whereRaw("LEFT(transaction_number, 3) = 'JU-'");
            })
            ->get();

        $data = $general_ledger->groupBy('transaction_number')->map(function ($transactions, $transaction_number) {
            // Ambil data yang sama untuk setiap kelompok
            $firstTransaction = $transactions->first();
            return [
                'transaction_number' => $transaction_number,
                'date' => $firstTransaction->date,
                'description' => $firstTransaction->description,
                'amount' => $firstTransaction->value,
                'journals' => $transactions->map(function ($item) {
                    return [
                        'coa' => $item->coa,
                        'name' => $item->toCoa->name,
                        'debit' => $item->type === 'D' ? $item->value : 0,
                        'credit' => $item->type === 'K' ? $item->value : 0,
                    ];
                })->values(),
            ];
        })->values()->sortBy('date');

        if (empty($data->toArray()) || count($data->toArray()) === 0) {
            return Response::json(['success' => false, 'message' => 'Data masih kosong. Tidak dapat melakukan export!'], 400);
        }
        dd($data);
        $export = new JournalUmumExport($data->toArray());
        $export->setPeriode($periode);
        $response = Excel::download($export, 'JournalUmum-'.$current.'.xlsx');

        return $response;
    }

    public function general_ledger(Request $request)
    {
        $start_date =  $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode      = Carbon::parse($request->end_date)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        $general_ledgers = GeneralLedger::with([
            'toCoa.toCoaBody.toCoaHead.toCoaGroup'
        ])->whereBetween('date', [$start_date, $end_date])->get();

        // Grouping berdasarkan id_coa_group

        $data = $general_ledgers->groupBy(function ($ledger) {
            return data_get($ledger, 'toCoa.toCoaBody.toCoaHead.toCoaGroup.id_coa_group');
        })->map(function (Collection $groupItems, $id_coa_group) {
            return [
                'id_coa_group' => $id_coa_group,
                'coa_group' => data_get($groupItems->first(), 'toCoa.toCoaBody.toCoaHead.toCoaGroup.coa'),
                'group_name' => data_get($groupItems->first(), 'toCoa.toCoaBody.toCoaHead.toCoaGroup.name'),
                'groups' => $groupItems->groupBy(function ($ledger) {
                    return data_get($ledger, 'toCoa.id_coa');
                })->map(function (Collection $coaItems, $id_coa) {
                    $normal_balance = data_get($coaItems->first(), 'toCoa.toCoaBody.toCoaClasification.normal_balance');
                    $saldo = 0;

                    return [
                        'id_coa_head' => data_get($coaItems->first(), 'toCoa.toCoaBody.toCoaHead.id_coa_head'),
                        'coa_head' => data_get($coaItems->first(), 'toCoa.toCoaBody.toCoaHead.coa'),
                        'coa_head_name' => data_get($coaItems->first(), 'toCoa.toCoaBody.toCoaHead.name'),
                        'id_coa_body' => data_get($coaItems->first(), 'toCoa.toCoaBody.id_coa_body'),
                        'coa_body' => data_get($coaItems->first(), 'toCoa.toCoaBody.coa'),
                        'coa_body_name' => data_get($coaItems->first(), 'toCoa.toCoaBody.name'),
                        'id_coa' => $id_coa,
                        'coa' => data_get($coaItems->first(), 'toCoa.coa'),
                        'coa_name' => data_get($coaItems->first(), 'toCoa.name'),
                        'transactions' => $coaItems->map(function ($ledger) use (&$saldo, $normal_balance) {
                            $kredit = $ledger->type === 'K' ? $ledger->value : 0;
                            $debit = $ledger->type === 'D' ? $ledger->value : 0;

                            // Perhitungan saldo berdasarkan normal balance
                            if ($normal_balance === 'D') {
                                $saldo += $debit - $kredit;
                            } else {
                                $saldo += $kredit - $debit;
                            }

                            return [
                                'transaction_number' => $ledger->transaction_number,
                                'ref_number' => $ledger->reference_number,
                                'date' => $ledger->date,
                                'description' => $ledger->description,
                                'credit' => $kredit,
                                'debit' => $debit,
                                'balance' => $saldo,
                            ];
                        })->values()->sortBy('id_coa'),
                    ];
                })->values()->sortBy('id_coa_body'),
            ];
        });

        // dd($data);

        $export = new GeneralLedgerExport($data);
        $export->setPeriode($periode);

        // return Excel::download($export, 'JournalEntry-'.$current.'.xlsx');
        $response = Excel::download($export, 'JournalEntry-'.$current.'.xlsx');

        return $response;
    }

    public function journal_entry_old(Request $request)
    {
        $start_date =  $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode      = Carbon::parse($request->end_date)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        $general_ledger = GeneralLedger::with(['toCoa'])->whereBetween('date', [$start_date, $end_date])->get();

        $data = $general_ledger->groupBy('transaction_number')->map(function ($items, $transaction_number) {
            // Ambil data yang sama untuk setiap kelompok
            $firstItem      = $items->first();

            // Hitung total debit dan kredit
            $total_debit    = $items->where('type', 'D')->sum('value');
            $total_kredit   = $items->where('type', 'K')->sum('value');

            // looping return nya
            return [
                'date'              => $firstItem->date,
                'transaction_number'=> $transaction_number,
                'description'       => $firstItem->description,
                "total_debit"       => $total_debit,
                "total_kredit"      => $total_kredit,
                //looping di journals untuk detail general ledger
                'journals' => $items->map(function ($item) {
                    return [
                        'coa'   => $item->coa,
                        'name'  => $item->toCoa->name,
                        'type'  => $item->type,
                        'amount' => $item->value,
                    ];
                })->values(),
            ];
        })->values();

        $dataArray = $data->toArray();
        dd($dataArray);
        $export = new JournalEntryExport($dataArray);
        $export->setPeriode($periode);
        $export->setTitle($periode);

        // return Excel::download($export, 'JournalEntry-'.$current.'.xlsx');
        $response = Excel::download($export, 'JournalEntry-'.$current.'.xlsx');

        // Add custom headers to the response
        // $response->headers->set('Access-Control-Allow-Origin', '*'); // Ganti '*' dengan domain yang diperlukan
        // $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        // $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');

        return $response;
    }

}
