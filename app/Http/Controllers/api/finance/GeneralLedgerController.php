<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\finance\BankReconciliation;
use App\Models\finance\ClosingEntry;
use App\Models\finance\Coa;
use App\Models\finance\Expenditure;
use App\Models\finance\GeneralLedger;
use App\Models\finance\GeneralLedgerLog;
use App\Models\finance\InitialBalance;
use App\Models\finance\JournalEntry;
use App\Models\finance\Receipts;
use App\Models\finance\ReportMenu;
use App\Models\finance\Switching;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneralLedgerController extends Controller
{
    // untuk buku besar
    /**
     * @OA\Get(
     *  path="/general-ledgers",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $start_date = $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);

        $get = Coa::orderBy('id_coa_body', 'asc')->get();

        $data = [];
        foreach ($get as $key => $value) {
            $general_ledger = $value->toGeneralLedger->whereBetween('date', [$start_date, $end_date]);

            $val_debit  = 0;
            $val_credit = 0;
            $balance    = 0;

            $normal_balance = 'D';

            $general_ledger = $value->toGeneralLedger->whereBetween('date', [$start_date, $end_date]);

            if ($value->toCoaBody->toCoaClasification->normal_balance == 'D') {
                $normal_balance = 'D';
            } else {
                $normal_balance = 'K';
            }

            foreach ($general_ledger as $key => $value2) {
                if ($value2->type === 'K') {
                    $val_credit = $val_credit + $value2->value;
                } else {
                    $val_debit = $val_debit + $value2->value;
                }
            }

            if ($normal_balance == 'D') {
                $balance = $val_debit - $val_credit;
            } else {
                $balance = $val_credit - $val_debit;
            }

            if (($val_debit == 0) && ($val_credit == 0)) {
                continue;
            }

            $data[] = [
                'coa'   => $value->coa,
                'name'  => $value->name,
                'total' => $balance
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Retrieved Successfully');
    }

    // untuk laba rugi
    /**
     * @OA\Get(
     *  path="/general-ledgers/statement/{id}",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function statement($id, Request $request)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $report_menu = ReportMenu::with(['toReportTitle'])->findOrFail($id);

        $report_title = $report_menu->toReportTitle;

        $data = [];
        $body = [];
        foreach ($report_title as $key => $value) {
            if ($value->toReportBody) {
                foreach ($value->toReportBody as $key => $value2) {
                    $balance = 0;

                    if ($value2->method === 'range') {
                        $coa_name = $value2->toCoa->name . 'zzz' . Carbon::parse($start_date)->format('d M');
                        $balance = _sum_account_saldo($value2, $start_date, $start_date, ['opr', 'int']);
                    } else {
                        $coa_name = $value2->toCoa->name;
                        $balance = _sum_account_saldo($value2, $start_date, $end_date, ['opr', 'int']);
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
                'total' => $value->is_formula === '1' ? _count_report_title_formula($value->id_report_title, $start_date, $end_date) : _count_report_title_total($value->id_report_title, $start_date, $end_date)
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Retrieved Successfully');
    }

    // untuk neraca saldo
    /**
     * @OA\Get(
     *  path="/general-ledgers/balance",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    // ini sudah betul kalau pake closing bulanan
    // jangan dihapus, ini originalnya
    public function balanceWithClosingBulanan(Request $request)
    {
        $year  = Carbon::parse($request->period)->format('Y');
        $month = Carbon::parse($request->period)->format('m');

        $prev_year  = Carbon::parse($request->period)->subMonth()->format('Y');
        $prev_month = Carbon::parse($request->period)->subMonth()->format('m');

        $start_date = Carbon::create($year, $month, 1)->startOfDay()->format('Y-m-d');
        $end_date   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()->format('Y-m-d');

        $closing_entry = ClosingEntry::with(['toClosingEntryDetail'])->where('month', $prev_month)->where('year', $prev_year)->first();

        // saldo awal
        $saldo_awal = [];
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
        } else {
            // saldo awal current month
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

        // saldo awal acumulated prev month
        $initial_balance_prev = GeneralLedger::whereMonth('date', $prev_month)->whereYear('date', $prev_year)->where('phase', 'acm')->get();
        foreach ($initial_balance_prev as $key => $value) {
            if ($value->toCoa->toCoaBody->toCoaClasification->group !== 'beban') {
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

        $get = Coa::orderBy('id_coa_body', 'asc')->get();

        $data = [];
        foreach ($get as $key => $value) {
            $val_debit  = 0;
            $val_credit = 0;

            $credit_akhir =  0;
            $debit_akhir  =  0;

            // saldo awal
            $credit_awal =  $saldo_awal[$value->coa]['credit'] ?? 0;
            $debit_awal  =  $saldo_awal[$value->coa]['debit'] ?? 0;

            // mutasi
            $general_ledger = $value->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'acm']);
            foreach ($general_ledger as $key => $value2) {
                if ($value2->type === 'D') {
                    $val_debit = $val_debit + $value2->value;
                } else {
                    $val_credit = $val_credit + $value2->value;
                }
            }

            // saldo akhir
            if ($value->toCoaBody->toCoaClasification->normal_balance == 'D') {
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

            if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                continue;
            }

            $data[] = [
                'id_coa' => $value->id_coa,
                'coa'    => $value->coa,
                'name'   => $value->name,

                'credit_awal' => $credit_awal,
                'debit_awal'  => $debit_awal,

                'credit_mutasi' => $val_credit,
                'debit_mutasi'  => $val_debit,

                'credit_akhir' => $credit_akhir,
                'debit_akhir'  => $debit_akhir,
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Retrieved Successfully');
    }

    // untuk lihat detail coa per periode
    /**
     * @OA\Get(
     *  path="/general-ledgers/coa/{coa}",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="coa",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function coa($coa, Request $request)
    {
        $begin = Carbon::now();
        $end   = Carbon::now();

        $start_date = $request->start_date ?? $begin->startOfYear()->format('Y-m-d');
        $end_date   = $request->end_date ?? $end->format('Y-m-d');

        $get = GeneralLedger::with(['toCoa.toCoaBody.toCoaClasification'])
            ->where('coa', $coa)
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('date', 'asc')
            ->get();

        $data    = [];
        $balance = 0;
        $no      = 0;
        foreach ($get as $key => $row) {
            $val_debit  = 0;
            $val_credit = 0;

            $normal_balance = 'D';

            if ($row->toCoa->toCoaBody->toCoaClasification->normal_balance == 'D') {
                $normal_balance = 'D';
            } else {
                $normal_balance = 'K';
            }

            if ($row->type === 'K') {
                $val_credit = $row->value;

                $balance += ($normal_balance == 'D') ? -$row->value : $row->value;
            } else {
                $val_debit = $row->value;

                $balance += ($normal_balance == 'D') ? $row->value : -$row->value;
            }

            $data[] = [
                'no'          => $no += 1,
                'date'        => $row->date,
                'description' => $row->description,
                'debit'       => $val_debit,
                'credit'      => $val_credit,
                'saldo'       => $balance
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Retrieved Successfully');
    }

    // untuk cek transaksi
    /**
     * @OA\Get(
     *  path="/general-ledgers/check",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="transaction_number",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function check(Request $request)
    {
        $transaction_number = $request->transaction_number;
        if (str_starts_with($transaction_number, 'DP-')) {
            $transaction_number = Str::replaceFirst('DP-', '', $transaction_number);
        }

        $data = [];

        $logs = [];

        $debit = [];

        $credit = [];

        $transaction_category = '';

        $no = 0;

        // kenapa bisa ditampilkan beserta transaksi yang sudah dihapus?
        // $gl = GeneralLedger::with(['toTransaction'])->withTrashed()->where('transaction_number', $transaction_number)->orWhere('reference_number', $transaction_number)->get();

        $gl = GeneralLedger::with(['toTransaction'])->where('transaction_number', $transaction_number)->orWhere('reference_number', $transaction_number)->get();

        if ($gl->count() == 0) {
            return ApiResponseClass::sendResponse($data, 'Transaction Number Not Found!');
        } else {
            foreach ($gl as $key => $row) {
                $val_debit  = 0;
                $val_credit = 0;

                if ($row->type === 'K') {
                    $credit[] = $row->value;
                    $val_credit = $row->value;
                } else {
                    $debit[] = $row->value;
                    $val_debit = $row->value;
                }

                $data[] = [
                    'no'                => $no += 1,
                    'id_general_ledger' => $row->id_general_ledger,
                    'date'              => $row->date,
                    'coa'               => $row->toCoa->name,
                    'type'              => $row->type,
                    'debit'             => (float) $val_debit,
                    'credit'            => (float) $val_credit,
                    'value'             => (float) $row->value,
                    'description'       => $row->description,
                ];

                if ($row->toTransaction) {
                    $transaction_category = $row->toTransaction->category;
                } else {
                    $transaction_category = 'N/A';
                }
            }

            $check_gl_log = DB::connection('finance')
                ->table('general_ledger_logs')
                ->select('transaction_number', 'revision')
                ->where('transaction_number', $transaction_number)
                ->orWhere('reference_number', $transaction_number)
                ->groupBy('transaction_number', 'revision')
                ->get();


            foreach ($check_gl_log as $key => $row) {
                $gl_log = GeneralLedgerLog::where('revision', $row->revision)
                    ->where('transaction_number', $transaction_number)
                    ->orWhere('reference_number', $transaction_number)
                    ->get();

                $details = [];

                foreach ($gl_log as $key => $log) {
                    $val_debit  = 0;
                    $val_credit = 0;

                    if ($log->type === 'K') {
                        $val_credit = $log->value;
                    } else {
                        $val_debit = $log->value;
                    }

                    $details[] = [
                        'no'                    => $no += 1,
                        'id_general_ledger_log' => $log->id_general_ledger_log,
                        'date'                  => $log->date,
                        'coa'                   => $log->toCoa->name,
                        'type'                  => $log->type,
                        'debit'                 => (float) $val_debit,
                        'credit'                => (float) $val_credit,
                        'value'                 => (float) $log->value,
                        'description'           => $log->description,
                        'reference_number'      => $log->reference_number,
                        'date_revision'         => Carbon::parse($log->created_at)->format('Y-m-d'),
                    ];
                }

                $logs[] = $details;
            }

            $response = [
                'record'               => $data,
                'logs'                 => $logs,
                'debit'                => array_sum($debit),
                'credit'               => array_sum($credit),
                'description'          => $data[0]['description'],
                'transaction_category' => $transaction_category,
            ];

            return ApiResponseClass::sendResponse($response, 'General Ledger Retrieved Successfully');
        }
    }

    // untuk update transaction number
    /**
     * @OA\Put(
     *  path="/general-ledgers/update",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="transaction_number",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total"
     *              ),
     *              @OA\Property(
     *                  property="id_general_ledger",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="id_general_ledger"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="D or K"
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="Value"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              example={
     *                  "total": "integer",
     *                  "id_general_ledger": {"D", "K", "K"},
     *                  "type": {"D", "K", "K"},
     *                  "value": {"n", "n", "n"},
     *                  "description": "Lorem ipsum dolor sit amet"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = $request->transaction_number;
            $id_general_ledger  = $request->id_general_ledger;
            $type               = $request->type;
            $value              = $request->value;

            // begin:: update transaction
            $transaction = Transaction::where('transaction_number', $transaction_number)->first();
            $transaction->value = $request->total;
            $transaction->save();
            // end:: update transaction

            // begin:: insert general ledger log
            $check_general_ledger = GeneralLedger::where('transaction_number', $transaction_number)->get();

            $count_general_ledger_logs = DB::connection('finance')
                ->table('general_ledger_logs')
                ->select('transaction_number')
                ->where('transaction_number', $transaction_number)
                ->orWhere('reference_number', $transaction_number)
                ->groupBy('transaction_number', 'revision')
                ->get()
                ->count();

            $general_ledger_logs = [];
            foreach ($check_general_ledger as $key => $row) {
                $general_ledger_logs[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $row->date,
                    'coa'                => $row->coa,
                    'type'               => $row->type,
                    'value'              => $row->value,
                    'description'        => $row->description,
                    'reference_number'   => $row->reference_number,
                    'revision'           => $count_general_ledger_logs + 1
                ];
            }

            GeneralLedgerLog::insert($general_ledger_logs);
            // end:: insert general ledger log

            // begin:: update general ledger
            foreach ($id_general_ledger as $key => $row) {
                $gl              = GeneralLedger::find($row);
                $gl->type        = $type[$key];
                $gl->value       = $value[$key];
                $gl->description = $request->description;
                $gl->save();
            }
            // end:: update general ledger

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($transaction, 'General Ledger Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    // untuk jurnal balik
    /**
     * @OA\Get(
     *  path="/general-ledgers/reverse/{transaction_number}/{type}",
     *  summary="Get the list of general ledger",
     *  tags={"Finance - General Ledger"},
     *  @OA\Parameter(
     *      name="transaction_number",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"receipts", "expenditures", "initial_balance", "journal_entries", "switching", "transaction_out", "transaction_full"}
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */

    public function reverse(Request $request, $type)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = $request->transaction_number;

            $check_close = GeneralLedger::where('transaction_number', $transaction_number)->where('closed', '1')->get();

            if ($check_close->count() > 0) {
                return ApiResponseClass::sendResponse($check_close, 'Transaction Already Closed!');
            } else {
                $check_jb = GeneralLedger::whereTransactionNumber($transaction_number)->where('jb', '1')->get();

                if ($check_jb->count() > 0) {
                    return ApiResponseClass::throw('Transaction Already Reversed!', 400);
                } else {
                    if ($type === 'receipts') {
                        $head = Receipts::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'expenditures') {
                        $head = Expenditure::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'initial_balances') {
                        $head = InitialBalance::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'journal_entries') {
                        $head = JournalEntry::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'switching') {
                        $head = Switching::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'transaction_out') {
                        $head = Transaction::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'transaction_full') {
                        $head = TransactionFull::whereTransactionNumber($transaction_number)->first();
                    }

                    if ($type === 'bank_reconciliations') {
                        $head = BankReconciliation::whereTransactionNumber($transaction_number)->first();
                    }

                    $head->status = 'reversed';
                    $head->save();

                    $detail = GeneralLedger::whereTransactionNumber($transaction_number)->get();

                    $data = [];
                    foreach ($detail as $key => $row) {
                        $row->jb = '1';
                        $row->save();

                        $data[] = [
                            'id_journal'         => $row->id_journal,
                            'transaction_number' => 'RET-' . $row->transaction_number,
                            'date'               => $row->date,
                            'coa'                => $row->coa,
                            'type'               => $row->type === 'K' ? 'D' : 'K',
                            'value'              => $row->value,
                            'description'        => 'Retur ' . $row->description,
                            'reference_number'   => $row->reference_number,
                            'phase'              => $row->phase,
                            'created_by'         => auth('api')->user()->id_users
                        ];
                    }

                    GeneralLedger::insert($data);

                    DB::connection('finance')->commit();
                }

                return ApiResponseClass::sendResponse($data, 'General Ledger Reversed Successfully');
            }
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function complex(Request $request)
    {
        $start_date = $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date   = end_date_month($request->end_date);

        // 1. Ambil hanya COA codes yang memiliki General Ledger (exclude soft deleted)
        $coaCodes = DB::connection('finance')->table('general_ledgers')
            ->whereBetween('date', [$start_date, $end_date])
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('coa');

        // 2. Query COA dengan normal_balance langsung menggunakan join
        $coas = DB::connection('finance')->table('coa as c')
            ->select(
                'c.coa',
                'c.name',
                'c.id_coa_body',
                'cc.normal_balance'
            )
            ->join('coa_body as cb', 'c.id_coa_body', '=', 'cb.id_coa_body')
            ->join('coa_clasification as cc', 'cb.id_coa_clasification', '=', 'cc.id_coa_clasification')
            ->whereIn('c.coa', $coaCodes)
            ->orderBy('c.coa', 'asc')
            ->get();

        // 3. Ambil semua General Ledger sekaligus (exclude soft deleted)
        $generalLedgers = DB::connection('finance')->table('general_ledgers as gl')
            ->select(
                'gl.coa',
                'gl.date',
                'gl.description',
                'gl.type',
                'gl.value',
                'gl.reference_number',
                'gl.id_kontak'
            )
            ->whereBetween('gl.date', [$start_date, $end_date])
            ->whereIn('gl.coa', $coaCodes)
            ->whereNull('gl.deleted_at')
            ->orderBy('gl.coa', 'asc')
            ->orderBy('gl.date', 'asc')
            ->get()
            ->groupBy('coa');

        // 4. Ambil kontak sekaligus (jika beda database)
        $kontakIds = $generalLedgers->flatten(1)->pluck('id_kontak')->unique()->filter();
        $kontaks = DB::connection('mysql')->table('kontak')
            ->whereIn('id_kontak', $kontakIds)
            ->pluck('name', 'id_kontak');

        // 5. Ambil outstanding transactions sekaligus
        $referenceNumbers = $generalLedgers->flatten(1)->pluck('reference_number')->unique()->filter();
        $outstandingRefs = DB::connection('finance')->table('transaction')
            ->whereIn('reference_number', $referenceNumbers)
            ->pluck('reference_number')
            ->flip();

        $data = [];
        $globalNo = 0;

        // 6. Process data - loop berdasarkan urutan COA dari query
        foreach ($coas as $coa) {
            $ledgers = $generalLedgers->get($coa->coa, collect());

            if ($ledgers->isEmpty()) {
                continue;
            }

            $normal_balance = $coa->normal_balance ?? 'D';

            $val_debit = 0;
            $val_credit = 0;
            $localBalance = 0;
            $details = [];

            foreach ($ledgers as $ledger) {
                $debit = $ledger->type === 'D' ? $ledger->value : 0;
                $credit = $ledger->type === 'K' ? $ledger->value : 0;

                $val_debit += $debit;
                $val_credit += $credit;

                $localBalance += $ledger->type === 'K'
                    ? ($normal_balance === 'D' ? -$ledger->value : $ledger->value)
                    : ($normal_balance === 'D' ? $ledger->value : -$ledger->value);

                $details[] = [
                    'no'               => ++$globalNo,
                    'coa'              => $ledger->coa,
                    'date'             => $ledger->date,
                    'description'      => $ledger->description,
                    'debit'            => floatval($debit),
                    'credit'           => floatval($credit),
                    'saldo'            => $localBalance,
                    'outstanding'      => isset($outstandingRefs[$ledger->reference_number]),
                    'reference_number' => $ledger->reference_number,
                    'contact'          => $kontaks[$ledger->id_kontak] ?? '-',
                ];
            }

            if ($val_debit === 0 && $val_credit === 0) {
                continue;
            }

            $balance = $normal_balance === 'D' ? $val_debit - $val_credit : $val_credit - $val_debit;

            $data[] = [
                'coa'     => $coa->coa,
                'name'    => $coa->name,
                'total'   => $balance,
                'details' => $details
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Complex Successfully');
    }

    public function transaction_coa(Request $request)
    {
        $transactionNumber = $request->input('transaction_number');

        if (!$transactionNumber) {
            return ApiResponseClass::throw('Transaction number is required!', 400);
        }

        $result = GeneralLedger::where('transaction_number', $transactionNumber)
            ->with(['toCoa:id_coa,coa,name'])
            ->get()
            ->groupBy('coa')
            ->map(function ($items, $coa) {
                $coaData = $items->first()->toCoa;

                return [
                    'coa'    => $coa,
                    'name'   => $coaData->name ?? 'N/A',
                    'detail' => $items->map(function ($item) {
                        return [
                            'date'        => $item->date,
                            'description' => $item->description,
                            'credit'      => $item->type === 'K' ? $item->value : 0,
                            'debit'       => $item->type === 'D' ? $item->value : 0,
                        ];
                    })->toArray(),
                ];
            })
            ->values();

        return ApiResponseClass::sendResponse($result, 'General Ledger Retrieved Successfully');
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
        $year           = Carbon::parse($period)->format('Y');
        $month          = Carbon::parse($period)->format('m');

        $prev_year      = Carbon::parse($period)->subMonth()->format('Y');
        $prev_month     = Carbon::parse($period)->subMonth()->format('m');

        $start_date     = Carbon::create($year, $month, 1)->startOfDay()->format('Y-m-d');
        $start_date_end = Carbon::create($start_date)->endOfMonth()->endOfDay()->format('Y-m-d');
        $end_date       = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()->format('Y-m-d');

        $prev_balances  = $this->previousBalance($period);

        // saldo awal
        $saldo_awal         = [];
        $saldo_awal_current = [];
        // if ($closing_entry) {
        //     foreach ($closing_entry->toClosingEntryDetail as $key => $value) {
        //         if ($value->toCoa->toCoaBody->toCoaClasification->accrual == 'accumulated') {
        //             if ($value->toCoa->toCoaBody->toCoaClasification->normal_balance == 'D') {
        //                 $saldo_awal[$value->coa] = [
        //                     'debit'  => $value->debit - $value->credit,
        //                     'credit' => 0
        //                 ];
        //             } else {
        //                 $saldo_awal[$value->coa] = [
        //                     'debit'  => 0,
        //                     'credit' => $value->credit - $value->debit
        //                 ];
        //             }
        //         }
        //     }
        // }

        // saldo awal current month
        $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $start_date_end])->where('phase', 'int')->where('jb', '0')->get();
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

        if ($month != '01') {
            // foreach ($saldo_awal_current as $coa => $values) {
            //     // Cari apakah coa ada di prev_balances
            //     $prev_balance = collect($prev_balances)->firstWhere('coa', $coa);

            //     if ($prev_balance) {
            //         // Jika coa ada di prev_balances, tambahkan nilai credit dan debit
            //         $saldo_awal[$coa] = [
            //             'credit' => $values['credit'] + $prev_balance['credit'],
            //             'debit' => $values['debit'] + $prev_balance['debit'],
            //         ];
            //     } else {
            //         // Jika coa tidak ada di prev_balances, gunakan nilai dari saldo_awal_current
            //         $saldo_awal[$coa] = [
            //             'credit' => $values['credit'],
            //             'debit' => $values['debit'],
            //         ];
            //     }
            // }

            // Memastikan nilai dari prev_balances yang tidak ada di saldo_awal_current juga ditambahkan
            foreach ($prev_balances as $balance) {
                if (!array_key_exists($balance['coa'], $saldo_awal)) {
                    $saldo_awal[$balance['coa']] = [
                        'credit' => $balance['credit'],
                        'debit' => $balance['debit'],
                    ];
                }
            }
        }


        $get = Coa::orderBy('id_coa_body', 'asc')->get();

        $data = [];
        foreach ($get as $key => $value) {
            $val_debit  = 0;
            $val_credit = 0;

            $credit_akhir =  0;
            $debit_akhir  =  0;

            // saldo awal
            $credit_awal =  $saldo_awal[$value->coa]['credit'] ?? 0;
            $debit_awal  =  $saldo_awal[$value->coa]['debit'] ?? 0;

            // mutasi
            $general_ledger = $value->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'acm'])->where('jb', '0');
            foreach ($general_ledger as $key => $value2) {
                if ($value2->type === 'D') {
                    $val_debit = $val_debit + $value2->value;
                } else {
                    $val_credit = $val_credit + $value2->value;
                }
            }

            // normal balance
            if ($value->toCoaBody->toCoaClasification->normal_balance == 'D') {
                $default = 'D';
            } else {
                $default = 'K';
            }

            if ($default == 'D') {
                $balance_debit  = $debit_awal + ($val_debit - $val_credit);
                $balance_credit = $credit_awal + ($val_debit - $val_credit);

                $debit_akhir  = $balance_debit;
                $credit_akhir = $credit_awal == 0 ? 0 : $balance_credit;
            } else {
                $balance_credit = $credit_awal + ($val_credit - $val_debit);
                $balance_debit  = $debit_awal + ($val_credit - $val_debit);

                $debit_akhir  = $debit_awal == 0 ? 0 : $balance_debit;
                $credit_akhir = $balance_credit;
            }

            if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                continue;
            }

            $data[] = [
                'id_coa' => $value->id_coa,
                'coa'    => $value->coa,
                'name'   => $value->name,

                'credit_awal' => $credit_awal,
                'debit_awal'  => $debit_awal,

                'credit_mutasi' => $val_credit,
                'debit_mutasi'  => $val_debit,

                'credit_akhir' => $credit_akhir,
                'debit_akhir'  => $debit_akhir,
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Retrieved Successfully');
    }

    public function balance_tahunan($period)
    {
        $year       = Carbon::createFromFormat('Y', $period)->format('Y');
        $prev_year  = Carbon::createFromFormat('Y', $period)->subYear()->format('Y');

        $start_date = Carbon::create($year, 1, 1)->startOfYear()->startOfDay()->format('Y-m-d');
        $end_date   = Carbon::create($year, 12, 31)->endOfYear()->endOfDay()->format('Y-m-d');

        $saldo_awal = [];

        // ambil initial balance
        $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $end_date])->where('phase', 'int')->where('jb', '0')->get();
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

        $get = Coa::orderBy('id_coa_body', 'asc')->get();

        $data = [];
        foreach ($get as $key => $value) {
            $val_debit  = 0;
            $val_credit = 0;

            $debit_akhir  =  0;
            $credit_akhir =  0;

            // saldo awal
            $debit_awal  =  $saldo_awal[$value->coa]['debit'] ?? 0;
            $credit_awal =  $saldo_awal[$value->coa]['credit'] ?? 0;

            // mutasi
            $general_ledger = $value->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'acm'])->where('jb', '0');
            foreach ($general_ledger as $key => $value2) {
                if ($value2->type === 'D') {
                    $val_debit = $val_debit + $value2->value;
                } else {
                    $val_credit = $val_credit + $value2->value;
                }
            }

            // saldo akhir
            if ($value->toCoaBody->toCoaClasification->normal_balance == 'D') {
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
            if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                continue;
            }

            $data[] = [
                'id_coa'        => $value->id_coa,
                'coa'           => $value->coa,
                'name'          => $value->name,

                'credit_awal'   => $credit_awal,
                'debit_awal'    => $debit_awal,

                'credit_mutasi' => $val_credit,
                'debit_mutasi'  => $val_debit,

                'credit_akhir'  => $credit_akhir,
                'debit_akhir'   => $debit_akhir,
            ];
        }

        return ApiResponseClass::sendResponse($data, 'General Ledger Retrieved Successfully');
    }

    public function previousBalance($period)
    {
        $year  = Carbon::parse($period)->format('Y');
        $month = Carbon::parse($period)->format('m');

        $prev_year  = Carbon::parse($period)->subMonth()->format('Y');
        $prev_month = Carbon::parse($period)->subMonth()->format('m');

        $prev_date  = Carbon::parse($period)->subMonth()->endOfMonth()->toDateString();
        $start_date = Carbon::create($year, 1, 1)->toDateString();

        $closing_entry = ClosingEntry::with(['toClosingEntryDetail'])
            ->where(function ($query) use ($year, $prev_year, $prev_month) {
                // Jika $prev_year sama dengan $year, hanya cek sampai $prev_month
                if ($prev_year == $year) {
                    $query->where('year', $prev_year)
                        ->where('month', '<=', $prev_month);
                } else {
                    // Jika tahun berbeda, ambil semua dari tahun sebelumnya
                    $query->where('year', '<', $year);
                }
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        // saldo awal
        $saldo_awal = [];
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
        } else {
            if ($month == '01') {
                // saldo awal bulan 01
                $initial_balance = GeneralLedger::whereMonth('date', $month)->whereYear('date', $year)->where('phase', 'int')->get();
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
            } else {
                // saldo awal prev month
                $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $prev_date])
                    ->where('phase', 'int')->get();
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
        }

        $mutasi_prev = GeneralLedger::whereBetween('date', [$start_date, $prev_date])->whereIn('phase', ['opr', 'acm'])->get();
        $mutasi = $mutasi_prev->groupBy('coa')->map(function ($items) {
            return [
                'debit' => $items->where('type', 'D')->sum('value'),
                'credit' => $items->where('type', 'K')->sum('value'),
            ];
        });

        $get = Coa::orderBy('id_coa_body', 'asc')->get();

        $data = [];
        foreach ($get as $key => $value) {
            $val_debit  = 0;
            $val_credit = 0;

            $credit_akhir =  0;
            $debit_akhir  =  0;

            // normal balance
            if ($value->toCoaBody->toCoaClasification->normal_balance == 'D') {
                $default = 'D';
            } else {
                $default = 'K';
            }

            // saldo awal
            $credit_awal =  $saldo_awal[$value->coa]['credit'] ?? 0;
            $debit_awal  =  $saldo_awal[$value->coa]['debit'] ?? 0;

            // mutasi
            $val_credit =  $mutasi[$value->coa]['credit'] ?? 0;
            $val_debit  =  $mutasi[$value->coa]['debit'] ?? 0;

            // saldo akhir
            if ($default == 'D') {
                $balance_debit  = $debit_awal + ($val_debit - $val_credit);
                $balance_credit = $credit_awal + ($val_debit - $val_credit);

                $debit_akhir  = $balance_debit;
                $credit_akhir = $credit_awal == 0 ? 0 : $balance_credit;
            } else {
                $balance_credit = $credit_awal + ($val_credit - $val_debit);
                $balance_debit  = $debit_awal + ($val_credit - $val_debit);

                $debit_akhir  = $debit_awal == 0 ? 0 : $balance_debit;
                $credit_akhir = $balance_credit;
            }

            if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                continue;
            }

            $data[] = [
                'id_coa' => $value->id_coa,
                'coa'    => $value->coa,
                'name'   => $value->name,

                'credit' => $credit_akhir,
                'debit'  => $debit_akhir,
            ];
        }

        return $data;
    }

    public function previousBalanceYear($period)
    {
        $year  = Carbon::parse($period)->format('Y');
        $month = Carbon::parse($period)->format('m');

        $prev_year  = Carbon::parse($period)->subMonth()->format('Y');
        $prev_month = Carbon::parse($period)->subMonth()->format('m');

        $prev_date  = Carbon::parse($period)->subMonth()->endOfMonth()->toDateString();
        $start_date = Carbon::create($year, 1, 1)->toDateString();

        $closing_entry = ClosingEntry::with(['toClosingEntryDetail'])
            ->where(function ($query) use ($year, $prev_year, $prev_month) {
                // Jika $prev_year sama dengan $year, hanya cek sampai $prev_month
                if ($prev_year == $year) {
                    $query->where('year', $prev_year)
                        ->where('month', '<=', $prev_month);
                } else {
                    // Jika tahun berbeda, ambil semua dari tahun sebelumnya
                    $query->where('year', '<', $year);
                }
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        // saldo awal
        $saldo_awal = [];
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
        } else {
            if ($month == '01') {
                // saldo awal bulan 01
                $initial_balance = GeneralLedger::whereMonth('date', $month)->whereYear('date', $year)->where('phase', 'int')->get();
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
            } else {
                // saldo awal prev month
                $initial_balance = GeneralLedger::whereBetween('date', [$start_date, $prev_date])
                    ->where('phase', 'int')->get();
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
        }

        $mutasi_prev = GeneralLedger::whereBetween('date', [$start_date, $prev_date])->whereIn('phase', ['opr', 'acm'])->get();
        $mutasi = $mutasi_prev->groupBy('coa')->map(function ($items) {
            return [
                'debit' => $items->where('type', 'D')->sum('value'),
                'credit' => $items->where('type', 'K')->sum('value'),
            ];
        });

        $get = Coa::orderBy('id_coa_body', 'asc')->get();

        $data = [];
        foreach ($get as $key => $value) {
            $val_debit  = 0;
            $val_credit = 0;

            $credit_akhir =  0;
            $debit_akhir  =  0;

            // normal balance
            if ($value->toCoaBody->toCoaClasification->normal_balance == 'D') {
                $default = 'D';
            } else {
                $default = 'K';
            }

            // saldo awal
            $credit_awal =  $saldo_awal[$value->coa]['credit'] ?? 0;
            $debit_awal  =  $saldo_awal[$value->coa]['debit'] ?? 0;

            // mutasi
            $val_credit =  $mutasi[$value->coa]['credit'] ?? 0;
            $val_debit  =  $mutasi[$value->coa]['debit'] ?? 0;

            // saldo akhir
            if ($default == 'D') {
                $balance = $debit_awal + ($val_debit - $val_credit);

                $debit_akhir  = $balance;
                $credit_akhir = 0;
            } else {
                $balance = $credit_awal + ($val_credit - $val_debit);

                $debit_akhir  = 0;
                $credit_akhir = $balance;
            }

            if (($credit_awal == 0) && ($debit_awal == 0) && ($val_debit == 0) && ($val_credit == 0) && ($credit_akhir == 0) && ($debit_akhir == 0)) {
                continue;
            }

            $data[] = [
                'id_coa' => $value->id_coa,
                'coa'    => $value->coa,
                'name'   => $value->name,

                'credit' => $credit_akhir,
                'debit'  => $debit_akhir,
            ];
        }

        return $data;
    }
}
