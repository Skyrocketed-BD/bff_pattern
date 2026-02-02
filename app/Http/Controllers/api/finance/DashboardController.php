<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\finance\BankNCash;
use App\Models\finance\CoaClasification;
use App\Models\finance\Expenditure;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Receipts;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *  path="/dashboard/finance",
     *  summary="Dashboard Finance",
     *  tags={"Finance - Dashboard"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */

    protected   $month = [
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


    // NOTE //,
    // ==================================================
    // ini hitungannya adalah dari awal periode sampe tanggal saat ini (atau tahunan)
    // karena di detail chartnya sudah ada data bulanannya
    // begini mo dulu
    // ===================================================
    public function index() {}

    public function transaction(Request $request)
    {
        $period = $this->getPeriodDates(
            $request->input('type', 'year_to_date'),
            $request->input('period', date('Y'))
        );

        $startDate = $period['start_date'];
        $endDate   = $period['end_date'];

        // ini untuk data transaksi
        $outstanding = Transaction::whereBetween('date', [$startDate, $endDate])->where('status', 'valid')->limit(3)->orderBy('date', 'desc')->get();
        $full        = TransactionFull::whereBetween('date', [$startDate, $endDate])->where('status', 'valid')->limit(3)->orderBy('date', 'desc')->get();
        $receipts    = Receipts::whereBetween('date', [$startDate, $endDate])->where('status', 'valid')->limit(3)->orderBy('date', 'desc')->get();
        $expenditure = Expenditure::whereBetween('date', [$startDate, $endDate])->where('status', 'valid')->limit(3)->orderBy('date', 'desc')->get();

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

        $data = [
            'transactions'       => $transactions,
        ];

        return ApiResponseClass::sendResponse($data, 'Dashboard Finance Retrieved Successfully');
    }

    public function bankCash(Request $request)
    {
        $period = $this->getPeriodDates(
            $request->input('type', 'year'),
            $request->input('period', date('Y'))
        );

        $startDate = $period['start_date'];
        $endDate   = $period['end_date'];

        // query untuk mengelompokkan data per BULAN
        $bankAndCash = BankNCash::with(['toCoa.toCoaBody.toCoaClasification'])->get();
        $bankAndCashCoas = $bankAndCash->pluck('toCoa.coa')->toArray();

        $monthlyLedgerData = GeneralLedger::selectRaw("coa,
        DATE_FORMAT(date, '%m') as month,
        SUM(CASE WHEN type = 'D' THEN value ELSE 0 END) as total_debit,
        SUM(CASE WHEN type = 'K' THEN value ELSE 0 END) as total_credit")
            ->whereIn('coa', $bankAndCashCoas)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('phase', ['opr', 'int', 'acm'])

            // cek ke relasi table, untuk dapatkan status valid
            // ini karena di GL tidak ada status validnya
            // ->where(function ($query) {
            //     $query->whereHas('toTransactionFull', function ($subQuery) {
            //         $subQuery->where('status', 'valid');
            //     })
            //         ->orWhereHas('toTransaction', function ($subQuery) {
            //             $subQuery->where('status', 'valid');
            //         });
            // })

            ->groupBy('coa', 'month')
            ->get()
            ->groupBy('coa'); // dikelompokkan per COA

        // init struktur data
        $bank = ['value' => 0];
        $cash = ['value' => 0];

        // array sementara untuk menampung total per bulan untuk setiap tipe (bank/cash)
        $monthlyTotals = [
            'bank' => [],
            'cash' => [],
        ];

        // menghitung total & data bulanan setiap akun
        foreach ($bankAndCash as $account) {
            $coaCode = $account->toCoa->coa;
            $normalBalance = $account->toCoa->toCoaBody->toCoaClasification->normal_balance ?? 'D';
            $accountType = $account->type; // 'bank' atau 'cash'

            // ambil transaksi bulanan untuk COA ini
            $monthlyTransactions = $monthlyLedgerData->get($coaCode, collect());

            // a. Hitung grand total saldo untuk rentang waktu utama
            $grandTotalDebit = $monthlyTransactions->sum('total_debit');
            $grandTotalCredit = $monthlyTransactions->sum('total_credit');

            $balance = ($normalBalance == 'D')
                ? $grandTotalDebit - $grandTotalCredit
                : $grandTotalCredit - $grandTotalDebit;

            if ($accountType == 'bank') {
                $bank['value'] += $balance;
            } else {
                $cash['value'] += $balance;
            }

            // b. Akumulasikan data per bulan untuk chart
            foreach ($monthlyTransactions as $tx) {
                $monthKey = $tx->month;
                $monthlyBalance = ($normalBalance == 'D')
                    ? $tx->total_debit - $tx->total_credit
                    : $tx->total_credit - $tx->total_debit;

                // inisialisasi jika bulan ini belum ada di array
                if (!isset($monthlyTotals[$accountType][$monthKey])) {
                    $monthlyTotals[$accountType][$monthKey] = 0;
                }

                // tambah saldo bulanan akun ini ke total tipe akun (bank/cash)
                $monthlyTotals[$accountType][$monthKey] += $monthlyBalance;
            }
        }

        // Finalisasi data chart untuk memastikan semua bulan ada
        $monthLabels = [];
        $bankSeries = [];
        $cashSeries = [];

        for ($m = 1; $m <= $endDate->month; $m++) {
            $monthKey = str_pad($m, 2, '0', STR_PAD_LEFT);

            // label nama bulan
            $monthLabels[] = $this->month[$monthKey];

            // total bulanan untuk bank, default 0 jika tidak ada transaksi
            $bankSeries[] = $monthlyTotals['bank'][$monthKey] ?? 0;

            // total bulanan untuk cash, default 0 jika tidak ada transaksi
            $cashSeries[] = $monthlyTotals['cash'][$monthKey] ?? 0;
        }

        // struktur data output final
        $current_balance = [
            'value' => $bank['value'] + $cash['value'],
        ];

        $bank['chart'] = [
            'labels' => $monthLabels,
            'series' => $bankSeries,
        ];

        $cash['chart'] = [
            'labels' => $monthLabels,
            'series' => $cashSeries,
        ];

        $data = [
            'current_balance' => $current_balance,
            'bank'            => $bank,
            'cash'            => $cash,
        ];

        return ApiResponseClass::sendResponse($data, 'Dashboard Finance Retrieved Successfully');
    }

    public function receiveExpense(Request $request)
    {
        $period = $this->getPeriodDates(
            $request->input('type', 'year'),
            $request->input('period', date('Y'))
        );

        $startDate = $period['start_date'];
        $endDate   = $period['end_date'];

        // PENERIMAAN
        // 1. Ambil data bulanan dari tabel TransactionFull
        $monthlyFullReceives = TransactionFull::selectRaw("DATE_FORMAT(date, '%m') as month, SUM(value) as total_value")
            ->whereBetween('date', [$startDate, $endDate])
            ->where('category', 'penerimaan')
            ->where('status', 'valid')
            ->groupBy('month')
            ->pluck('total_value', 'month'); // Hasil: ['01' => 1000, '03' => 500]

        // 2. Ambil data bulanan dari tabel Receive
        $monthlyOsReceives = Receipts::selectRaw("DATE_FORMAT(date, '%m') as month, SUM(value) as total_value")
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'valid')
            ->groupBy('month')
            ->pluck('total_value', 'month'); // Hasil: ['01' => 200, '02' => 300]

        // 3. Gabungkan hasil & siapkan data chart
        $monthLabels = [];
        $monthReceiveSeries = [];
        $grandTotalReceive = 0;

        // Looping dari bulan pertama hingga bulan saat ini
        for ($m = 1; $m <= $endDate->month; $m++) {
            // Format bulan menjadi 2 digit (01, 02, ..., 12)
            $monthKey = str_pad($m, 2, '0', STR_PAD_LEFT);

            // Tambahkan label bulan
            $monthLabels[] = $this->month[$monthKey];

            // Ambil total dari setiap hasil query. Jika tidak ada, nilainya 0.
            $fullValue = $monthlyFullReceives->get($monthKey, 0);
            $osValue = $monthlyOsReceives->get($monthKey, 0);

            // Jumlahkan total pengeluaran untuk bulan ini
            $totalMonthlyValue = $fullValue + $osValue;

            // Tambahkan nilai gabungan ke series chart
            $monthReceiveSeries[] = $totalMonthlyValue;

            // Akumulasikan grand total pengeluaran
            $grandTotalReceive += $totalMonthlyValue;
        }

        // 4. Bentuk struktur data final
        $receives = [
            "value" => $grandTotalReceive,
            "chart" => [
                "labels" => $monthLabels,
                "series" => $monthReceiveSeries
            ]
        ];

        // PENGELUARAN
        // 1. Ambil data bulanan dari tabel TransactionFull
        $monthlyFullExpenses = TransactionFull::selectRaw("DATE_FORMAT(date, '%m') as month, SUM(value) as total_value")
            ->whereBetween('date', [$startDate, $endDate])
            ->where('category', 'pengeluaran')
            ->where('status', 'valid')
            ->groupBy('month')
            ->pluck('total_value', 'month'); // Hasil: ['01' => 1000, '03' => 500]

        // 2. Ambil data bulanan dari tabel Expenditure
        $monthlyOsExpenses = Expenditure::selectRaw("DATE_FORMAT(date, '%m') as month, SUM(value) as total_value")
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'valid')
            ->groupBy('month')
            ->pluck('total_value', 'month'); // Hasil: ['01' => 200, '02' => 300]

        // 3. Gabungkan hasil & siapkan data chart
        $monthLabels = [];
        $monthExpenseSeries = [];
        $grandTotalExpense = 0;

        // Looping dari bulan pertama hingga bulan saat ini
        for ($m = 1; $m <= $endDate->month; $m++) {
            // Format bulan menjadi 2 digit (01, 02, ..., 12)
            $monthKey = str_pad($m, 2, '0', STR_PAD_LEFT);

            // Tambahkan label bulan
            $monthLabels[] = $this->month[$monthKey];

            // Ambil total dari setiap hasil query. Jika tidak ada, nilainya 0.
            $fullValue = $monthlyFullExpenses->get($monthKey, 0);
            $osValue = $monthlyOsExpenses->get($monthKey, 0);

            // Jumlahkan total pengeluaran untuk bulan ini
            $totalMonthlyValue = $fullValue + $osValue;

            // Tambahkan nilai gabungan ke series chart
            $monthExpenseSeries[] = $totalMonthlyValue;

            // Akumulasikan grand total pengeluaran
            $grandTotalExpense += $totalMonthlyValue;
        }

        // 4. Bentuk struktur data final
        $expenses = [
            "value" => $grandTotalExpense,
            "chart" => [
                "labels" => $monthLabels,
                "series" => $monthExpenseSeries
            ]
        ];

        // receives vs expenses
        $receive_vs_expense = [
            "labels" => $monthLabels,
            "series" => [$monthReceiveSeries, $monthExpenseSeries]
        ];

        $data = [
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'receive'            => $receives,
            'expense'            => $expenses,
            'receive_vs_expense' => $receive_vs_expense,
        ];

        return ApiResponseClass::sendResponse($data, 'Dashboard Finance Retrieved Successfully');
    }

    public function receivablePayable(Request $request)
    {
        $period = $this->getPeriodDates(
            $request->input('type', 'year'),
            $request->input('period', date('Y'))
        );

        $startDate = $period['start_date'];
        $endDate   = $period['end_date'];

        // =======================================================
        // ## 1. PIUTANG (ACCOUNT RECEIVABLE)
        // =======================================================

        // a. Hitung total nilai piutang dari AWAL WAKTU
        // DIUBAH: Menghitung total dari awal waktu hingga tanggal saat ini
        $totalReceivableInvoices = Transaction::where('date', '<=', $endDate)
            ->where('status', 'valid')
            ->where('category', 'penerimaan')
            ->sum('value');

        // b. Hitung total pembayaran yang sudah diterima dari AWAL WAKTU
        // DIUBAH: Menghitung total dari awal waktu hingga tanggal saat ini
        $totalReceipts = Receipts::where('date', '<=', $endDate)
            ->where('status', 'valid')
            ->sum('value');

        // c. Hitung nilai piutang yang masih beredar (outstanding)
        $receivable = [
            'value' => $totalReceivableInvoices - $totalReceipts,
        ];

        // d. Ambil data penambahan piutang baru per bulan untuk chart TAHUN INI
        // TETAP: Chart tetap menggunakan rentang waktu tahun ini ($start_year)
        $monthlyNewReceivables = Transaction::selectRaw("
        DATE_FORMAT(date, '%m') as month,
        SUM(value) as total_value
    ")
            ->where('status', 'valid')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('category', 'penerimaan')
            ->groupBy('month')
            ->pluck('total_value', 'month');

        // e. Finalisasi data chart Receivable (tidak ada perubahan di sini)
        $receivableLabels = [];
        $receivableSeries = [];
        for ($m = 1; $m <= $endDate->month; $m++) {
            $monthKey = str_pad($m, 2, '0', STR_PAD_LEFT);
            $receivableLabels[] = $this->month[$monthKey];
            $receivableSeries[] = $monthlyNewReceivables->get($monthKey, 0);
        }

        $receivable['chart'] = [
            'labels' => $receivableLabels,
            'series' => $receivableSeries,
        ];


        // =======================================================
        // ## 2. HUTANG (ACCOUNT PAYABLE)
        // =======================================================

        // a. Hitung total nilai hutang dari AWAL WAKTU
        // DIUBAH: Menghitung total dari awal waktu hingga tanggal saat ini
        $totalPayableInvoices = Transaction::where('date', '<=', $endDate)
            ->where('status', 'valid')
            ->where('category', 'pengeluaran')
            ->sum('value');

        // b. Hitung total pembayaran yang sudah dilakukan dari AWAL WAKTU
        // DIUBAH: Menghitung total dari awal waktu hingga tanggal saat ini
        $totalExpenditures = Expenditure::where('date', '<=', $endDate)
            ->where('status', 'valid')
            ->sum('value');

        // c. Hitung nilai hutang yang masih beredar (outstanding)
        $payable = [
            'value' => $totalPayableInvoices - $totalExpenditures,
        ];

        // d. Ambil data penambahan hutang baru per bulan untuk chart TAHUN INI
        // TETAP: Chart tetap menggunakan rentang waktu tahun ini ($start_year)
        $monthlyNewPayables = Transaction::selectRaw("
        DATE_FORMAT(date, '%m') as month,
        SUM(value) as total_value
    ")
            ->whereBetween('date', [$startDate, $endDate])
            ->where('category', 'pengeluaran')
            ->groupBy('month')
            ->pluck('total_value', 'month');

        // e. Finalisasi data chart Payable (tidak ada perubahan di sini)
        $payableLabels = [];
        $payableSeries = [];
        for ($m = 1; $m <= $endDate->month; $m++) {
            $monthKey = str_pad($m, 2, '0', STR_PAD_LEFT);
            $payableLabels[] = $this->month[$monthKey];
            $payableSeries[] = $monthlyNewPayables->get($monthKey, 0);
        }

        $payable['chart'] = [
            'labels' => $payableLabels,
            'series' => $payableSeries,
        ];

        // receives vs expenses
        $receivable_vs_payable = [
            "labels" => $payableLabels,
            "series" => [$payableSeries, $receivableSeries]
        ];

        $data = [
            'payable' => $payable,
            'receivable' => $receivable,
            'receivable_vs_payable' => $receivable_vs_payable,
        ];

        return ApiResponseClass::sendResponse($data, 'Dashboard Finance Retrieved Successfully');
    }

    public function indexOld(Request $request)
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
        $bankAndCash = BankNCash::with(['toCoa'])->get();

        $bank = [];
        $cash = [];
        $series = [];
        foreach ($bankAndCash as $key => $value) {
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

    private function getPeriodDates(?string $type, ?string $period): array
    {
        $now    = Carbon::now();

        switch ($type) {
            case 'month':
                $startDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
                $endDate   = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
                break;

            case 'quarter':
                [$q_start, $q_end] = explode(':', $period);
                $startDate = Carbon::createFromFormat('Y-m', $q_start)->startOfMonth();
                $endDate   = Carbon::createFromFormat('Y-m', $q_end)->endOfMonth();
                break;

            case 'year':
                $date = Carbon::createFromDate($period);
                $startDate = $date->copy()->startOfYear();
                if ($period == $now->year) {
                    $endDate = $now;
                } else {
                    $endDate = $date->copy()->endOfYear();
                }
                break;

            // custom
            case 'year_to_date':
                $startDate = Carbon::createFromFormat('Y', $period)->startOfYear();
                $endDate   = $now;
                break;

            default:
                // default value jika tipe tidak valid
                $startDate = $now->copy()->startOfYear();
                $endDate   = $now;
                break;
        }

        return [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
    }
}
