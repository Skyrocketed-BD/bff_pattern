<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\finance\ClosingDepreciation;
use App\Models\finance\ClosingEntry;
use App\Models\finance\ClosingEntryDetail;
use App\Models\finance\Coa;
use App\Models\finance\CoaClasification;
use App\Models\finance\GeneralLedger;
use App\Models\finance\InitialBalance;
use App\Models\finance\JournalClosingEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ClosingEntryController extends Controller
{
    /**
     * @OA\Get(
     *  path="/closing-entries",
     *  summary="Get the list of closing entries",
     *  tags={"Finance - Closing Entries"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $year  = Carbon::createFromFormat('Y', $request->period)->format('Y');

        $closing_entry = ClosingEntry::where('year', $year)->first();

        $data = [];

        if (!$closing_entry) {
            return ApiResponseClass::sendResponse($data, 'Closing Entry Not Found');
        } else {
            $transaction_number = $closing_entry->transaction_number;
            $general_ledger = GeneralLedger::whereTransactionNumber($transaction_number)->get();

            foreach ($general_ledger as $key => $value) {
                $data[] = [
                    'no'                 => $key + 1,
                    'transaction_number' => $value->transaction_number,
                    'date'               => $value->date,
                    'coa'                => $value->coa,
                    'coa_name'           => Coa::whereCoa($value->coa)->first()->name,
                    'type'               => $value->type,
                    'value'              => $value->value,
                    'description'        => $value->description,
                    'reference_number'   => $value->reference_number
                ];
            }

            return ApiResponseClass::sendResponse($data, 'Closing Entries Retrieved Successfully');
        }
    }

    /**
     * @OA\Post(
     *  path="/closing-entries",
     *  summary="Create a new closing entry",
     *  tags={"Finance - Closing Entries"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="period",
     *                  type="integer",
     *                  description="Period"
     *              ),
     *              required={"period"},
     *              example={
     *                  "period": "2022"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $start_date = Carbon::createFromFormat('Y', $request->period)->startOfYear()->format('Y-m-d');
            $end_date   = Carbon::createFromFormat('Y', $request->period)->endOfYear()->format('Y-m-d');
            $year       = Carbon::createFromFormat('Y', $request->period)->format('Y');

            $alreadyClosed = ClosingEntry::where('year', $year)->exists();

            if ($alreadyClosed) {
                return response()->json([
                    'success' => false,
                    'message' => "Year {$year} is already closed.",
                ], 400);
            }

            $previousYear = $year - 1;

            $totalEntries = ClosingEntry::count();

            if ($totalEntries > 0) {
                $previousYearClosed = ClosingEntry::where('year', $previousYear)->exists();

                if (!$previousYearClosed) {
                    return response()->json([
                        'success' => false,
                        'message' => "Year {$year} cannot be closed because year {$previousYear} is not closed yet.",
                    ], 400);
                }
            }
            
            $equity_coa            = Coa::whereIdCoa(get_arrangement('equity_coa'))->first();
            $income_summary_coa    = Coa::whereIdCoa(get_arrangement('income_summary_coa'))->first();
            $retained_earnings_coa = Coa::whereIdCoa(get_arrangement('retained_earnings_coa'))->first();

            $check_closing_entry = ClosingEntry::where('year', $year)->first();

            if ($check_closing_entry) {
                return response()->json([
                    'success' => false,
                    'message' => "'Already Closed on Selected Period !",
                ], 400);
            } else {
                $transaction_number = generate_number('finance', 'closing_entries', 'transaction_number', 'CLS');

                $coa_clasification = CoaClasification::with(['toCoaBody.toCoa'])->get();

                // begin:: jurnal closing
                $coa_closing = JournalClosingEntry::with(['toJournalClosingEntrySet'])->whereYear('date', $year)->get();

                $closing    = [];
                $beban      = [];
                $pendapatan = [];

                foreach ($coa_closing as $key => $value) {
                    foreach ($value->toJournalClosingEntrySet as $key => $value2) {
                        if ($value2->toCoa->toCoaBody->toCoaClasification->group === 'beban') {
                            $beban[] = [
                                'transaction_number' => $transaction_number,
                                'date'               => $end_date,
                                'coa'                => $value2->toCoa->coa,
                                'type'               => 'K',
                                'value'              => $value2->value,
                                'description'        => 'Ikhtisar Laba Rugi | Beban',
                                'reference_number'   => $transaction_number,
                                'phase'              => 'cls',
                                'created_by'         => auth('api')->user()->id_users
                            ];
                        }

                        if ($value2->toCoa->toCoaBody->toCoaClasification->group === 'pendapatan') {
                            $pendapatan[] = [
                                'transaction_number' => $transaction_number,
                                'date'               => $end_date,
                                'coa'                => $value2->toCoa->coa,
                                'type'               => 'D',
                                'value'              => $value2->value,
                                'description'        => 'Ikhtisar Laba Rugi | Pendapatan',
                                'reference_number'   => $transaction_number,
                                'phase'              => 'cls',
                                'created_by'         => auth('api')->user()->id_users
                            ];
                        }

                        $closing[] = [
                            'transaction_number' => $value->transaction_number,
                            'date'               => $end_date,
                            'coa'                => $value2->toCoa->coa,
                            'type'               => $value2->type,
                            'value'              => $value2->value,
                            'description'        => 'Jurnal Penyesuaian',
                            'reference_number'   => $value->transaction_number,
                            'phase'              => 'opr',
                            'created_by'         => auth('api')->user()->id_users
                        ];
                    }
                }
                // end:: jurnal closing

                $prive      = [];
                $coa        = [];
                foreach ($coa_clasification as $key => $value) {
                    //skip untuk modal, dihitung setelah jurnal penutup di general ledger
                    // if ($value->group === 'modal') {
                    //     continue;
                    // }

                    foreach ($value->toCoaBody as $key => $value2) {
                        foreach ($value2->toCoa as $key => $value3) {

                            if ($value3->coa == $income_summary_coa->coa) {
                                continue; //skip untuk ikthisar laba rugi
                            }
                            if ($value3->coa == $equity_coa->coa) {
                                continue; //skip untuk labarugi tahun berjalan
                            }

                            $saldo = _sum_coa_saldo_real($value3, $start_date, $end_date);
                            $coa[] = $value3->coa;

                            if ($value->accrual === 'closed') {
                                if ($value->group === 'beban') {
                                    $nilai_beban = $saldo;
                                    $type_beban  = ($value->normal_balance === 'D') ? ($nilai_beban < 0 ? 'D' : 'K') : ($nilai_beban < 0 ? 'K' : 'D');

                                    if ($nilai_beban != 0) {
                                        $beban[] = [
                                            'transaction_number' => $transaction_number,
                                            'date'               => $end_date,
                                            'coa'                => $value3->coa,
                                            'type'               => $type_beban,
                                            'value'              => $nilai_beban,
                                            'description'        => 'Ikhtisar Laba Rugi | Beban',
                                            'reference_number'   => $transaction_number,
                                            'phase'              => 'cls',
                                            'created_by'         => auth('api')->user()->id_users
                                        ];
                                    }
                                }

                                if ($value->group === 'pendapatan') {
                                    $nilai_pendapatan = $saldo;
                                    $type_pendapatan  = ($value->normal_balance === 'D') ? ($nilai_pendapatan < 0 ? 'D' : 'K') : ($nilai_pendapatan < 0 ? 'K' : 'D');

                                    if ($nilai_pendapatan != 0) {
                                        $pendapatan[] = [
                                            'transaction_number' => $transaction_number,
                                            'date'               => $end_date,
                                            'coa'                => $value3->coa,
                                            'type'               => $type_pendapatan,
                                            'value'              => $nilai_pendapatan,
                                            'description'        => 'Ikhtisar Laba Rugi | Pendapatan',
                                            'reference_number'   => $transaction_number,
                                            'phase'              => 'cls',
                                            'created_by'         => auth('api')->user()->id_users
                                        ];
                                    }
                                }

                                if ($value->group === 'prive') {
                                    $nilai_prive = $saldo;
                                    $type_prive  = ($value->normal_balance === 'D') ? ($nilai_prive < 0 ? 'D' : 'K') : ($nilai_prive < 0 ? 'K' : 'D');

                                    if ($nilai_prive != 0) {
                                        $prive[] = [
                                            'transaction_number' => $transaction_number,
                                            'date'               => $end_date,
                                            'coa'                => $value3->coa,
                                            'type'               => $type_prive,
                                            'value'              => $nilai_prive,
                                            'description'        => 'Jurnal Penutup Prive',
                                            'reference_number'   => $transaction_number,
                                            'phase'              => 'cls',
                                            'created_by'         => auth('api')->user()->id_users
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

                // update column closed gl transaction
                $general_ledger = GeneralLedger::whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr'])->get();
                foreach ($general_ledger as $key => $value) {
                    $value->closed = '1';
                    $value->save();
                }

                $sum_pendapatan = array_sum(array_column($pendapatan, 'value'));
                $sum_beban      = array_sum(array_column($beban, 'value'));
                $sum_prive      = array_sum(array_column($prive, 'value'));
                $profitloss     = ($sum_pendapatan - $sum_beban); // (laba tahun berjalan)

                $laba_bersih_sebelum_pajak = (int) substr($profitloss, 0, -3) . '000';
                $omzet                     = $sum_pendapatan;
                $pph_badan                 = 0;

                $company_category = get_arrangement('company_category');
                $est_date         = get_arrangement('est_date');

                if ($omzet > 4800000000 && $omzet < 50000000000) {
                    $fasilitas     = (4800000000 / $omzet) * $laba_bersih_sebelum_pajak;
                    $non_fasilitas = ($laba_bersih_sebelum_pajak - $fasilitas) * 0.22;
                    $pph_badan     = ($fasilitas * 0.22 * 0.5) + ($non_fasilitas);
                } else if ($omzet > 50000000000) {
                    $pph_badan = $omzet * 0.22;
                } else if ($omzet < 4800000000) {
                    $date       = Carbon::parse($est_date);
                    $count_year = Carbon::now()->diffInYears($date);

                    // umkm pribadi
                    if ($company_category === 'umkm' && $count_year < 7) {
                        $pph_badan = $omzet * 0.5;
                    } else {
                        $pph_badan = $profitloss * 0.22 * 0.5;
                    }

                    // umkm badan
                    if ($company_category === 'badan' && $count_year < 3) {
                        $pph_badan = $omzet * 0.5;
                    } else {
                        $pph_badan = $profitloss * 0.22 * 0.5;
                    }
                }

                $coa_pph_badan      = Coa::find(get_arrangement('coa_pph_badan'))->coa;
                $coa_pph_pasal_22   = Coa::find(get_arrangement('coa_pph_pasal_22'))->coa;
                $coa_pph_pasal_23   = Coa::find(get_arrangement('coa_pph_pasal_23'))->coa;
                $coa_pph_pasal_25   = Coa::find(get_arrangement('coa_pph_pasal_25'))->coa;
                $coa_utang_pajak_29 = Coa::find(get_arrangement('coa_utang_pajak_29'))->coa;

                // ambil laba ditahan tahun ini
                $saldo_laba_ditahan_tahun_lalu = GeneralLedger::whereCoa($retained_earnings_coa->coa)->whereYear('date', $year)->sum('value');
                $laba_ditahan                  = ($saldo_laba_ditahan_tahun_lalu + $profitloss) - $sum_prive;

                // pajak
                $saldo_pph_pasal_22 = GeneralLedger::whereCoa($coa_pph_pasal_22)->whereYear('date', $year)->wherePhase('opr')->sum('value');
                $saldo_pph_pasal_23 = GeneralLedger::whereCoa($coa_pph_pasal_23)->whereYear('date', $year)->wherePhase('opr')->sum('value');
                $saldo_pph_pasal_25 = GeneralLedger::whereCoa($coa_pph_pasal_25)->whereYear('date', $year)->wherePhase('opr')->sum('value');
                $total_saldo_pph    = ($saldo_pph_pasal_22 + $saldo_pph_pasal_23 + $saldo_pph_pasal_25);
                $utang_pajak        = (floor($pph_badan) - $total_saldo_pph);

                // begin:: pph badan
                $coa_pph_badan = [
                    [
                        'coa'   => $coa_pph_badan,
                        'type'  => 'D',
                        'value' => floor($pph_badan),
                        'phase' => 'tax',
                    ],
                    [
                        'coa'   => $coa_pph_pasal_22,
                        'type'  => 'K',
                        'value' => $saldo_pph_pasal_22,
                        'phase' => 'cls',
                    ],
                    [
                        'coa'   => $coa_pph_pasal_23,
                        'type'  => 'K',
                        'value' => $saldo_pph_pasal_23,
                        'phase' => 'cls',
                    ],
                    [
                        'coa'   => $coa_pph_pasal_25,
                        'type'  => 'K',
                        'value' => $saldo_pph_pasal_25,
                        'phase' => 'cls',
                    ],
                    [
                        'coa'   => $coa_utang_pajak_29,
                        'type'  => 'K',
                        'value' => $utang_pajak,
                        'phase' => 'cls',
                    ],
                ];

                foreach ($coa_pph_badan as $key => $value) {
                    $journal_pph_badan[] = [
                        'transaction_number' => $transaction_number,
                        'date'               => $end_date,
                        'coa'                => $value['coa'],
                        'type'               => $value['type'],
                        'value'              => $value['value'],
                        'description'        => 'Jurnal PPH Badan',
                        'reference_number'   => $transaction_number,
                        'phase'              => $value['phase'],
                        'created_by'         => auth('api')->user()->id_users
                    ];
                }
                // end:: pph badan

                if ($sum_pendapatan != 0) {
                    $coa_pendapatan = [
                        'transaction_number' => $transaction_number,
                        'date'               => $end_date,
                        'coa'                => $income_summary_coa->coa,
                        'type'               => 'K',
                        'value'              => $sum_pendapatan,
                        'description'        => 'Ikhtisar Laba Rugi | Pendapatan',
                        'reference_number'   => $transaction_number,
                        'phase'              => 'cls',
                        'created_by'         => auth('api')->user()->id_users
                    ];
                    GeneralLedger::insert($pendapatan);
                    GeneralLedger::create($coa_pendapatan);
                }

                if ($sum_beban != 0) {
                    $coa_beban = [
                        'transaction_number' => $transaction_number,
                        'date'               => $end_date,
                        'coa'                => $income_summary_coa->coa,
                        'type'               => 'D',
                        'value'              => $sum_beban,
                        'description'        => 'Ikhtisar Laba Rugi | Beban',
                        'reference_number'   => $transaction_number,
                        'phase'              => 'cls',
                        'created_by'         => auth('api')->user()->id_users
                    ];
                    GeneralLedger::create($coa_beban);
                    GeneralLedger::insert($beban);
                }

                if ($laba_ditahan < 0) {
                    // negatif
                    $coa_modal = [
                        [
                            'transaction_number' => $transaction_number,
                            'date'               => $end_date,
                            'coa'                => $equity_coa->coa,              //laba tahun berjalan
                            'type'               => 'D',
                            'value'              => abs($laba_ditahan),
                            'description'        => 'Profit Loss | Modal',
                            'reference_number'   => $transaction_number,
                            'phase'              => 'cls',
                            'created_by'         => auth('api')->user()->id_users
                        ],
                        [
                            'transaction_number' => $transaction_number,
                            'date'               => $end_date,
                            'coa'                => $income_summary_coa->coa,       //ikhtisar laba rugi
                            'type'               => 'K',
                            'value'              => abs($laba_ditahan),
                            'description'        => 'Ikhtisar Laba Rugi | Modal',
                            'reference_number'   => $transaction_number,
                            'phase'              => 'cls',
                            'created_by'         => auth('api')->user()->id_users
                        ]
                    ];
                } else {
                    // positif
                    $coa_modal = [
                        [
                            'transaction_number' => $transaction_number,
                            'date'               => $end_date,
                            'coa'                => $income_summary_coa->coa,       //ikhtisar laba rugi
                            'type'               => 'D',
                            'value'              => abs($laba_ditahan),
                            'description'        => 'Ikhtisar Laba Rugi | Modal',
                            'reference_number'   => $transaction_number,
                            'phase'              => 'cls',
                            'created_by'         => auth('api')->user()->id_users
                        ],
                        [
                            'transaction_number' => $transaction_number,
                            'date'               => $end_date,
                            'coa'                => $equity_coa->coa,              //laba tahun berjalan
                            'type'               => 'K',
                            'value'              => abs($laba_ditahan),
                            'description'        => 'Profit Loss | Modal',
                            'reference_number'   => $transaction_number,
                            'phase'              => 'cls',
                            'created_by'         => auth('api')->user()->id_users
                        ],
                    ];
                }

                if ($profitloss != 0) {
                    GeneralLedger::insert($coa_modal);
                    GeneralLedger::insert($journal_pph_badan);
                    GeneralLedger::insert($closing);
                }

                if ($sum_prive != 0) {
                    $coa_prive = [
                        'transaction_number' => $transaction_number,
                        'date'               => $end_date,
                        'coa'                => $income_summary_coa->coa,
                        'type'               => 'D',
                        'value'              => $sum_prive,
                        'description'        => 'Menutup Akun Prive',
                        'reference_number'   => $transaction_number,
                        'phase'              => 'cls',
                        'created_by'         => auth('api')->user()->id_users
                    ];
                    GeneralLedger::create($coa_prive);
                    GeneralLedger::insert($prive);
                }

                if ($sum_pendapatan != 0 || $sum_beban != 0 || $sum_prive != 0 || $profitloss != 0) {
                    // siapkan no transaksi initial balance
                    $tahun_berikut          = ($year + 1);
                    $transaction_number_sa  = generate_number('finance', 'initial_balances', 'transaction_number', 'SA');

                    // insert ke closing entry
                    $closing_entry = new ClosingEntry();
                    // untuk kebutuhan buka closing, nanti tambahkan ref_number initial balance
                    // $closing_entry->ref_number        = $transaction_number_sa;
                    $closing_entry->transaction_number = $transaction_number;
                    $closing_entry->year               = $year;
                    $closing_entry->save();

                    // transaksi initial balance untuk tahun depan
                    $initial_balance = new InitialBalance();
                    $initial_balance->transaction_number = $transaction_number_sa;
                    $initial_balance->date               = $tahun_berikut . "-01-01";
                    $initial_balance->description        = "Saldo Awal Laba Ditahan";
                    $initial_balance->value              = $laba_ditahan;
                    $initial_balance->save();

                    // tampung array initial balance untuk tahun depan khusus laba ditahan
                    $initial_balance_next_year[] = [
                        'transaction_number' => $transaction_number_sa,
                        'date'               => $tahun_berikut . "-01-01",
                        'coa'                => $retained_earnings_coa->coa,   //laba ditahan
                        'type'               => 'K',
                        'value'              => $laba_ditahan,
                        'description'        => "Laba Ditahan",
                        'reference_number'   => $transaction_number_sa,
                        'phase'              => 'int',
                        'created_by'         => auth('api')->user()->id_users
                    ];

                    // mengurangi loop_ced, langsung saja di sini insert ke ClosingEntryDetail
                    // sekaligus menampung array initial balance buat di insert ke GL
                    // foreach ($closing_entry_detail['coa'] as $key => $value) {
                    foreach ($coa as $key => $value) {
                        $coa_clasification   = Coa::where('coa', $value)->first()->toCoaBody->toCoaClasification;
                        $debit              = $this->_sum_coa_value($value, 'D', $start_date, $end_date);
                        $credit             = $this->_sum_coa_value($value, 'K', $start_date, $end_date);
                        $group              = $coa_clasification->group;
                        $normal_balance     = $coa_clasification->normal_balance;
                        $balance            = 0;

                        // skip untuk laba ditahan
                        if ($value == $retained_earnings_coa->coa) {
                            continue;
                        }
                        // pilih yang group harta dan utang saja
                        if ($group == 'harta' || $group == 'utang' || $group == 'modal') {
                            if ($normal_balance == 'D') {
                                $balance = $debit - $credit;
                                $type    = 'D';
                            } else {
                                $balance = $credit - $debit;
                                $type    = 'K';
                            }
                        }

                        // tampung ke array intial_balance_next_year
                        $initial_balance_next_year[] = [
                            'transaction_number' => $transaction_number_sa,
                            'date'               => $tahun_berikut . "-01-01",
                            'coa'                => $value,
                            'type'               => $type,
                            'value'              => $balance,
                            'description'        => "Saldo Awal",
                            'reference_number'   => $transaction_number_sa,
                            'phase'              => 'int',
                            'created_by'         => auth('api')->user()->id_users
                        ];

                        $closing_entry_detail = new ClosingEntryDetail();
                        $closing_entry_detail->id_closing_entry = $closing_entry->id_closing_entry;
                        $closing_entry_detail->coa              = $value;
                        $closing_entry_detail->debit            = $debit;
                        $closing_entry_detail->credit           = $credit;
                        $closing_entry_detail->save();
                    }

                    GeneralLedger::insert($initial_balance_next_year);

                    ActivityLogHelper::log('finance:closing_entry', 1, [
                        'description'   => 'Successfully closed the period ' . $request->period,
                    ]);

                    DB::connection('finance')->commit();
                    return ApiResponseClass::sendResponse($closing_entry, 'Journal Entry Created Successfully');
                }

                return response()->json(['success' => false, 'message' => 'Nothing to Close on Selected Period'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:closing_entry', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    // open period closing
    /**
     * @OA\Post(
     *  path="/closing-entries/open",
     *  summary="Open Period Closing",
     *  tags={"Finance - Closing Entries"},
     *  @OA\Parameter(
     *      name="period",
     *      in="query",
     *      required=true,
     *      @OA\Schema(type="string")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function open(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $year = Carbon::createFromFormat('Y', $request->period)->format('Y');

            $check_closing_entry = ClosingEntry::with(['toClosingEntryDetail'])->where('year', $year)->first();

            if ($check_closing_entry) {
                $phase  = 'opr';
                $closed = '1';
                $date   = $check_closing_entry->year;

                GeneralLedger::where('phase', $phase)->where('closed', $closed)->whereYear('date', $date)->update([
                    'phase'  => 'opr',
                    'closed' => '0',
                ]);

                GeneralLedger::where('transaction_number', $check_closing_entry->transaction_number)->delete();

                // begin:: closing entry
                $check_closing_entry->toClosingEntryDetail->each(function ($row) {
                    $row->delete();
                });

                $check_closing_entry->delete();
                // end:: closing entry

                // begin:: initial balance
                $year_next = $year + 1;
                $check_initial_balance = InitialBalance::whereYear('date', $year_next)->get();
                $check_initial_balance->each(function ($row) {
                    $row->update([
                        'status' => 'deleted'
                    ]);

                    GeneralLedger::where('transaction_number', $row->transaction_number)->delete();
                });
                // end:: initial balance
            }

            DB::connection('finance')->commit();

            return Response::json(['success' => true, 'message' => 'Period Opened Successfully ' . $year], 200);
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function _sum_coa_value($coa, $dk, $start_date, $end_date)
    {
        $sum = GeneralLedger::whereCoa($coa)->whereType($dk)->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');

        return $sum;
    }

    /**
     * This private function checks if all months of the previous year are closed.
     * It ensures that there are no unclosed entries for the previous year.
     * Note: This function is not in use currently.
     * @param int $year
     * @return bool
     */
    private function _checkClosingYear($year): bool
    {
        $previousYear = $year - 1;

        // Early exit if there's no data for previous year at all
        $existing = ClosingDepreciation::where('year', $previousYear)->count();

        if ($existing < 12) {
            return false; // Some or all months are missing
        }

        // Check for unclosed months
        $unclosed = ClosingEntry::where('year', $previousYear)
            ->whereNull('closed_at')
            ->count();

        return $unclosed === 0;
    }
}
