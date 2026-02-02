<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Classes\PdfClass;
use App\Exports\AssetDepreciationExport;
use App\Exports\Finance\ARControl\ARControlExport;
use App\Exports\GeneralLedger\GeneralLedgerExport;
use App\Exports\JournalEntryExport;
use App\Exports\JournalInterface\JournalInterfaceExport;
use App\Exports\JournalUmumExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\api\finance\GeneralLedgerController;
use App\Models\finance\AssetCoa;
use App\Models\finance\ClosingEntry;
use App\Models\finance\CoaGroup;
use App\Models\finance\GeneralLedger;
use App\Models\finance\ReportMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrialBalanceExport;
use App\Helpers\ActivityLogHelper;
use App\Models\finance\Coa;
use App\Models\finance\CoaBody;
use App\Models\finance\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportExportController extends Controller
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
                'name'             => $value->name,
                'body'             => $menu_body,
                'type'             => $value->type,
                'display_currency' => $value->display_currency,
                'total'            => $value->type === 'formula' ? _count_report_title_formula($value->id_report_title, $start_date, $end_date) : _count_report_title_total($value->id_report_title, $start_date, $end_date)
            ];
        }

        $result = [
            'data'     => $data,
            'title'    => $report_menu->name,
            'subtitle' => Carbon::parse($end_date)->format('d M Y'),
        ];
        // dd($result);
        $pdfOutput = PdfClass::print($report_menu->name, 'finance.report.generate-pdf', 'A4', 'potrait', $result);

        $fileName = $report_menu->name . '-' . now()->format('YmdHis') . '.pdf';

        ActivityLogHelper::log('finance:export_report', 1, [
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'title'          => $report_menu->name,
        ]);

        return response($pdfOutput, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename=' . $fileName);
    }

    public function asset_depreciation(Request $request)
    {
        $year  = Carbon::parse($request->period)->format('Y');
        $month = Carbon::parse($request->period)->format('m');
        $day   = Carbon::parse($request->period)->endOfMonth()->format('d');

        $set_date = $year . '-' . $month . '-' . $day;

        $asset_coa = AssetCoa::with(['toAssetHead.toAssetItem'])->whereHas('toAssetHead.toAssetItem', function ($query) {
            $query->where('disposal', '0');
        })->get();

        $data = [];

        foreach ($asset_coa as $key => $value) {
            $item = [];
            if ($value->toAssetHead) {
                foreach ($value->toAssetHead as $key => $value2) {
                    if ($value2->toAssetItem) {
                        foreach ($value2->toAssetItem as $key => $value3) {
                            $first_date   = $value3->toAssetHead->tgl;
                            $current_date = Carbon::parse($request->period)->endOfMonth()->format('Y-m-d');

                            $dayDifference = Carbon::parse($first_date)->diffInDays(Carbon::parse($current_date));
                            $lifespan      = get_arrangement('lifespan');

                            // $monthDifference = count_month_by_cut_off($first_date, $set_date);
                            $monthDifference    = count_cut_off($first_date, $set_date);

                            $rate = ($value3->toAssetHead->toAssetGroup->rate / 100);

                            $depreciation        = round(($value3->total * $rate) * (1 / 12), 0);
                            $depreciation_amount = ($depreciation * $monthDifference);
                            $gl                  = ($value3->total - $depreciation_amount);

                            if ($lifespan > 0) {
                                $depreciation        = ($dayDifference < $lifespan ? 0 : $depreciation);
                                $depreciation_amount = ($dayDifference < $lifespan ? 0 : $depreciation_amount);
                                $gl                  = ($dayDifference < $lifespan ? 0 : $gl);
                            }

                            $item[] = [
                                'identity_number'     => $value3->identity_number,
                                'name'                => $value3->toAssetHead->name,
                                'date'                => $value3->toAssetHead->tgl,
                                'qty'                 => $value3->qty,
                                'price'               => $value3->price,
                                'total'               => $value3->total,
                                'group'               => $value3->toAssetHead->toAssetGroup->name,
                                'rate'                => $value3->toAssetHead->toAssetGroup->rate . '%',
                                'depreciation'        => $depreciation,
                                'depreciation_amount' => $depreciation_amount,
                                'gl'                  => $gl,
                            ];
                        }
                    }
                }
            }

            $data[] = [
                'group' => $value->name,
                'item'  => $item
            ];
        }

        $result = [
            'data'     => $data,
            'title'    => 'Asset Depreciation',
            'subtitle' => Carbon::parse($request->period)->format('M Y'),
        ];

        //        PdfClass::view('Asset Depreciation', 'finance.report.asset-depreciation', 'A4', 'landscape', $result);
        $pdfOutput = PdfClass::print('AssetDepreciation', 'finance.report.asset-depreciation', 'A4', 'landscape', $result);

        $fileName = 'AssetDepreciation-' . now()->format('YmdHis') . '.pdf';

        ActivityLogHelper::log('finance:export_asset_depreciation', 1, [
            'period' => $request->period,
            'title'  => $result['title'],
        ]);

        return response($pdfOutput, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename=' . $fileName);
    }

    public function asset_depreciation_excel(Request $request)
    {
        $year   = Carbon::parse($request->period)->format('Y');
        $month  = Carbon::parse($request->period)->format('m');
        $day    = Carbon::parse($request->period)->endOfMonth()->format('d');

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
                    // $price = $head->toAssetItem->sum('price');
                    $price = $head->toAssetItem[0]->price;

                    $head->qty = $qty;
                    $head->price = $price;
                    $head->total = $qty * $price;

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
                    // $total              = $qty * $price;
                    $total              = $head->total;
                    $rate               = $head->toAssetGroup->rate;
                    $group              = $head->toAssetGroup->name;

                    $start_month        = Carbon::parse($acquisition_date)->startOfMonth();

                    $monthly_data = [];
                    $remaining_value = 0;
                    $depreciation = 0;
                    $total_depreciation = 0;

                    // dd($monthDifference);
                    for ($current_month = 1; $current_month <= $month; $current_month++) {

                        $current_date       = Carbon::parse($year . '-' . $current_month)->endOfMonth()->format('Y-m-d');
                        $dayDifference      = Carbon::parse($acquisition_date)->diffInDays(Carbon::parse($current_date));
                        $monthDifference    = count_cut_off($acquisition_date, $current_date);
                        // $monthDifference    = count_month_by_cut_off($acquisition_date, $current_date);

                        if ($current_month >= $monthDifference) {
                            // Penyusutan bulanan
                            $depreciation = ($head->price * $rate / 100) * (1 / 12);
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
                        'rate' => $rate / 100,
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

        $exportExcel = new AssetDepreciationExport($result, $request->period);
        $exportExcel->setAllBorder(true);
        $exportExcel->setTitle('Daftar Aset Tetap dan Penyusutan');

        ActivityLogHelper::log('finance:export_asset_depreciation', 1, [
            'period' => $request->period,
            'title'  => $result['title'],
        ]);

        return Excel::download($exportExcel, "Asset_Depreciation_{$year}.xlsx");
    }

    public function balance(Request $request)
    {
        $period         = $request->period;
        $type           = $request->type;
        $jenis_closing  = 'yearly'; //nanti ini ambil dari preferences;

        if ($jenis_closing == 'yearly') {
            if ($type == 'month') {
                return $this->balance_bulanan($period);
            } else {
                return $this->balance_tahunan($period);
            }
        } else {
            // jika closing bulanan;
        }
    }

    public function balance_bulanan($period)
    {
        $title        = 'TRIAL BALANCE';
        $periode      = Carbon::parse($period)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        $year         = Carbon::parse($period)->format('Y');
        $month        = Carbon::parse($period)->format('m');

        $prev_year    = Carbon::parse($period)->subMonth()->format('Y');
        $prev_month   = Carbon::parse($period)->subMonth()->format('m');

        $start_date   = Carbon::create($year, $month, 1)->startOfDay()->format('Y-m-d');
        $end_date     = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()->format('Y-m-d');;

        $closing_entry = ClosingEntry::with(['toClosingEntryDetail'])->where('month', $prev_month)->where('year', $prev_year)->first();

        $controller = new GeneralLedgerController();
        $prev_balances = $controller->previousBalance($period);

        // saldo awal
        $saldo_awal = [];
        $saldo_awal_current = [];
        if ($closing_entry) {
            foreach ($closing_entry->toClosingEntryDetail as $key => $value) {
                if ($value->toCoa->toCoaBody->toCoaClasification->accrual == 'accumulated') {
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
            }
        }
        // else {
        // saldo awal current month
        $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $end_date])->where('phase', 'int')->where('jb', 0)->get();
        foreach ($initial_balance as $key => $value) {
            if ($value->type === 'K') {
                $saldo_awal_current[$value->coa] = [
                    'debit'  => 0,
                    'credit' => $value->value
                ];
            } else {
                $saldo_awal_current[$value->coa] = [
                    'debit'  => $value->value,
                    'credit' => 0
                ];
            }
        }
        // }

        if ($month != '01') {
            foreach ($saldo_awal_current as $coa => $values) {
                //Cari apakah coa ada di prev_balances
                $prev_balance = collect($prev_balances)->firstWhere('coa', $coa);

                if ($prev_balance) {
                    // Jika coa ada di prev_balances, tambahkan nilai credit dan debit
                    $saldo_awal[$coa] = [
                        'credit' => $values['credit'] + $prev_balance['credit'],
                        'debit' => $values['debit'] + $prev_balance['debit'],
                    ];
                } else {
                    // Jika coa tidak ada di prev_balances, gunakan nilai dari saldo_awal_current
                    $saldo_awal[$coa] = [
                        'credit' => $values['credit'],
                        'debit' => $values['debit'],
                    ];
                }
            }
        }

        // Memastikan nilai dari prev_balances yang tidak ada di saldo_awal_current juga ditambahkan
        foreach ($prev_balances as $balance) {
            if (!array_key_exists($balance['coa'], $saldo_awal)) {
                $saldo_awal[$balance['coa']] = [
                    'credit' => $balance['credit'],
                    'debit' => $balance['debit'],
                ];
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
                                    $general_ledger = $value4->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'acm'])->where('jb', 0);
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

                                    // if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                                    //     continue;
                                    // }

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

                            // if (empty($coas)) {
                            //     continue;
                            // }

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
        // return $data;
        $export = new TrialBalanceExport($data);
        $export->setTitle($title);
        $export->setPeriode($periode);
        // return Excel::download($export, 'TrialBalance-'.$current.'.xlsx');

        $file_name = str_replace(' ', '', $title) . '-' . $current . '.xlsx';

        ActivityLogHelper::log('finance:export_balance', 1, [
            'period' => $period,
            'title'  => $title,
        ]);

        return Excel::download($export, $file_name, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Disposition' => 'attachment; filename="\$file_name\"',
        ]);
    }

    public function balance_tahunan($period)
    {
        $title        = 'TRIAL BALANCE';
        // $periode      = Carbon::parse($period)->format('Y');
        $current      = Carbon::now()->format('YmdHis');

        $year       = Carbon::createFromFormat('Y', $period)->format('Y');
        $prev_year  = Carbon::createFromFormat('Y', $period)->subYear()->format('Y');

        $start_date = Carbon::create($year, 1, 1)->startOfYear()->startOfDay()->format('Y-m-d');
        $end_date   = Carbon::create($year, 12, 31)->endOfYear()->endOfDay()->format('Y-m-d');

        $periode      = Carbon::create($year, 12, 31)->endOfYear()->endOfDay()->format('d F Y');

        // $controller = new GeneralLedgerController();
        // $prev_balances = $controller->previousBalance($period);

        $saldo_awal = [];

        // ambil initial balance
        $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $end_date])->where('phase', 'int')->where('jb', 0)->get();
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
                                    $val_debit      = 0;
                                    $val_credit     = 0;

                                    $debit_akhir    =  0;
                                    $credit_akhir   =  0;

                                    // saldo awal
                                    $debit_awal     =  $saldo_awal[$value4->coa]['debit'] ?? 0;
                                    $credit_awal    =  $saldo_awal[$value4->coa]['credit'] ?? 0;

                                    // mutasi
                                    $general_ledger = $value4->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'acm'])->where('jb', 0);
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

                                    // if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                                    //     continue;
                                    // }

                                    $coas[] = [
                                        'name'          => $value4->name,
                                        'coa'           => $value4->coa,

                                        'debit_awal'    => $debit_awal,
                                        'credit_awal'   => $credit_awal,

                                        'debit_mutasi'  => $val_debit,
                                        'credit_mutasi' => $val_credit,

                                        'debit_akhir'   => $debit_akhir,
                                        'credit_akhir'  => $credit_akhir
                                    ];
                                }
                            }

                            // if (empty($coas)) {
                            //     continue;
                            // }

                            $coa_bodys[] = [
                                'name_body'     => $value3->name,
                                'coa_body'      => $value3->coa,

                                'debit_awal'    => array_sum($sum_debit_awal),
                                'credit_awal'   => array_sum($sum_credit_awal),

                                'debit_mutasi'  => array_sum($sum_debit_mutasi),
                                'credit_mutasi' => array_sum($sum_credit_mutasi),

                                'debit_akhir'   => array_sum($sum_debit_akhir),
                                'credit_akhir'  => array_sum($sum_credit_akhir),

                                'coa'           => $coas
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
        // return $data;
        $export = new TrialBalanceExport($data);
        $export->setTitle($title);
        $export->setPeriode($periode);
        // return Excel::download($export, 'TrialBalance-'.$current.'.xlsx');

        $file_name = str_replace(' ', '', $title) . '-' . $current . '.xlsx';

        ActivityLogHelper::log('finance:export_balance', 1, [
            'period' => $period,
            'title'  => $title,
        ]);

        return Excel::download($export, $file_name, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Disposition' => 'attachment; filename="\$file_name\"',
        ]);
    }

    public function journal_interface(Request $request)
    {
        $start_date =  $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode      = Carbon::parse($request->end_date)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        // Optimasi: Query dengan JOIN untuk menghindari N+1 problem
        $general_ledger = DB::connection('finance')->table('general_ledgers as gl')
            ->select(
                'gl.id_general_ledger',
                'gl.id_journal',
                'gl.transaction_number',
                'gl.date',
                'gl.description',
                'gl.coa',
                'gl.type',
                'gl.value',
                'j.name as journal_name',
                'c.name as coa_name',
                'tf.invoice_number',
                'tf.efaktur_number',
                'tf.from_or_to as tf_from_or_to',
                't.from_or_to as t_from_or_to'
            )
            ->join('journal as j', 'gl.id_journal', '=', 'j.id_journal')
            ->join('coa as c', 'gl.coa', '=', 'c.coa')
            ->leftJoin('transaction_full as tf', 'gl.transaction_number', '=', 'tf.transaction_number')
            ->leftJoin('transaction as t', 'gl.transaction_number', '=', 't.transaction_number')
            ->whereNotNull('gl.id_journal')
            ->whereBetween('gl.date', [$start_date, $end_date])
            ->whereNull('gl.deleted_at')
            ->orderBy('j.name', 'asc')
            ->orderBy('gl.date', 'asc')
            ->get();

        // Group dan transformasi data
        $data = $general_ledger->groupBy('id_journal')->map(function ($items, $id_journal) {
            $firstItem = $items->first();

            return [
                'id_journal' => $id_journal,
                'journal_name' => $firstItem->journal_name,
                'description' => $firstItem->description,
                'transactions' => $items->groupBy('transaction_number')->map(function ($transactions, $transaction_number) {
                    $firstTransaction = $transactions->first();
                    
                    // Prioritas: TransactionFull dulu, baru Transaction
                    $from_or_to = $firstTransaction->tf_from_or_to ?? $firstTransaction->t_from_or_to;
                    
                    return [
                        'transaction_number' => $transaction_number,
                        'date' => $firstTransaction->date,
                        'description' => $firstTransaction->description,
                        'invoice_number' => $firstTransaction->invoice_number,
                        'efaktur_number' => $firstTransaction->efaktur_number,
                        'from_or_to' => $from_or_to,
                        'value' => $firstTransaction->value,
                        'journals' => $transactions->map(function ($item) {
                            return [
                                'coa' => $item->coa,
                                'name' => $item->coa_name,
                                'debit' => $item->type === 'D' ? $item->value : 0,
                                'credit' => $item->type === 'K' ? $item->value : 0,
                            ];
                        })->values(),
                    ];
                })->values()->sortBy('date'),
            ];
        })->values()->sortBy('journal_name');

        if (empty($data->toArray()) || count($data->toArray()) === 0) {
            return ApiResponseClass::throw('Data masih kosong. Tidak dapat melakukan export!', 204);
        }

        $export = new JournalInterfaceExport($data);
        $export->setPeriode($periode);
        $response = Excel::download($export, 'JournalInterface-' . $current . '.xlsx');

        ActivityLogHelper::log('finance:export_journal_interface', 1, [
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'period'     => $periode,
        ]);

        return $response;
    }

    public function journal_umum(Request $request)
    {
        $start_date =  $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode      = Carbon::parse($request->end_date)->format('F Y');
        $current      = Carbon::now()->format('YmdHis');

        // Optimasi: Query dengan JOIN untuk menghindari N+1 problem
        $general_ledger = DB::connection('finance')->table('general_ledgers as gl')
            ->select(
                'gl.id_general_ledger',
                'gl.transaction_number',
                'gl.date',
                'gl.description',
                'gl.coa',
                'gl.type',
                'gl.value',
                'c.name as coa_name'
            )
            ->join('coa as c', 'gl.coa', '=', 'c.coa')
            ->join('journal_entries as je', 'gl.transaction_number', '=', 'je.transaction_number')
            ->whereNull('gl.id_journal')
            ->whereBetween('gl.date', [$start_date, $end_date])
            ->whereRaw("LEFT(gl.transaction_number, 3) = 'JU-'")
            ->whereNull('gl.deleted_at')
            ->orderBy('gl.date', 'asc')
            ->get();

        // Group dan transformasi data
        $data = $general_ledger->groupBy('transaction_number')->map(function ($transactions, $transaction_number) {
            $firstTransaction = $transactions->first();
            return [
                'transaction_number' => $transaction_number,
                'date' => $firstTransaction->date,
                'description' => $firstTransaction->description,
                'amount' => $firstTransaction->value,
                'journals' => $transactions->map(function ($item) {
                    return [
                        'coa' => $item->coa,
                        'name' => $item->coa_name,
                        'debit' => $item->type === 'D' ? $item->value : 0,
                        'credit' => $item->type === 'K' ? $item->value : 0,
                    ];
                })->values(),
            ];
        })->values()->sortBy('date');

        if (empty($data->toArray()) || count($data->toArray()) === 0) {
            return ApiResponseClass::throw('Data masih kosong. Tidak dapat melakukan export!', 204);
        }

        $export = new JournalUmumExport($data->toArray());
        $export->setPeriode($periode);
        $response = Excel::download($export, 'JournalUmum-' . $current . '.xlsx');

        ActivityLogHelper::log('finance:export_general_entry', 1, [
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'period'     => $periode,
        ]);

        return $response;
    }

    public function general_ledger(Request $request)
    {
        $start_date = $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);
        $periode    = Carbon::parse($request->end_date)->format('F Y');
        $current    = Carbon::now()->format('YmdHis');

        // 1. Build query dengan join langsung untuk menghindari N+1
        $query = DB::connection('finance')->table('general_ledgers as gl')
            ->select(
                'gl.transaction_number',
                'gl.reference_number',
                'gl.date',
                'gl.description',
                'gl.type',
                'gl.value',
                'gl.id_kontak',
                'c.id_coa',
                'c.coa',
                'c.name as coa_name',
                'cb.id_coa_body',
                'cb.coa as coa_body',
                'cb.name as coa_body_name',
                'ch.id_coa_head',
                'ch.coa as coa_head',
                'ch.name as coa_head_name',
                'cg.id_coa_group',
                'cg.coa as coa_group',
                'cg.name as group_name',
                'cc.normal_balance'
            )
            ->join('coa as c', 'gl.coa', '=', 'c.coa')
            ->join('coa_body as cb', 'c.id_coa_body', '=', 'cb.id_coa_body')
            ->join('coa_head as ch', 'cb.id_coa_head', '=', 'ch.id_coa_head')
            ->join('coa_group as cg', 'ch.id_coa_group', '=', 'cg.id_coa_group')
            ->join('coa_clasification as cc', 'cb.id_coa_clasification', '=', 'cc.id_coa_clasification')
            ->whereBetween('gl.date', [$start_date, $end_date])
            ->whereNull('gl.deleted_at')
            ->orderBy('cg.coa', 'asc')
            ->orderBy('c.coa', 'asc')
            ->orderBy('gl.date', 'asc');

        // 2. Filter by COA jika ada
        if ($request->has('coa')) {
            $coas = explode(',', $request->coa);
            $query->whereIn('c.coa', $coas);
        }

        $general_ledgers = $query->get();

        if ($general_ledgers->isEmpty()) {
            return ApiResponseClass::throw('Data masih kosong. Tidak dapat melakukan export!', 204);
        }

        // 3. Batch load kontak dari database mysql
        $kontakIds = $general_ledgers->pluck('id_kontak')->unique()->filter();
        $kontaks = DB::connection('mysql')->table('kontak')
            ->whereIn('id_kontak', $kontakIds)
            ->pluck('name', 'id_kontak');

        // 4. Grouping data dengan collection
        $data = $general_ledgers->groupBy('id_coa_group')->map(function ($groupItems, $id_coa_group) use ($kontaks) {
            $firstGroupItem = $groupItems->first();

            return [
                'id_coa_group' => $id_coa_group,
                'coa_group'    => $firstGroupItem->coa_group,
                'group_name'   => $firstGroupItem->group_name,
                'groups'       => $groupItems->groupBy('id_coa')->map(function ($coaItems, $id_coa) use ($kontaks) {
                    $firstCoaItem = $coaItems->first();
                    $normal_balance = $firstCoaItem->normal_balance;
                    $saldo = 0;

                    return [
                        'id_coa_head'   => $firstCoaItem->id_coa_head,
                        'coa_head'      => $firstCoaItem->coa_head,
                        'coa_head_name' => $firstCoaItem->coa_head_name,
                        'id_coa_body'   => $firstCoaItem->id_coa_body,
                        'coa_body'      => $firstCoaItem->coa_body,
                        'coa_body_name' => $firstCoaItem->coa_body_name,
                        'id_coa'        => $id_coa,
                        'coa'           => $firstCoaItem->coa,
                        'coa_name'      => $firstCoaItem->coa_name,
                        'transactions'  => $coaItems->map(function ($ledger) use (&$saldo, $normal_balance, $kontaks) {
                            $kredit = $ledger->type === 'K' ? $ledger->value : 0;
                            $debit  = $ledger->type === 'D' ? $ledger->value : 0;

                            if ($normal_balance === 'D') {
                                $saldo += $debit - $kredit;
                            } else {
                                $saldo += $kredit - $debit;
                            }

                            return [
                                'contact'            => $kontaks[$ledger->id_kontak] ?? '-',
                                'transaction_number' => $ledger->transaction_number,
                                'ref_number'         => $ledger->reference_number,
                                'date'               => $ledger->date,
                                'description'        => $ledger->description,
                                'credit'             => $kredit,
                                'debit'              => $debit,
                                'balance'            => $saldo,
                            ];
                        })->values()
                    ];
                })->values()->sortBy('coa')
            ];
        })->sortBy('coa_group')->values();

        $export = new GeneralLedgerExport($data);
        $export->setPeriode($periode);

        $response = Excel::download($export, 'GeneralLedger-' . $current . '.xlsx');

        ActivityLogHelper::log('finance:export_general_ledger', 1, [
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'period'     => $periode,
        ]);

        return $response;
    }

    public function journal_entry(Request $request)
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
                'transaction_number' => $transaction_number,
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

        $export = new JournalEntryExport($dataArray);
        $export->setPeriode($periode);
        $export->setTitle($periode);

        $file_name = 'JournalEntry-' . $current . '.xlsx';

        ActivityLogHelper::log('finance:export_journal_entry', 1, [
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'period'     => $periode,
        ]);

        // return Excel::download($export, 'JournalEntry-'.$current.'.xlsx');
        // $response = Excel::download($export, 'JournalEntry-'.$current.'.xlsx');
        return Excel::download($export, $file_name, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Disposition' => 'attachment; filename=' . $file_name,
        ]);
    }

    public function ar_control(Request $request)
    {
        $current            = Carbon::now()->format('YmdHis');

        $typeRequest        = $request->type;
        $fullyPaidRequest   = $request->boolean('fully_paid');
        $idKontakRequest    = $request->id_kontak;

        if (!isset($request->type)) {
            die('ssss');
        }

        if ($typeRequest == 'piutang') {
            $category = 'penerimaan';
            $paymentRelation = 'toReceipts';
            $title = 'Account Receivable Control';
            $file_name = 'AR-Control-' . $current . '.xlsx';
        } else {
            $category = 'pengeluaran';
            $paymentRelation = 'toExpenditure';
            $title = 'Account Payable Control';
            $file_name = 'AP-Control-' . $current . '.xlsx';
        }

        //ambil semua transaksi
        $allTransactions = Transaction::select(
            'id_kontak',
            'date',
            'transaction_number',
            'reference_number',
            'description',
            'value',
        )
            // Filter berdasarkan id_kontak HANYA jika request-nya ada
            ->when($idKontakRequest, function ($query, $kontakId) {
                return $query->where('id_kontak', $kontakId);
            })
            ->with([
                'toKontak',
                $paymentRelation => function ($query) {
                    $query->where('status', 'valid');
                }
            ])
            ->where('category', $category)
            ->where('status', 'valid')
            ->get();

        //group by (`reference_number`) untuk kalkulasi
        $groupedByReference = $allTransactions->groupBy('reference_number');

        //restrukturisasi arraynya
        $transactions = $groupedByReference->map(function (Collection $transactionsInGroup, $refNumber) {
            $firstTransaction = $transactionsInGroup->first();
            $allPayments = $transactionsInGroup->pluck('toReceipts')->flatten()->unique('id_receipt');
            $totalInvoiceValue = $transactionsInGroup->sum('value');
            $totalPaid = $allPayments->sum('value');
            $remaining = $totalInvoiceValue - $totalPaid;

            return [
                'id_kontak'           => $firstTransaction->id_kontak,
                'kontak_name'         => $firstTransaction->toKontak->name,
                'date'                => $firstTransaction->date,
                'transaction_number'  => $firstTransaction->transaction_number,
                'reference_number'    => $refNumber,
                'description'         => $firstTransaction->description,
                'total_invoice'       => (float) $totalInvoiceValue,
                'total_paid'          => (float) $totalPaid,
                'remaining'           => (float) $remaining,
                'is_fully_paid'       => $remaining <= 0,
                'payments'            => $allPayments->map(function ($payment) {
                    return [
                        'date'               => $payment->date,
                        'transaction_number' => $payment->transaction_number,
                        'description'        => $payment->description,
                        'total'              => (float) $payment->value,
                    ];
                })->values(),
            ];
        })->values();

        //filter lagi  by  request `fully_paid` kalau ada
        $filteredTransactions = $transactions->when($fullyPaidRequest !== null, function ($collection) use ($fullyPaidRequest) {
            $isFullyPaid = filter_var($fullyPaidRequest, FILTER_VALIDATE_BOOLEAN);

            return $collection->filter(function ($project) use ($isFullyPaid) {
                return $project['is_fully_paid'] === $isFullyPaid;
            });
        });

        //group hasil akhir berdasarkan `id_kontak`
        $groupedByKontak = $filteredTransactions->groupBy('id_kontak');

        //output finalnya
        $finalOutput = $groupedByKontak->map(function ($contactTransactions, $kontakId) {
            return [
                'id_kontak'    => $kontakId,
                'kontak_name'  => $contactTransactions->first()['kontak_name'] ?? 'N/A',
                'transactions' => $contactTransactions->values(),
            ];
        })->values();

        if (!count($finalOutput)) {
            return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 204);
        }

        $export = new ARControlExport($finalOutput);
        $export->setTitle($title);

        // return Excel::download($export, 'JournalEntry-'.$current.'.xlsx');
        // $response = Excel::download($export, 'JournalEntry-'.$current.'.xlsx');
        return Excel::download($export, $file_name, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Disposition' => 'attachment; filename=' . $file_name,
        ]);
    }
}
