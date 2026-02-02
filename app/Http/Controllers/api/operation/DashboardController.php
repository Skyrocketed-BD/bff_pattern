<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OperationController;
use App\Models\operation\Kontraktor;
use App\Models\operation\ShippingInstruction;
use App\Models\operation\StokEfo;
use App\Models\operation\StokEto;
use App\Models\operation\StokInPit;
use App\Models\operation\StokPsi;
use App\Models\main\User;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    public $user;
    public $kontraktor;
    public $id_kontraktor;


    public function __construct()
    {
        $this->user          = Auth::user();
        $this->kontraktor    = User::with(['toKontraktor'])->whereIdUsers($this->user->id_users)->first();
        $this->id_kontraktor = $this->kontraktor->id_kontraktor ?? null;
    }

    public function index(Request $request)
    {
        // begin:: filter
        $date_1     = Carbon::now();
        $date_2     = Carbon::now();
        $start_date     = $date_1->firstOfMonth()->format('Y-m-d');
        $yesterday_date = Carbon::yesterday()->format('Y-m-d');
        $current_date   = $date_2->format('Y-m-d');

        $current_year   = Carbon::now()->format('Y');
        $request_year   = Carbon::createFromFormat('Y', $request->period)->format('Y');

        if ($current_year != $request_year) {
            $date_1     = Carbon::createFromFormat('Y', $request->period)->endOfYear();
            $date_2     = Carbon::createFromFormat('Y', $request->period)->endOfYear();
            $start_date     = Carbon::createFromFormat('Y', $request->period)->firstOfMonth()->format('Y-m-d');
            $yesterday_date = Carbon::parse($date_2)->subDay()->format('Y-m-d');
            $current_date   = $date_2;
            // dd($date_1,$date_1->year, $start_date,$yesterday_date,$current_date   );
        }


        $period = CarbonPeriod::create($start_date, $current_date);
        $dates  = [];
        $day    = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
            $day[]   = $date->format('d');
        }

        $month = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
        ];
        // end:: filter

        // ini untuk data transaksi
        $outstanding = Transaction::whereYear('date', $date_1->year)->whereMonth('date', $date_1->month)->where('status', 'valid')->limit(2)->get();
        $full        = TransactionFull::whereYear('date', $date_1->year)->whereMonth('date', $date_1->month)->where('status', 'valid')->limit(2)->get();
        $receipts    = Receipts::whereYear('date', $date_1->year)->whereMonth('date', $date_1->month)->where('status', 'valid')->limit(2)->get();
        $expenditure = Expenditure::whereYear('date', $date_1->year)->whereMonth('date', $date_1->month)->where('status', 'valid')->limit(2)->get();

        $transactions = [];
        foreach ($outstanding as $key => $value) {
            $transactions[] = [
                'transaction_number' => $value->transaction_number,
                'type'               => ucfirst($value->toJournal->alocation),
                'date'               => $value->date,
                'from_or_to'         => $value->from_or_to,
                'value'              => $value->value,
                'description'        => $value->description,
                'cash_flow'          => $value->toJournal->category == 'penerimaan' ? 'receive' : 'expense'
            ];
        }

        foreach ($full as $key => $value) {
            $transactions[] = [
                'transaction_number' => $value->transaction_number,
                'type'               => ucfirst($value->record_type),
                'date'               => $value->date,
                'from_or_to'         => $value->from_or_to,
                'value'              => $value->value,
                'description'        => $value->description,
                'cash_flow'          => $value->category == 'penerimaan' ? 'receive' : 'expense'
            ];
        }

        foreach ($receipts as $key => $value) {
            $transactions[] = [
                'transaction_number' => $value->transaction_number,
                'type'               => ucfirst($value->toJournal->alocation),
                'date'               => $value->date,
                'from_or_to'         => $value->receive_from,
                'value'              => $value->value,
                'description'        => $value->description,
                'cash_flow'          => $value->toJournal->category == 'penerimaan' ? 'receive' : 'expense'
            ];
        }

        foreach ($expenditure as $key => $value) {
            $transactions[] = [
                'transaction_number' => $value->transaction_number,
                'type'               => ucfirst($value->toJournal->alocation),
                'date'               => $value->date,
                'from_or_to'         => $value->outgoing_to,
                'value'              => $value->value,
                'description'        => $value->description,
                'cash_flow'          => $value->toJournal->category == 'penerimaan' ? 'receive' : 'expense'
            ];
        }

        // untuk data bank dan kas
        $bank_and_cash = BankNCash::with(['toCoa'])->get();

        $bank = [];
        $cash = [];
        $series = [];
        foreach ($bank_and_cash as $key => $value) {
            $sum_bank_cash_val  = _sum_coa_saldo($value->toCoa, $start_date, $current_date);
            // $sum_bank_cash_prev = _sum_coa_saldo($value->toCoa, $start_date, $yesterday_date);
            // $sum_bank_cash_current = _sum_coa_saldo($value->toCoa, $current_date, $current_date);

            if ($value->type == 'bank') {
                $bank[] = [
                    'value'    => $sum_bank_cash_val,
                    // 'previous' => $sum_bank_cash_prev,
                    // 'current'  => $sum_bank_cash_current
                ];
            } else {
                $cash[] = [
                    'value'    => $sum_bank_cash_val,
                    // 'previous' => $sum_bank_cash_prev,
                    // 'current'  => $sum_bank_cash_current
                ];
            }

            foreach ($dates as $key => $value2) {
                $series[$value->type][$value->toCoa->coa][Carbon::parse($value2)->format('d')] = _sum_coa_saldo($value->toCoa, $value2, $value2);
            }
        }

        $series_bank = [];
        $series_cash = [];
        foreach ($series as $key => $value) {
            foreach ($value as $key2 => $value2) {
                if ($key == 'bank') {
                    $series_bank[] = $value2;
                } else {
                    $series_cash[] = $value2;
                }
            }
        }

        $total_bank = [];
        $total_cash = [];
        foreach ($day as $key => $value) {
            $total_bank[] = array_sum(array_column($series_bank, $value));
            $total_cash[] = array_sum(array_column($series_cash, $value));
        }

        $val_bank     = array_sum(array_column($bank, 'value'));
        // $prev_bank    = array_sum(array_column($bank, 'previous'));
        // $current_bank = array_sum(array_column($bank, 'current'));

        // $percent_bank = round(calculate_percentage($prev_bank, $current_bank, false));


        $val_cash     = array_sum(array_column($cash, 'value'));
        // $prev_cash    = array_sum(array_column($cash, 'previous'));
        // $current_cash = array_sum(array_column($cash, 'current'));

        // $percent_cash = round(calculate_percentage($prev_cash, $current_cash, false));

        $current_balance = [
            'value'    => $val_cash + $val_bank,
            // 'previous' => $sum_kas_bank_prev,
            // 'percent'  => $percent_kas_bank
        ];


        $bank = [
            'value'    => $val_bank,
            // 'previous' => $prev_bank,
            // 'percent'  => $percent_bank,
            'chart'    => [
                'labels' => $day,
                'series' => $total_bank,
            ]
        ];

        $cash = [
            'value'    => $val_cash,
            // 'previous' => $prev_cash,
            // 'percent'  => $percent_cash,
            'chart'    => [
                'labels' => $day,
                'series' => $total_cash,
            ]
        ];



        $val_receipts_1     = Receipts::whereBetween('date', [$start_date, $current_date])->where('status', 'valid')->sum('value');
        // $prev_receipts_1    = Receipts::whereBetween('date', [$start_date, $yesterday_date])->where('status', 'valid')->sum('value');
        // $current_recepits_1 = Receipts::whereBetween('date', [$current_date, $current_date])->where('status', 'valid')->sum('value');

        $val_receipts_2     = TransactionFull::whereBetween('date', [$start_date, $current_date])->where('category', 'penerimaan')->where('status', 'valid')->sum('value');
        // $prev_receipts_2    = TransactionFull::whereBetween('date', [$start_date, $yesterday_date])->where('category', 'penerimaan')->where('status', 'valid')->sum('value');
        // $current_recepits_2 = TransactionFull::whereBetween('date', [$current_date, $current_date])->where('category', 'penerimaan')->where('status', 'valid')->sum('value');

        $val_receipts  = ($val_receipts_1 + $val_receipts_2);
        // $prev_receipts = ($prev_receipts_1 + $prev_receipts_2);
        // $current_recepits = ($current_recepits_1 + $current_recepits_2);

        // $percent_receipts = round(calculate_percentage($prev_receipts, $current_recepits, false));

        $series_receipts = [];
        foreach ($dates as $key => $value) {
            $s_receipts = (Receipts::whereYear('date', $request_year)->whereDate('date', $value)->where('status', 'valid')->sum('value') + TransactionFull::whereYear('date', $request_year)->whereDate('date', $value)->where('category', 'penerimaan')->where('status', 'valid')->sum('value'));
            $series_receipts[] = $s_receipts;
        }

        $month_receipts = [];
        foreach ($month as $key => $value) {
            $m_receipts = (Receipts::whereYear('date', $request_year)->whereMonth('date', $key)->where('status', 'valid')->sum('value') + TransactionFull::whereYear('date', $request_year)->whereMonth('date', $key)->where('category', 'penerimaan')->where('status', 'valid')->sum('value'));
            $month_receipts[] = $m_receipts;
        }

        $receive = [
            'value'    => $val_receipts,
            // 'previous' => $prev_receipts,
            // 'percent'  => $percent_receipts,
            'chart'    => [
                'labels' => $day,
                'series' => $series_receipts,
            ]
        ];

        $val_expenditure_1      = Expenditure::whereBetween('date', [$start_date, $current_date])->where('status', 'valid')->sum('value');
        // $prev_expenditure_1     = Expenditure::whereBetween('date', [$start_date, $yesterday_date])->where('status', 'valid')->sum('value');
        // $current_expenditure_1  = Expenditure::whereBetween('date', [$current_date, $current_date])->where('status', 'valid')->sum('value');

        $val_expenditure_2      = TransactionFull::whereBetween('date', [$start_date, $current_date])->where('category', 'pengeluaran')->where('status', 'valid')->sum('value');
        // $prev_expenditure_2     = TransactionFull::whereBetween('date', [$start_date, $yesterday_date])->where('category', 'pengeluaran')->where('status', 'valid')->sum('value');
        // $current_expenditure_2  = TransactionFull::whereBetween('date', [$current_date, $current_date])->where('category', 'pengeluaran')->where('status', 'valid')->sum('value');

        $val_expenditure  = ($val_expenditure_1 + $val_expenditure_2);
        // $prev_expenditure = ($prev_expenditure_1 + $prev_expenditure_2);
        // $current_expenditure = ($current_expenditure_1 + $current_expenditure_2);

        // $percent_expenditure = round(calculate_percentage($prev_expenditure, $current_expenditure, false));

        $series_expenditure = [];
        foreach ($dates as $key => $value) {
            $s_expenditure = (Expenditure::whereYear('date', $request_year)->whereDate('date', $value)->where('status', 'valid')->sum('value') + TransactionFull::whereYear('date', $request_year)->whereDate('date', $value)->where('category', 'pengeluaran')->where('status', 'valid')->sum('value'));

            $series_expenditure[] = $s_expenditure;
        }

        $month_expenditure = [];
        foreach ($month as $key => $value) {
            $m_expenditure = (Expenditure::whereYear('date', $request_year)->whereMonth('date', $key)->where('status', 'valid')->sum('value') + TransactionFull::whereYear('date', $request_year)->whereMonth('date', $key)->where('category', 'pengeluaran')->where('status', 'valid')->sum('value'));

            $month_expenditure[] = $m_expenditure;
        }

        $expense = [
            'value'    => $val_expenditure,
            // 'previous' => $prev_expenditure,
            // 'percent'  => $percent_expenditure,
            'chart'    => [
                'labels' => $day,
                'series' => $series_expenditure,
            ]
        ];

        // untuk utang piutang
        $piutang = CoaClasification::with(['toCoaBody.toCoa'])->find(9);
        $payable_val    = [];
        $payable_prev   = [];
        $payable_current = [];
        $payable_series = [];
        $month_payable  = [];
        foreach ($piutang->toCoaBody as $key => $value) {
            if ($value->toCoa) {
                foreach ($value->toCoa as $key => $value2) {
                    $payable_val[]  = _sum_coa_saldo($value2, $start_date, $current_date);
                    // $payable_prev[] = _sum_coa_saldo($value2, $start_date, $yesterday_date);
                    // $payable_current[] = _sum_coa_saldo($value2, $current_date, $current_date);

                    foreach ($dates as $key => $value3) {
                        $payable_series[Carbon::parse($value3)->format('d')][] = _sum_coa_saldo($value2, $value3, $value3);
                    }

                    foreach ($month as $key => $value3) {
                        $start = Carbon::create(date('Y'), $key)->startOfMonth()->format('Y-m-d');
                        $end   = Carbon::create(date('Y'), $key)->lastOfMonth()->format('Y-m-d');

                        $month_payable[$key][] = _sum_coa_saldo($value2, $start, $end);
                    }
                }
            }
        }

        foreach ($payable_series as $key => $value) {
            $payable_series[$key] = array_sum($value);
        }

        foreach ($month_payable as $key => $value) {
            $month_payable[$key] = array_sum($value);
        }

        $sum_payable_val    = array_sum($payable_val);
        // $sum_payable_prev   = array_sum($payable_prev);
        // $sum_payable_current= array_sum($payable_current);

        // $percent_payable    = round(calculate_percentage($sum_payable_prev, $sum_payable_current, false));

        $payable = [
            'value'    => $sum_payable_val,
            // 'previous' => $sum_payable_prev,
            // 'percent'  => $percent_payable,
            'chart'    => [
                'labels' => $day,
                'series' => $payable_series,
            ]
        ];

        $utang = CoaClasification::with(['toCoaBody.toCoa'])->find(2);
        $receivable_val    = [];
        $receivable_prev   = [];
        $receivable_current = [];
        $receivable_series = [];
        $month_receivable  = [];
        foreach ($utang->toCoaBody as $key => $value) {
            if ($value->toCoa) {
                foreach ($value->toCoa as $key => $value2) {
                    $receivable_val[]     = _sum_coa_saldo($value2, $start_date, $current_date);
                    // $receivable_prev[]    = _sum_coa_saldo($value2, $start_date, $yesterday_date);
                    // $receivable_current[] = _sum_coa_saldo($value2, $current_date, $current_date);

                    foreach ($dates as $key => $value3) {
                        $receivable_series[Carbon::parse($value3)->format('d')][] = _sum_coa_saldo($value2, $value3, $value3);
                    }

                    foreach ($month as $key => $value3) {
                        $start = Carbon::create(date('Y'), $key)->startOfMonth()->format('Y-m-d');
                        $end   = Carbon::create(date('Y'), $key)->lastOfMonth()->format('Y-m-d');

                        $month_receivable[$key][] = _sum_coa_saldo($value2, $start, $end);
                    }
                }
            }
        }

        foreach ($receivable_series as $key => $value) {
            $receivable_series[$key] = array_sum($value);
        }

        foreach ($month_receivable as $key => $value) {
            $month_receivable[$key] = array_sum($value);
        }

        $sum_receivable_val     = array_sum($receivable_val);
        // $sum_receivable_prev    = array_sum($receivable_prev);
        // $sum_receivable_current = array_sum($receivable_current);

        // $percent_receivable     = round(calculate_percentage($sum_receivable_prev, $sum_receivable_current, false));

        $receivable = [
            'value'    => $sum_receivable_val,
            // 'previous' => $sum_receivable_prev,
            // 'percent'  => $percent_receivable,
            'chart'    => [
                'labels' => $day,
                'series' => $receivable_series,
            ]
        ];

        $receive_expense = [
            'labels' => array_values($month),
            'series' => [$month_receipts, $month_expenditure],
        ];

        $receivable_payable = [
            'labels' => array_values($month),
            'series' => [array_values($month_receivable), array_values($month_payable)],
        ];

        usort($transactions, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        $data = [
            'transactions'       => $transactions,
            'current_balance'    => $current_balance,
            'bank'               => $bank,
            'cash'               => $cash,
            'receive'            => $receive,
            'expense'            => $expense,
            'payable'            => $payable,
            'receivable'         => $receivable,
            'receive_expense'    => $receive_expense,
            'receivable_payable' => $receivable_payable,
        ];

        return ApiResponseClass::sendResponse($data, 'Dashboard Finance Retrieved Successfully');
    }

    public function contractor_summary(Request $request)
    {
        $period = $request->period;
        $type   = $request->type;
        $id_kontraktor = $this->id_kontraktor;

        $query = Kontraktor::query();

        $query->with(['toShippingInstruction.toProvision.toProvisionCoa']);

        if ($id_kontraktor !== null){
            $query->whereHas('toShippingInstruction', function ($q) use ($id_kontraktor) {
                $q->whereKontraktor($id_kontraktor);
            });
        }

        if ($type === 'month') {
            $query->whereHas('toShippingInstruction.toProvision.toProvisionCoa', function ($q) use ($period) {
                [$year, $month] = explode('-', $period);
                $q->whereYear('date', $year)->whereMonth('date', $month);
            });
        } else {
            $query->whereHas('toShippingInstruction.toProvision.toProvisionCoa', function ($q) use ($period) {
                $q->whereYear('date', $period);
            });
        }

        $get = $query->get();

        $result = [];
        $data = [];
        foreach ($get as $key => $value) {
            $tonage_final = [];

            if ($value->toShippingInstruction->count() > 0) {

                foreach ($value->toShippingInstruction as $key2 => $value2) {

                    if ($value2->toProvision) {
                        foreach ($value2->toProvision->toProvisionCoa as $key3 => $value3) {
                            $tonage_final[] = $value3->tonage_final;
                        }
                    }
                }
            }

            $result[] = [
                'id_kontraktor' => $value->id_kontraktor,
                'company'       => $value->company,
                'tonage'        => round(array_sum($tonage_final), 2),
                'wmt_tonage'    => round(array_sum($tonage_final), 2),
            ];

            usort($result, function ($a, $b) {
                return $b['tonage'] <=> $a['tonage'];
            });
        }

        $data = [
            'details' => $result,
            'total' => array_sum(array_column($result, 'wmt_tonage')),
        ];

        return ApiResponseClass::sendResponse($data, 'Contractor Summary');
    }

    public function product_shipping_old(Request $request)
    {
        $queryPSI = StokPsi::query();
        $querySI  = ShippingInstruction::query();

        // $query->with(['toShippingInstruction.toProvision.toProvisionCoa']);

        if ($request->month) {
            $queryPSI->whereMonth('date', $request->month);
            $querySI->whereMonth('load_date_start', $request->month);
            // $query->whereHas('toShippingInstruction', function ($q) use ($request) {
            //     $q->whereMonth('departure_date', $request->month);
            // });
        }

        if ($request->year) {
            $queryPSI->whereYear('date', $request->year);
            $querySI->whereYear('load_date_start', $request->year);
            // $query->whereHas('toShippingInstruction', function ($q) use ($request) {
            //     $q->whereYear('departure_date', $request->year);
            // });
        }

        $psiTonage = $queryPSI->sum('tonage');
        $siTonage = $querySI->sum('gross_tonage');

        $data = [
            'psi' => $psiTonage,
            'si'  => $siTonage,
        ];

        return ApiResponseClass::sendResponse($data, 'Product Shipping Summary');
    }

    public function product_shipping_old_dua(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year ?? now()->year;

        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $dates = collect();
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates->push($date->toDateString());
        }

        $psiData = StokPsi::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->selectRaw('date(date) as date, SUM(tonage) as tonage')
            ->groupByRaw('date(date)')
            ->pluck('tonage', 'date');

        $siData = ShippingInstruction::whereMonth('load_date_start', $month)
            ->whereYear('load_date_start', $year)
            ->selectRaw('date(load_date_start) as date, SUM(gross_tonage) as tonage')
            ->groupByRaw('date(load_date_start)')
            ->pluck('tonage', 'date');

        $psiTonnage = $dates->map(function ($date) use ($psiData) {
            return [
                'labels' => $date,
                'series' => (float) ($psiData[$date] ?? 0),
            ];
        });

        $siTonnage = $dates->map(function ($date) use ($siData) {
            return [
                'labels' => $date,
                'series' => (float) ($siData[$date] ?? 0),
            ];
        });

        return response()->json([
            'psi_tonage' => $psiTonnage,
            'si_tonage' => $siTonnage,
        ]);
    }

    public function product_shipping_old_tiga(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year ?? now()->year;

        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $dates = collect();
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates->push($date->toDateString());
        }

        $psiData = StokPsi::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->selectRaw('date(date) as date, SUM(tonage) as tonage')
            ->groupByRaw('date(date)')
            ->pluck('tonage', 'date');

        $siData = ShippingInstruction::whereMonth('load_date_start', $month)
            ->whereYear('load_date_start', $year)
            ->selectRaw('date(load_date_start) as date, SUM(gross_tonage) as tonage')
            ->groupByRaw('date(load_date_start)')
            ->pluck('tonage', 'date');

        $psiTonage = [
            'labels' => $dates,
            'series' => $dates->map(fn($date) => (float) ($psiData[$date] ?? 0)),
        ];

        $siTonage = [
            'labels' => $dates,
            'series' => $dates->map(fn($date) => (float) ($siData[$date] ?? 0)),
        ];

        return response()->json([
            'psi_tonage' => $psiTonage,
            'si_tonage' => $siTonage,
        ]);
    }

    public function product_shipping(Request $request)
    {
        $period = $request->period;
        $type = $request->type;
        $id_kontraktor = $this->id_kontraktor;

        $dates = collect();
        $psiData = collect();
        $siData = collect();

        if ($type === 'month') {
            [$year, $month] = explode('-', $period);
            $start = Carbon::createFromFormat('Y-m-d', "$year-$month-01");

            if ($start->isSameMonth(now())) {
                $end = now()->copy()->startOfDay();
            } else {
                $end = $start->copy()->endOfMonth();
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dates->push($date->toDateString());
            }

            $queryPsi   = StokPsi::whereMonth('date', $month)->whereYear('date', $year);
            $querySi    = ShippingInstruction::whereMonth('load_date_start', $month)->whereYear('load_date_start', $year);

            if ($id_kontraktor !== null) {
                $queryPsi->where('id_kontraktor', $id_kontraktor);
                $querySi->where('id_kontraktor', $id_kontraktor);
            }

            // $psiData = StokPsi::whereMonth('date', $month)
            $psiData = $queryPsi
                ->selectRaw('date(date) as date, SUM(tonage) as tonage')
                ->groupByRaw('date(date)')
                ->pluck('tonage', 'date');

            // $siData = ShippingInstruction::whereMonth('load_date_start', $month)
            $siData = $querySi
                ->selectRaw('date(load_date_start) as date, SUM(gross_tonage) as tonage')
                ->groupByRaw('date(load_date_start)')
                ->pluck('tonage', 'date');

            $psiTonageValues = $dates->map(fn($date) => (float) ($psiData[$date] ?? 0));
            $siTonageValues = $dates->map(fn($date) => (float) ($siData[$date] ?? 0));

            $psiTonage = [
                'labels' => $dates,
                'series' => $dates->map(fn($date) => (float) ($psiData[$date] ?? 0)),
                'total'  => round($psiTonageValues->sum(),2),
            ];

            $siTonage = [
                'labels' => $dates,
                'series' => $dates->map(fn($date) => (float) ($siData[$date] ?? 0)),
                'total'  => round($siTonageValues->sum(),2)
            ];

        } elseif ($type === 'year') {
            $year = (int) $period;

            $months = collect(range(1, 12))->map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT));

            $queryPsi   = StokPsi::whereYear('date', $year);
            $querySi    = ShippingInstruction::whereYear('load_date_start', $year);

            if ($id_kontraktor !== null) {
                $queryPsi->where('id_kontraktor', $id_kontraktor);
                $querySi->where('id_kontraktor', $id_kontraktor);
            }

            // $psiData = StokPsi::whereYear('date', $year)
            $psiData = $queryPsi
                ->selectRaw('MONTH(date) as month, SUM(tonage) as tonage')
                ->groupByRaw('MONTH(date)')
                ->pluck('tonage', 'month');

            // $siData = ShippingInstruction::whereYear('load_date_start', $year)
            $siData = $querySi
                ->selectRaw('MONTH(load_date_start) as month, SUM(gross_tonage) as tonage')
                ->groupByRaw('MONTH(load_date_start)')
                ->pluck('tonage', 'month');

            $psiTonageValues = $months->map(fn($m) => (float) ($psiData[(int) $m] ?? 0));
            $siTonageValues = $months->map(fn($m) => (float) ($siData[(int) $m] ?? 0));

            $psiTonage = [
                'labels' => $months->map(fn($m) => "$year-$m"),
                'series' => $months->map(fn($m) => (float) ($psiData[(int) $m] ?? 0)),
                'total'  => round($psiTonageValues->sum(),2),
            ];

            $siTonage = [
                'labels' => $months->map(fn($m) => "$year-$m"),
                'series' => $months->map(fn($m) => (float) ($siData[(int) $m] ?? 0)),
                'total'  => round($siTonageValues->sum(),2)
            ];
        } else {
            return response()->json(['error' => 'Invalid type parameter'], 400);
        }

        return response()->json([
            'psi_tonage' => $psiTonage,
            'si_tonage' => $siTonage,
        ]);
    }

    public function product_inventory(Request $request)
    {
        $period = $request->period;
        $type   = $request->type;
        $id_kontraktor = $this->id_kontraktor;

        $dates  = collect();
        $labels = collect();
        $etoData= collect();
        $efoData = collect();
        $pitData = collect();

        if ($type === 'month') {
            [$year, $month] = explode('-', $period);
            $start = Carbon::createFromFormat('Y-m-d', "$year-$month-01");

            if ($start->isSameMonth(now())) {
                $end = now()->copy()->startOfDay();
            } else {
                $end = $start->copy()->endOfMonth();
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dates->push($date->toDateString());
                $labels->push($date->format('d'));
            }

            $queryEfo   = StokEfo::whereMonth('date_in', $month)->whereYear('date_in', $year);
            $queryEto   = StokEto::whereMonth('date_in', $month)->whereYear('date_in', $year);
            $queryPit   = StokInPit::whereMonth('date', $month)->whereYear('date', $year);

            if ($id_kontraktor !== null) {
                $queryEfo->where('id_kontraktor', $id_kontraktor);
                $queryEto->where('id_kontraktor', $id_kontraktor);
                $queryPit->where('id_kontraktor', $id_kontraktor);
            }

            $efoData = $queryEfo
                ->selectRaw('DATE(date_in) as date, SUM(tonage) as tonage')
                ->groupByRaw('DATE(date_in)')
                ->pluck('tonage', 'date');

            $etoData = $queryEto
                ->selectRaw('DATE(date_in) as date, SUM(tonage) as tonage')
                ->groupByRaw('DATE(date_in)')
                ->pluck('tonage', 'date');

            $pitData = $queryPit
                ->selectRaw('DATE(date) as date, SUM(tonage) as tonage')
                ->groupByRaw('DATE(date)')
                ->pluck('tonage', 'date');

            $efoTonageValues = $dates->map(fn($date) => (float) ($efoData[$date] ?? 0));
            $etoTonageValues = $dates->map(fn($date) => (float) ($etoData[$date] ?? 0));
            $pitTonageValues = $dates->map(fn($date) => (float) ($pitData[$date] ?? 0));

            $efoTonage = [
                // 'labels' => $dates,
                'series' => $dates->map(fn($date) => (float) ($efoData[$date] ?? 0)),
                'total'  => round($efoTonageValues->sum(),2),
            ];

            $etoTonage = [
                // 'labels' => $dates,
                'series' => $dates->map(fn($date) => (float) ($etoData[$date] ?? 0)),
                'total'  => round($etoTonageValues->sum(),2),
            ];

            $pitTonage = [
                // 'labels' => $dates,
                'series' => $dates->map(fn($date) => (float) ($pitData[$date] ?? 0)),
                'total'  => round($pitTonageValues->sum(),2),
            ];

            // $labels = $dates;

        } elseif ($type === 'year') {
            $year = (int) $period;

            // Buat daftar bulan (01 - 12)
            $months = collect(range(1, 12))->map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT));

            $queryEfo   = StokEfo::whereYear('date_in', $year);
            $queryEto   = StokEto::whereYear('date_in', $year);
            $queryPit   = StokInPit::whereYear('date', $year);

            if ($id_kontraktor !== null) {
                $queryEfo->where('id_kontraktor', $id_kontraktor);
                $queryEto->where('id_kontraktor', $id_kontraktor);
                $queryPit->where('id_kontraktor', $id_kontraktor);
            }

            // Ambil tonnage per bulan
            $efoData = $queryEfo
                ->selectRaw('MONTH(date_in) as month, SUM(tonage) as tonage')
                ->groupByRaw('MONTH(date_in)')
                ->pluck('tonage', 'month');

            $etoData = $queryEto
                ->selectRaw('MONTH(date_in) as month, SUM(tonage) as tonage')
                ->groupByRaw('MONTH(date_in)')
                ->pluck('tonage', 'month');

            $pitData = $queryPit
                ->selectRaw('MONTH(date) as month, SUM(tonage) as tonage')
                ->groupByRaw('MONTH(date)')
                ->pluck('tonage', 'month');

            $efoTonageValues = $months->map(fn($m) => (float) ($efoData[(int) $m] ?? 0));
            $etoTonageValues = $months->map(fn($m) => (float) ($etoData[(int) $m] ?? 0));
            $pitTonageValues = $months->map(fn($m) => (float) ($pitData[(int) $m] ?? 0));

            $efoTonage = [
                // 'labels' => $months->map(fn($m) => "$year-$m"),
                'series' => $months->map(fn($m) => (float) ($efoData[(int) $m] ?? 0)),
                'total'  => round($efoTonageValues->sum(),2),
            ];

            $etoTonage = [
                // 'labels' => $months->map(fn($m) => "$year-$m"),
                'series' => $months->map(fn($m) => (float) ($etoData[(int) $m] ?? 0)),
                'total'  => round($etoTonageValues->sum(),2),
            ];

            $pitTonage = [
                // 'labels' => $months->map(fn($m) => "$year-$m"),
                'series' => $months->map(fn($m) => (float) ($pitData[(int) $m] ?? 0)),
                'total'  => round($pitTonageValues->sum(),2),
            ];

            // $labels = $months->map(fn($m) => "$year-$m");
            $labels = $months->map(function ($m) {
                return Carbon::createFromFormat('m', $m)->format('M');
            });

        } else {
            return response()->json(['error' => 'Invalid type parameter'], 400);
        }

        return response()->json([
            'labels'        => $labels,
            'efo_tonage'    => $efoTonage,
            'eto_tonage'    => $etoTonage,
            'pit_tonage'    => $pitTonage,
        ]);
    }

    public function product_grade(Request $request)
    {
        $period = $request->period;
        $type   = $request->type;
        $id_kontraktor = $this->id_kontraktor;

        if ($type === 'month') {
            // Format: 2025-05
            [$year, $month] = explode('-', $period);
            $start_date = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $end_date = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        } else {
            // Format: 2025
            $start_date = Carbon::create($period, 1, 1)->startOfYear()->toDateString();
            $end_date = now()->toDateString();
        }

        $request->start_date = $start_date;
        $request->end_date   = $end_date;
        if ($id_kontraktor !== null) {
            $request->id_kontraktor = $id_kontraktor;
        }

        $stokGlobal = new StokGlobalController();
        $data = json_decode($stokGlobal->index($request)->getContent(), true);
        unset($data['data']['items']);
        unset($data['data']['total']);

        return response()->json([
            'data'   => $data['data'],
        ]);
    }

}
