<?php

use App\Models\finance\AssetHead;
use Carbon\Carbon;
use App\Models\finance\Coa;
use App\Models\finance\DownPayment;
use App\Models\finance\DownPaymentDetails;
use App\Models\finance\Expenditure;
use App\Models\finance\GeneralLedger;
use App\Models\finance\InvoiceBill;
use App\Models\finance\Journal;
use App\Models\finance\Liability;
use App\Models\finance\LiabilityDetail;
use App\Models\finance\Receipts;
use App\Models\finance\ReportFormula;
use App\Models\finance\ReportTitle;
use App\Models\finance\TaxRate;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use App\Models\finance\TransactionTax;
use Illuminate\Support\Facades\DB;

if (!function_exists('_count_report_title_formula')) {
    function _count_report_title_formula($id_report_title, $start_date, $end_date)
    {
        $report_formula = ReportFormula::where('id_report_title', $id_report_title)->get();

        $total = 0;

        foreach ($report_formula as $key => $value) {
            $result = _count_report_title_total($value->id_report_title_select, $start_date, $end_date);

            if ($value->operation === '+') {
                $total = $total + $result;
            } else if ($value->operation === '-') {
                $total = $total - $result;
            } else if ($value->operation === '*') {
                $total = $total * $result;
            } else if ($value->operation === '/') {
                $total = $total / $result;
            }
        }

        return $total;
    }
}

if (!function_exists('_count_report_title_total')) {
    function _count_report_title_total($id_report_title, $start_date, $end_date)
    {
        $report_title = ReportTitle::with(['toReportBody'])->find($id_report_title);
        $coa_labarugi_berjalan = Coa::find(get_arrangement('equity_coa'))->coa;

        $total = 0;
        if ($report_title->type === 'formula') {
            $total = _count_report_title_formula($id_report_title, $start_date, $end_date);
        } else if ($report_title->type === 'input') {
            $total = $report_title->value;
        } else {
            // default
            $report_body = $report_title->toReportBody;

            foreach ($report_body as $key => $value2) {
                $balance = 0;

                if ($value2->method === 'coa') {
                    if ($value2->toCoa->coa == $coa_labarugi_berjalan) {
                        $balance = _sum_pendapatan_beban($start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                    } else {
                        $balance = _sum_account_saldo($value2, $start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                    }
                }

                if ($value2->method === 'subcoa') {
                    $balance = _count_coa_body($value2->id_coa_body, $start_date, $end_date);
                }

                if ($value2->method === 'range') {
                    $balance = _sum_account_saldo($value2, $start_date, $start_date, ['opr', 'int', 'acm', 'tax']);
                }

                if ($value2->method === 'report') {
                    $balance = _count_report_menu_total($value2->id_report_menu, $start_date, $end_date);
                }

                if ($value2->operation === '+') {
                    $total = $total + $balance;
                } else if ($value2->operation === '-') {
                    $total = $total - $balance;
                } else if ($value2->operation === '*') {
                    $total = $total * $balance;
                } else if ($value2->operation === '/') {
                    $total = $total / $balance;
                }
            }
        }

        return $total;
    }
}

if (!function_exists('_count_report_menu_total')) {
    function _count_report_menu_total($id_report_menu, $start_date, $end_date)
    {
        $report_title = ReportTitle::whereIdReportMenu($id_report_menu)->get();

        $total = [];
        foreach ($report_title as $key => $value) {
            if ($value->type === 'formula') {
                $nilai = _count_report_title_formula($value->id_report_title, $start_date, $end_date);
            } else if ($value->type === 'input') {
                $nilai = $value->value;
            } else {
                // default
                $nilai = _count_report_title_total($value->id_report_title, $start_date, $end_date);
            }

            $total[] =  $nilai;
        }

        end($total);

        $key = key($total);

        return $total[$key] ?? 0;
    }
}

if (!function_exists('_count_coa_body')) {
    function _count_coa_body($id_coa_body, $start_date, $end_date)
    {
        $coa = Coa::whereIdCoaBody($id_coa_body)->get();

        $total = 0;

        foreach ($coa as $key => $value) {
            $total += _sum_coa_saldo($value, $start_date, $end_date);
        }

        return $total;
    }
}

if (!function_exists('_sum_account_saldo')) {
    function _sum_account_saldo($coa, $start_date, $end_date, $phase)
    {
        $balance     = 0;
        $totalDebit  = 0;
        $totalCredit = 0;

        $totalDebit  = $coa->toCoa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'D')->whereIn('phase', $phase)->sum('value');
        $totalCredit = $coa->toCoa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'K')->whereIn('phase', $phase)->sum('value');

        if ($coa->toCoa->toCoaBody->toCoaClasification->normal_balance == 'D') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return $balance;
    }
}

// menghitung saldo akhir berdasarkan coa
if (!function_exists('_sum_coa_saldo')) {
    function _sum_coa_saldo($coa, $start_date, $end_date)
    {
        $balance     = 0;
        $totalDebit  = 0;
        $totalCredit = 0;

        $totalDebit  = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'D')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');
        $totalCredit = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'K')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');

        if ($coa->toCoaBody->toCoaClasification->normal_balance == 'D') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return $balance;
    }
}

if (!function_exists('_sum_coa_saldo_real')) {
    function _sum_coa_saldo_real($coa, $start_date, $end_date)
    {
        $balance     = 0;
        $totalDebit  = 0;
        $totalCredit = 0;

        $totalDebit  = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'D')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');
        $totalCredit = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'K')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');

        if ($coa->toCoaBody->toCoaClasification->normal_balance == 'D') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return $balance;
    }
}

if (!function_exists('_check_transaction')) {
    function _check_transaction($id_transaction, $table)
    {
        if ($table === 'asset_head') {
            $check_asset_head = AssetHead::where('id_transaction_full', $id_transaction)->orWhere('id_transaction', $id_transaction)->first();

            if ($check_asset_head) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('_sum_pendapatan_beban')) {
    function _sum_pendapatan_beban($start_date, $end_date, $phase)
    {
        // Ambil semua CoA dengan klasifikasi 'beban' dan 'pendapatan'
        $coas = Coa::whereHas('toCoaBody.toCoaClasification', function ($query) {
            $query->whereIn('group', ['beban', 'pendapatan']);
        })
            ->with([
                'toGeneralLedger' => function ($query) use ($start_date, $end_date) {
                    $query->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'int', 'acm']);
                },
                'toCoaBody.toCoaClasification'
            ])
            ->get();

        // Pisahkan saldo beban dan pendapatan dalam satu loop
        $saldo_beban = 0;
        $saldo_pendapatan = 0;

        foreach ($coas as $coa) {
            $generalLedgers = $coa->toGeneralLedger;
            $normal_balance = $coa->toCoaBody->toCoaClasification->normal_balance ?? 'D';
            $group = $coa->toCoaBody->toCoaClasification->group;

            $debit = $generalLedgers->where('type', 'D')->sum('value');
            $credit = $generalLedgers->where('type', 'K')->sum('value');

            $saldo = $normal_balance === 'D' ? $debit - $credit : $credit - $debit;

            if ($group === 'beban') {
                $saldo_beban += $saldo;
            } elseif ($group === 'pendapatan') {
                $saldo_pendapatan += $saldo;
            }
        }

        // Hitung balance akhir
        $balance = $saldo_pendapatan - $saldo_beban;

        return $balance;
    }
}

if (!function_exists('_numberToWords')) {
    function _numberToWords($lang, $number)
    {
        $number = abs($number);

        if ($lang == 'id') {
            return _terbilang($number);
        } else {
            return _terbilang_en($number);
        }
    }
}

if (!function_exists('_terbilang')) {
    function _terbilang($number)
    {
        $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

        if ($number < 12) {
            return $huruf[$number];
        } elseif ($number < 20) {
            return _terbilang($number - 10) . " Belas";
        } elseif ($number < 100) {
            return _terbilang(floor($number / 10)) . " Puluh " . _terbilang($number % 10);
        } elseif ($number < 200) {
            return "Seratus " . _terbilang($number - 100);
        } elseif ($number < 1000) {
            return _terbilang(floor($number / 100)) . " Ratus " . _terbilang($number % 100);
        } elseif ($number < 2000) {
            return "Seribu " . _terbilang($number - 1000);
        } elseif ($number < 1000000) {
            return _terbilang(floor($number / 1000)) . " Ribu " . _terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            return _terbilang(floor($number / 1000000)) . " Juta " . _terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            return _terbilang(floor($number / 1000000000)) . " Miliar " . _terbilang($number % 1000000000);
        } elseif ($number < 1000000000000000) {
            return _terbilang(floor($number / 1000000000000)) . " Triliun " . _terbilang($number % 1000000000000);
        } else {
            return "angka terlalu besar";
        }
    }
}

if (!function_exists('_terbilang_en')) {
    function _terbilang_en($number)
    {
        $words = [
            "",
            "One",
            "Two",
            "Three",
            "Four",
            "Five",
            "Six",
            "Seven",
            "Eight",
            "Nine",
            "Ten",
            "Eleven",
            "Twelve",
            "Thirteen",
            "Fourteen",
            "Fifteen",
            "Sixteen",
            "Seventeen",
            "Eighteen",
            "Nineteen"
        ];

        $tens = [
            "",
            "",
            "Twenty",
            "Thirty",
            "Forty",
            "Fifty",
            "Sixty",
            "Seventy",
            "Eighty",
            "Ninety"
        ];

        if ($number < 20) {
            return $words[$number];
        } elseif ($number < 100) {
            return $tens[intval($number / 10)] . ($number % 10 ? "-" . strtolower($words[$number % 10]) : "");
        } elseif ($number < 1000) {
            return $words[intval($number / 100)] . " Hundred" . ($number % 100 ? " and " . _terbilang_en($number % 100) : "");
        } elseif ($number < 1000000) {
            return _terbilang_en(intval($number / 1000)) . " Thousand" . ($number % 1000 ? " " . _terbilang_en($number % 1000) : "");
        } elseif ($number < 1000000000) {
            return _terbilang_en(intval($number / 1000000)) . " Million" . ($number % 1000000 ? " " . _terbilang_en($number % 1000000) : "");
        } elseif ($number < 1000000000000) {
            return _terbilang_en(intval($number / 1000000000)) . " Billion" . ($number % 1000000000 ? " " . _terbilang_en($number % 1000000000) : "");
        } elseif ($number < 1000000000000000) {
            return _terbilang_en(intval($number / 1000000000000)) . " Trillion" . ($number % 1000000000000 ? " " . _terbilang_en($number % 1000000000000) : "");
        } else {
            return "number too large";
        }
    }
}

if (!function_exists('generateFinNumber')) {
    function generateFinNumber($table, $key, $kode): string
    {
        $year   = Carbon::now()->format('Y');
        $month  = angka_romawi(Carbon::now()->format('m'));
        $company_initial = get_arrangement('company_initial');

        // 0001/INV/SSP/KEU/III/2025
        // "{$formattedGlobal}/INV/{$company_initial}/FIN/{$month}/{$year}";
        $full_prefix = "/{$kode}/{$company_initial}/FIN/{$month}/{$year}";

        $last_global = DB::connection('finance')
            ->table($table)
            ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX($key, '/', 1) AS UNSIGNED)) AS max_global"))
            ->whereRaw("RIGHT($key, LENGTH('$full_prefix')) = '$full_prefix'")
            ->whereYear('created_at', $year)
            ->first();
        $globalNumber = ($last_global->max_global ?? 0) + 1;

        $formattedGlobal = str_pad($globalNumber, 4, '0', STR_PAD_LEFT);

        return "{$formattedGlobal}/{$kode}/{$company_initial}/FIN/{$month}/{$year}";
    }
}

// untuk mengambil journal automatic
if (!function_exists('_get_journal_automatic')) {
    function _get_journal_automatic($id_journal, $request = null)
    {
        $journal = Journal::with([
            'toJournalSet.toCoa.toTaxCoa',
            'toJournalSet.toTaxRate',
            'toJournalSet' => function ($query) {
                $query->orderBy('serial_number', 'asc');
            },
        ])->find($id_journal);

        $count_journal = $journal->toJournalSet->count();

        $get_journal = [];
        $another_journal = [];
        $transaction_tax = [];

        $tax_default = TaxRate::find(get_arrangement('default_ppn'))->rate;

        if ($count_journal > 2) {
            $journal_beban  = [];

            // begin:: beban / biaya
            if (isset($request->dataBeban)) {
                if (count($request->dataBeban) > 0) {
                    foreach ($request->dataBeban as $key => $value) {
                        if (is_array($value)) {
                            $value = (object) $value; // Ubah array menjadi objek
                        }

                        /**
                         * diubah untuk kebutuhan data beban jika lebih dari 1, untuk menghitung dpp secara otomatis dan yang diinput adalah total invoice.
                         */
                        if ($request->in_ex_tax === 'n') {
                            $journal_beban[$value->coa] = [
                                'amount' => round($value->amount / (1 + ($tax_default / 100))),
                                'posisi' => $value->posisi
                            ];
                        } else {
                            $journal_beban[$value->coa] = [
                                'amount' => $value->amount,
                                'posisi' => $value->posisi
                            ];
                        }
                    }
                }
            }
            // begin:: beban / biaya

            // begin:: journal discount
            if (isset($request->discount)) {
                if ($request->discount > 0) {
                    if ($journal->category === 'penerimaan') {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('receive_coa_discount'))->first()->coa,
                            'type'   => "D",
                            'piece'  => "y",
                            'ppn'    => "n",
                            'amount' => $request->discount
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('expense_coa_discount'))->first()->coa,
                            'type'   => "K",
                            'piece'  => "y",
                            'ppn'    => "n",
                            'amount' => $request->discount
                        ];
                    }
                }
            }
            // end:: journal discount

            // begin:: journal deposit
            if (isset($request->deposit)) {
                $get_journal = _has_deposit($request, $journal->category);
            }
            // end:: journal deposit

            // begin:: journal interface
            foreach ($journal->toJournalSet as $key => $value) {
                if ($value->toCoa->toTaxCoa) {
                    $transaction_tax[] = [
                        'id_coa'      => $value->toCoa->id_coa,
                        'id_tax'      => $value->toTaxRate->id_tax,
                        'id_tax_rate' => $value->toTaxRate->id_tax_rate,
                        'rate'        => $value->toTaxRate->rate
                    ];

                    if ($value->toCoa->toTaxCoa->toTax->category === 'ppn') {
                        if ($value->toTaxRate->rate != 0) {
                            $get_journal[] = [
                                'rate'   => ($value->toTaxRate->rate / 100),
                                'type'   => $value->type,
                                'coa'    => $value->toCoa->coa,
                                'piece'  => 'y',
                                'ppn'    => 'y',
                                'amount' => 0
                            ];
                        } else {
                            if ($value->toTaxRate->count === 'y') {
                                // jika category === ppn dan tax rate 0%  dan count === y / ppn dibebaskan
                                $another_journal[] = [
                                    'rate'   => ($value->toTaxRate->rate / 100),
                                    'type'   => $value->type,
                                    'coa'    => $value->toCoa->coa,
                                    'ppn'    => 'y',
                                    'amount' => 0
                                ];
                            } else {
                                // jika category === ppn dan tax rate 0%  dan count === n / ppn nol (Bu Cici)
                                $another_journal[] = [
                                    'rate'   => ($tax_default / 100),
                                    'type'   => $value->type,
                                    'coa'    => $value->toCoa->coa,
                                    'ppn'    => 'y',
                                    'amount' => 0
                                ];
                            }
                        }
                    } else {
                        $get_journal[] = [
                            'rate'   => ($value->toTaxRate->rate / 100),
                            'type'   => $value->type,
                            'coa'    => $value->toCoa->coa,
                            'piece'  => 'y',
                            'ppn'    => 'n',
                            'amount' => 0
                        ];
                    }
                } else {
                    if (isset($journal_beban[$value->toCoa->coa])) {
                        $get_journal[] = [
                            'rate'   => null,
                            'type'   => $value->type,
                            'coa'    => $value->toCoa->coa,
                            'piece'  => 'y',
                            'ppn'    => 'n',
                            'amount' => $journal_beban[$value->toCoa->coa]['amount']
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'type'   => $value->type,
                            'coa'    => $value->toCoa->coa,
                            'piece'  => "n",
                            'ppn'    => "n",
                            'amount' => 0
                        ];
                    }
                }
            }
            // end:: journal interface
        } else {
            // begin:: journal deposit
            if (isset($request->deposit)) {
                $get_journal = _has_deposit($request, $journal->category);
            }
            // end:: journal deposit

            foreach ($journal->toJournalSet as $key => $value) {
                $get_journal[] = [
                    'rate'   => null,
                    'type'   => $value->type,
                    'coa'    => $value->toCoa->coa,
                    'piece'  => "n",
                    'ppn'    => "n",
                    'amount' => 0
                ];
            }
        }

        return [
            'get_journal'     => $get_journal,
            'another_journal' => $another_journal,
            'transaction_tax' => $transaction_tax
        ];
    }
}

// untuk mengambil jika memiliki ppn
if (!function_exists('_has_ppn')) {
    function _has_ppn($journal)
    {
        $result = in_array('y', array_column($journal, 'ppn'), true);

        return $result;
    }
}

// untuk mengambil jika memiliki deposit
if (!function_exists('_has_deposit')) {
    function _has_deposit($request, $category)
    {
        if ($request->deposit == 'advance_payment') {
            $result[] = [
                'rate'   => null,
                'coa'    => Coa::whereIdCoa(get_arrangement('advance_payment_adjustment_coa'))->first()->coa,
                'type'   => "D",
                'piece'  => "y",
                'ppn'    => "n",
                'amount' => $request->deposit_total
            ];
        }

        return $result;
    }
}

// untuk hitung nilai journal
if (!function_exists('_count_journal')) {
    function _count_journal($request, $transaction_number = null)
    {
        $journal_automatic = _get_journal_automatic($request->id_journal, $request);

        $get_journal = $journal_automatic['get_journal'];

        $another_journal = $journal_automatic['another_journal'];

        $transaction_tax = $journal_automatic['transaction_tax'];

        $tax_default = TaxRate::find(get_arrangement('default_ppn'))->rate;

        // begin:: check transaction
        $hasPpn = false;

        if (isset($request->reference_number) && $request->reference_number != null) {
            $check_transaction = Transaction::where('reference_number', $request->reference_number)->first();

            if ($check_transaction) {
                $id_journal_transaction = $check_transaction->id_journal;

                $journal_transaction = _get_journal_automatic($id_journal_transaction);

                $hasPpn = _has_ppn($journal_transaction['get_journal']);
            }
        }
        // end:: check transaction

        // begin:: filter hanya yang ppn = y
        $ppn = [];
        foreach ($get_journal as $row) {
            if ($row['ppn'] === 'y') {
                $ppn[$row['coa']] = $row['ppn'];
            }
        }
        // end:: filter hanya yang ppn = y

        $dpp   = 0;
        $total = 0;

        // begin:: untuk check include, exclude or no (y, n, o)
        if (isset($request->in_ex_tax)) {
            if ($request->in_ex_tax === 'y') {
                $total += $request->total;

                $dpp += $request->total;

                foreach ($get_journal as $key => $value) {
                    if ($value['ppn'] === 'y') {
                        $total += $request->total * $value['rate'];
                    }
                }

                foreach ($another_journal as $key => $value) {
                    if ($value['ppn'] === 'y') {
                        $total += $request->total * $value['rate'];
                    }
                }

                /**
                 * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
                 */
                // if (isset($request->dataBeban)) {
                //     if (count($request->dataBeban) > 0) {
                //         foreach ($request->dataBeban as $key => $value) {
                //             if (is_array($value)) {
                //                 $value = (object) $value; // Ubah array menjadi objek
                //             }

                //             $total += $value->amount;
                //         }
                //     }
                // }
            } else if ($request->in_ex_tax === 'n') {
                foreach ($get_journal as $key => $value) {
                    if ($value['ppn'] === 'y') {
                        $dpp += round($request->total / (1 + $value['rate']), 2);

                        $total += round($request->total / (1 + $value['rate']), 2);
                    }
                }

                foreach ($another_journal as $key => $value) {
                    if ($value['ppn'] === 'y') {
                        $dpp += round($request->total / (1 + $value['rate']), 2);

                        $total += round($request->total / (1 + $value['rate']), 2);
                    }
                }

                foreach ($get_journal as $key => $value) {
                    if ($value['ppn'] === 'y') {
                        $total += $total * $value['rate'];
                    }
                }

                foreach ($another_journal as $key => $value) {
                    if ($value['ppn'] === 'y') {
                        $total += $total * $value['rate'];
                    }
                }

                /**
                 * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
                 */
                // if (isset($request->dataBeban)) {
                //     if (count($request->dataBeban) > 0) {
                //         foreach ($request->dataBeban as $key => $value) {
                //             if (is_array($value)) {
                //                 $value = (object) $value; // Ubah array menjadi objek
                //             }

                //             $total += $value->amount;
                //         }
                //     }
                // }
            } else {
                $total = $request->total;

                $dpp = $request->total;

                $check_coa_tax = array_filter($get_journal, function ($row) {
                    return $row['rate'] !== null;
                });

                // untuk mengecek apa bila jurnal memiliki component tax hpp & ppn pada transaction
                if (count($check_coa_tax) > 0 && $hasPpn) {
                    // untuk mencari dpp
                    $dpp = round($request->total / (1 + ($tax_default / 100)), 2);

                    $total = $request->total;
                }
            }
        }

        $total = round($total);

        $journal_credit = [];
        $journal_debit  = [];

        $debit  = [];
        $credit = [];

        foreach ($get_journal as $key => $value) {
            $amount = ($value['amount'] <= 0) ? floor(($dpp * $value['rate'])) : $value['amount'];

            if ($value['type'] === 'K') {
                $journal_credit[] = [
                    'type'  => $value['type'],
                    'coa'   => $value['coa'],
                ];

                if ($value['piece'] === 'y') {
                    $credit[$key] = $amount;
                } else {
                    $credit[$key] = $value['amount'];
                }
            } else {
                $journal_debit[] = [
                    'type'  => $value['type'],
                    'coa'   => $value['coa'],
                ];

                if ($value['piece'] === 'y') {
                    $debit[$key] = $amount;
                } else {
                    $debit[$key] = $value['amount'];
                }
            }
        }

        $get_debit  = [];
        $get_credit = [];

        foreach ($debit as $key => $value) {
            if (count($debit) > 1) {
                if ($value <= 0) {
                    $value = remainder($debit, $total);

                    $calculated = '1';
                } else {
                    $value = $value;

                    $calculated = '0';
                }
            } else {
                if ($request->in_ex_tax === 'y') {
                    $value = $total;
                } else {
                    $value = $request->total;
                }

                $calculated = '1';
            }

            $get_debit[$key] = [
                'value'      => $value,
                'calculated' => $calculated
            ];
        }

        foreach ($credit as $key => $value) {
            if (count($credit) > 1) {
                if ($value <= 0) {
                    $value = remainder($credit, $total);

                    $calculated = '1';
                } else {
                    $value = $value;

                    $calculated = '0';
                }
            } else {
                if ($request->in_ex_tax === 'y') {
                    $value = $total;
                } else {
                    $value = $request->total;
                }

                $calculated = '1';
            }

            $get_credit[$key] = [
                'value'      => $value,
                'calculated' => $calculated
            ];
        }

        $another_credit = [];
        $another_debit = [];

        foreach ($another_journal as $key => $val) {
            if ($val['type'] === 'K') {
                $journal_credit[] = [
                    'type'  => $val['type'],
                    'coa'   => $val['coa'],
                ];

                $another_credit[] = [
                    'value'      => $val['amount'],
                    'calculated' => '0'
                ];
            } else {
                $journal_debit[] = [
                    'type'  => $val['type'],
                    'coa'   => $val['coa'],
                ];

                $another_debit[] = [
                    'value'      => $val['amount'],
                    'calculated' => '0'
                ];
            }
        }

        $last_debit  = array_merge($get_debit, $another_debit);
        $last_credit = array_merge($get_credit, $another_credit);

        $journal_debit_sort  = array_values($journal_debit);
        $journal_credit_sort = array_values($journal_credit);

        foreach ($journal_debit_sort as $key => $val) {
            $result[] = [
                'coa'        => $val['coa'],
                'type'       => $val['type'],
                'value'      => $last_debit[$key]['value'],
                'ppn'        => $ppn[$val['coa']] ?? 'n',
                'calculated' => $last_debit[$key]['calculated'],
            ];
        }

        foreach ($journal_credit_sort as $key => $val) {
            $result[] = [
                'coa'        => $val['coa'],
                'type'       => $val['type'],
                'value'      => $last_credit[$key]['value'],
                'ppn'        => $ppn[$val['coa']] ?? 'n',
                'calculated' => $last_credit[$key]['calculated'],
            ];
        }

        $list_debit = [];
        $list_credit = [];

        foreach ($result as $key => $val) {
            if ($val['type'] === 'D') {
                $list_debit[] = $val['value'];
            } else {
                $list_credit[] = $val['value'];
            }
        }

        $sum_debit  = array_sum($list_debit);
        $sum_credit = array_sum($list_credit);
        $balance    = ($sum_debit - $sum_credit);

        if (round($balance) != 0) {
            return false;
        } else {
            if (count($transaction_tax) != 0) {
                $check_transaction_tax = TransactionTax::whereTransactionNumber($transaction_number)->get()->count();

                if ($check_transaction_tax > 0) {
                    TransactionTax::whereTransactionNumber($transaction_number)->delete();
                }

                foreach ($transaction_tax as $key => $val) {
                    $transaction_tax[$key]['transaction_number'] = $transaction_number;
                }

                TransactionTax::insert($transaction_tax);
            }

            return $result;
        }
    }
}

if (!function_exists('book_value')) {
    function book_value($first_date, $price, $rate)
    {
        $currentYear  = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $currentDay   = Carbon::now()->endOfMonth()->day;

        $set_date = $currentYear . '-' . $currentMonth . '-' . $currentDay;

        $monthDifference = count_cut_off($first_date, $set_date);

        $rate                = ($rate / 100);
        $depreciation        = round(($price * $rate) * (1 / 12), 0);
        $depreciation_amount = ($depreciation * $monthDifference);
        $gl                  = ($price - $depreciation_amount);

        return $gl;
    }
}

if (!function_exists('_calculate_remaining_balance')) {
    // untuk menghitung sisa transaction 
    function _calculate_remaining_balance($reference_number)
    {
        $transaction = Transaction::where('reference_number', $reference_number)->first();

        $remaining_balance = 0;
        $payment = 0;

        if ($transaction) {
            $total = $transaction->value;

            if ($transaction->toReceipts) {
                $payment += $transaction->toReceipts->where('status', 'valid')->sum('value');
            }

            if ($transaction->toExpenditure) {
                $payment += $transaction->toExpenditure->where('status', 'valid')->sum('value');
            }

            $remaining_balance = ($total - $payment);
        }

        return $remaining_balance;
    }
}

if (!function_exists('_update_invoice_bill')) {
    // untuk update status pada invoice bill
    function _update_invoice_bill($reference_number)
    {
        $transaction = Transaction::where('reference_number', $reference_number)->first();
        if ($transaction) {
            $total = _calculate_remaining_balance($reference_number);

            $invoice_bill = InvoiceBill::where('reference_number', $reference_number)->first();

            if ($invoice_bill) {
                if ($total == 0) {
                    $invoice_bill->payment_status = 'paid';
                } else {
                    $invoice_bill->payment_status = 'partial';
                }

                $invoice_bill->save();
            }
        }
    }
}

if (!function_exists('_count_down_payment')) {
    // untuk melakukan perhitungan down_payment
    function _count_down_payment($request, $id_journal_dp)
    {
        // untuk jurnal pertama
        $request_pnm = (object) [
            'id_journal'       => $request->id_journal,
            'in_ex_tax'        => $request->in_ex_tax,
            'reference_number' => $request->reference_number,
            'total'            => ($request->total - $request->deposit_total),
        ];

        $result = _count_journal($request_pnm);

        // untuk jurnal kedua
        $request_dp = (object) [
            'id_journal' => $id_journal_dp,
            'in_ex_tax'  => 'y',
            'total'      => $request->deposit_total,
        ];

        $result_dp = _count_journal($request_dp);

        $result = array_filter($result, function ($item) {
            return $item['value'] != 0;
        });

        $result_dp = array_filter($result_dp, function ($item) {
            return $item['value'] != 0;
        });

        $result = array_values($result);

        $result_dp = array_values($result_dp);

        $merged = [];

        // Masukkan semua dari array pertama
        foreach ($result as $item) {
            $coa = $item['coa'];
            $merged[$coa] = $item;
        }

        // Gabungkan array kedua
        foreach ($result_dp as $item) {
            $coa = $item['coa'];
            if (isset($merged[$coa])) {
                // Jika COA sama, tambahkan value
                $merged[$coa]['value'] += $item['value'];
            } else {
                // Jika COA belum ada, tambahkan sebagai item baru
                $merged[$coa] = $item;
            }
        }

        // Reset indeks numerik (opsional)
        $merged = array_values($merged);

        usort($merged, function ($a, $b) {
            return $a['type'] === $b['type'] ? 0 : ($a['type'] === 'D' ? -1 : 1);
        });

        $list_debit  = [];
        $list_credit = [];

        foreach ($merged as $key => $val) {
            if ($val['type'] === 'D') {
                $list_debit[] = $val['value'];
            } else {
                $list_credit[] = $val['value'];
            }
        }

        $sum_debit  = array_sum($list_debit);
        $sum_credit = array_sum($list_credit);
        $balance    = ($sum_debit - $sum_credit);

        if ($balance != 0) {
            return false;
        } else {
            return $merged;
        }
    }
}

if (!function_exists('_count_advance_payment')) {
    // untuk melakukan perhitungan advance_payment
    function _count_advance_payment($request)
    {
        $request_pnm = (object) [
            'id_journal'       => $request->id_journal,
            'in_ex_tax'        => $request->in_ex_tax,
            'total'            => $request->total,
            'deposit'          => $request->deposit,
            'deposit_total'    => $request->deposit_total,
            'reference_number' => $request->reference_number
        ];

        $result = _count_journal($request_pnm);

        // Pisahkan berdasarkan type
        $grouped = ['D' => [], 'K' => []];
        foreach ($result as $item) {
            $grouped[$item['type']][] = $item;
        }

        // Urutkan masing-masing group: calculated="1" di awal
        foreach ($grouped as &$group) {
            usort($group, function ($a, $b) {
                return $b['calculated'] <=> $a['calculated'];
            });
        }

        // Gabungkan kembali ke satu array
        $sorted = array_merge($grouped['D'], $grouped['K']);

        $list_debit  = [];
        $list_credit = [];

        foreach ($sorted as $key => $val) {
            if ($val['type'] === 'D') {
                $list_debit[] = $val['value'];
            } else {
                $list_credit[] = $val['value'];
            }
        }

        $sum_debit  = array_sum($list_debit);
        $sum_credit = array_sum($list_credit);
        $balance    = ($sum_debit - $sum_credit);

        if ($balance != 0) {
            return false;
        } else {
            return $sorted;
        }
    }
}

// untuk insert general ledger
if (!function_exists('insert_general_ledger')) {
    function insert_general_ledger($data, $transaction_number, $reference_number)
    {
        foreach ($data as $key => $value) {
            $generalLedger                      = new GeneralLedger();
            $generalLedger->id_kontak           = $value['id_kontak'] ?? null;
            $generalLedger->id_journal          = $value['id_journal'];
            $generalLedger->transaction_number  = $transaction_number;
            $generalLedger->date                = $value['date'];
            $generalLedger->coa                 = $value['coa'];
            $generalLedger->type                = $value['type'];
            $generalLedger->value               = $value['value'];
            $generalLedger->description         = $value['description'];
            $generalLedger->reference_number    = $reference_number;
            $generalLedger->phase               = $value['phase'];
            $generalLedger->calculated          = $value['calculated'];
            $generalLedger->save();
        }
    }
}

// untuk delete general ledger
if (!function_exists('delete_general_ledger')) {
    function delete_general_ledger($transaction_number)
    {
        GeneralLedger::where('transaction_number', $transaction_number)->delete();
    }
}

// untuk insert transaction
if (!function_exists('insert_transaction')) {
    function insert_transaction($data, $transaction_number, $reference_number)
    {
        $transaction                     = new Transaction();
        $transaction->id_kontak          = $data->id_kontak;
        $transaction->id_journal         = $data->id_journal;
        $transaction->id_invoice_bill    = $data->id_invoice_bill ?? null;
        $transaction->transaction_number = $transaction_number;
        $transaction->from_or_to         = $data->from_or_to;
        $transaction->description        = $data->description;
        $transaction->date               = $data->date ?? date('Y-m-d');
        $transaction->reference_number   = $reference_number;
        $transaction->category           = $data->category;
        $transaction->value              = $data->value ?? $data->total;
        $transaction->in_ex              = $data->in_ex_tax ?? $data->in_ex;

        if (isset($data->attachment)) {
            $attachment              = add_file($data->attachment, 'transaction/');
            $transaction->attachment = $attachment;
        }

        $transaction->save();

        return $transaction;
    }
}

// untuk delete transaction
if (!function_exists('delete_transaction')) {
    function delete_transaction($id)
    {
        if ($id !== null) {
            $transaction = Transaction::where('id_transaction', $id)->first();
            $transaction->status = 'deleted';
            $transaction->save();

            delete_general_ledger($transaction->transaction_number);
        }
    }
}

// untuk insert transaction full
if (!function_exists('insert_transaction_full')) {
    function insert_transaction_full($data, $transaction_number)
    {
        $transaction_full                     = new TransactionFull();
        $transaction_full->id_kontak          = $data->id_kontak;
        $transaction_full->id_journal         = $data->id_journal;
        $transaction_full->id_invoice_bill    = $data->id_invoice_bill ?? null;
        $transaction_full->transaction_number = $transaction_number;
        $transaction_full->invoice_number     = $data->invoice_number;
        $transaction_full->efaktur_number     = $data->efaktur_number;
        $transaction_full->date               = $data->date;
        $transaction_full->from_or_to         = $data->from_or_to;
        $transaction_full->description        = $data->description;
        $transaction_full->category           = $data->category;
        $transaction_full->record_type        = $data->record_type;
        $transaction_full->in_ex              = $data->in_ex_tax;
        $transaction_full->value              = $data->value;

        if (isset($data->attachment)) {
            $attachment                   = add_file($data->attachment, 'transaction_full/');
            $transaction_full->attachment = $attachment;
        }

        $transaction_full->save();

        return $transaction_full;
    }
}

// untuk delete transaction full
if (!function_exists('delete_transaction_full')) {
    function delete_transaction_full($id)
    {
        if ($id !== null) {
            $transaction_full = TransactionFull::where('id_transaction_full', $id)->first();
            $transaction_full->status = 'deleted';
            $transaction_full->save();

            delete_general_ledger($transaction_full->transaction_number);
        }
    }
}

// untuk insert receipt
if (!function_exists('insert_receipt')) {
    function insert_receipt($data, $transaction_number, $reference_number)
    {
        $receipt                     = new Receipts();
        $receipt->id_kontak          = $data->id_kontak;
        $receipt->id_journal         = $data->id_journal;
        $receipt->transaction_number = $transaction_number;
        $receipt->date               = $data->date;
        $receipt->receive_from       = $data->from_or_to;
        $receipt->pay_type           = $data->pay_type;
        $receipt->record_type        = $data->record_type;
        $receipt->description        = $data->description;
        $receipt->reference_number   = $reference_number;
        $receipt->in_ex              = $data->in_ex_tax;
        $receipt->value              = $data->total;

        if (isset($data->attachment)) {
            $attachment          = add_file($data->attachment, 'receipt/');
            $receipt->attachment = $attachment;
        }

        $receipt->save();

        return $receipt;
    }
}

// untuk insert expenditure
if (!function_exists('insert_expenditure')) {
    function insert_expenditure($data, $transaction_number, $reference_number)
    {
        $expenditure                     = new Expenditure();
        $expenditure->id_kontak          = $data->id_kontak;
        $expenditure->id_journal         = $data->id_journal;
        $expenditure->transaction_number = $transaction_number;
        $expenditure->date               = $data->date;
        $expenditure->outgoing_to        = $data->from_or_to;
        $expenditure->pay_type           = $data->pay_type;
        $expenditure->record_type        = $data->record_type;
        $expenditure->description        = $data->description;
        $expenditure->reference_number   = $reference_number;
        $expenditure->in_ex              = $data->in_ex_tax;
        $expenditure->value              = $data->total;

        if (isset($data->attachment)) {
            $attachment              = add_file($data->attachment, 'expenditure/');
            $expenditure->attachment = $attachment;
        }

        $expenditure->save();

        return $expenditure;
    }
}

// untuk insert down payment
if (!function_exists('_insert_down_payment')) {
    // insert down payment
    function _insert_down_payment($request, $transaction_number_dp)
    {
        // mengecek apa bila id_kontak sudah didaftar
        $check_down_payment = DownPayment::where('id_kontak', $request->id_kontak)->first();

        if (!$check_down_payment) {
            $down_payment = new DownPayment();
            $down_payment->id_kontak = $request->id_kontak;
            $down_payment->save();
        }

        $id_down_payment = $check_down_payment->id_down_payment ?? $down_payment->id_down_payment;

        $down_payment_details                     = new DownPaymentDetails();
        $down_payment_details->id_down_payment    = $id_down_payment;
        $down_payment_details->id_invoice_bill    = $request->id_invoice_bill ?? null;
        $down_payment_details->category           = $request->category ?? 'pengeluaran';
        $down_payment_details->transaction_number = $transaction_number_dp;
        $down_payment_details->date               = $request->date;
        $down_payment_details->value              = $request->deposit_total;
        $down_payment_details->description        = $request->description;

        if (isset($request->attachment)) {
            $attachment                       = add_file($request->attachment, 'down_payments/');
            $down_payment_details->attachment = $attachment;
        }

        $down_payment_details->save();

        return $down_payment_details;
    }
}

// untuk insert advance payment
if (!function_exists('_insert_advance_payment')) {
    // insert advance payment
    function _insert_advance_payment($request, $transaction_number_dp)
    {
        // mengecek apa bila id_kontak sudah didaftar
        $check_advance_payment = Liability::where('id_kontak', $request->id_kontak)->first();

        if (!$check_advance_payment) {
            $liability = new Liability();
            $liability->id_kontak = $request->id_kontak;
            $liability->save();
        }

        $id_liability = $check_advance_payment->id_liability ?? $liability->id_liability;

        $liability_details                     = new LiabilityDetail();
        $liability_details->id_liability       = $id_liability;
        $liability_details->id_invoice_bill    = $request->id_invoice_bill ?? null;
        $liability_details->category           = $request->category ?? 'pengeluaran';
        $liability_details->transaction_number = $transaction_number_dp;
        $liability_details->date               = $request->date;
        $liability_details->value              = $request->deposit_total;
        $liability_details->description        = $request->description;

        if (isset($request->attachment)) {
            $attachment                    = add_file($request->attachment, 'liabilities/');
            $liability_details->attachment = $attachment;
        }

        $liability_details->save();

        return $liability_details;
    }
}


// old
// if (!function_exists('_count_journal')) {
//     function _count_journal($request, $transaction_number = null)
//     {
//         $journal = Journal::with([
//             'toJournalSet.toCoa.toTaxCoa',
//             'toJournalSet.toTaxRate',
//             'toJournalSet' => function ($query) {
//                 $query->orderBy('serial_number', 'asc');
//             },
//         ])->find($request->id_journal);

//         $count_journal = $journal->toJournalSet->count();

//         $result = [];

//         $transaction_tax = [];

//         if ($count_journal > 2) {
//             $get_journal = [];

//             $another_journal = [];

//             $journal_credit = [];

//             $journal_debit = [];

//             $journal_beban = [];

//             $tax_default = TaxRate::find(get_arrangement('default_ppn'))->rate;

//             // begin:: beban / biaya
//             if (isset($request->dataBeban)) {
//                 if (count($request->dataBeban) > 0) {
//                     foreach ($request->dataBeban as $key => $value) {
//                         if (is_array($value)) {
//                             $value = (object) $value; // Ubah array menjadi objek
//                         }

//                         /**
//                          * diubah untuk kebutuhan data beban jika lebih dari 1, untuk menghitung dpp secara otomatis dan yang diinput adalah total invoice.
//                          */
//                         if ($request->in_ex_tax === 'n') {
//                             $journal_beban[$value->coa] = [
//                                 'amount' => round($value->amount / (1 + ($tax_default / 100))),
//                                 'posisi' => $value->posisi
//                             ];
//                         } else {
//                             $journal_beban[$value->coa] = [
//                                 'amount' => $value->amount,
//                                 'posisi' => $value->posisi
//                             ];
//                         }
//                     }
//                 }
//             }
//             // begin:: beban / biaya

//             // begin:: journal discount
//             if (isset($request->discount)) {
//                 if ($request->discount > 0) {
//                     if ($journal->category === 'penerimaan') {
//                         $get_journal[] = [
//                             'rate'   => null,
//                             'coa'    => Coa::whereIdCoa(get_arrangement('receive_coa_discount'))->first()->coa,
//                             'type'   => "D",
//                             'piece'  => "y",
//                             'ppn'    => "n",
//                             'amount' => $request->discount
//                         ];
//                     } else {
//                         $get_journal[] = [
//                             'rate'   => null,
//                             'coa'    => Coa::whereIdCoa(get_arrangement('expense_coa_discount'))->first()->coa,
//                             'type'   => "K",
//                             'piece'  => "y",
//                             'ppn'    => "n",
//                             'amount' => $request->discount
//                         ];
//                     }
//                 }
//             }
//             // end:: journal discount

//             // begin:: journal deposit liability
//             if (isset($request->deposit)) {
//                 if ($request->deposit == 'advance_payment') {
//                     $get_journal[] = [
//                         'rate'   => null,
//                         'coa'    => Coa::whereIdCoa(get_arrangement('advance_payment_deposit_coa'))->first()->coa,
//                         'type'   => "D",
//                         'piece'  => "y",
//                         'ppn'    => "n",
//                         'amount' => $request->deposit_total
//                     ];
//                 }
//             }
//             // end:: journal deposit liability

//             // begin:: journal interface
//             foreach ($journal->toJournalSet as $key => $value) {
//                 if ($value->toCoa->toTaxCoa) {
//                     $transaction_tax[] = [
//                         'id_coa'      => $value->toCoa->id_coa,
//                         'id_tax'      => $value->toTaxRate->id_tax,
//                         'id_tax_rate' => $value->toTaxRate->id_tax_rate,
//                         'rate'        => $value->toTaxRate->rate
//                     ];

//                     if ($value->toCoa->toTaxCoa->toTax->category === 'ppn') {
//                         if ($value->toTaxRate->rate != 0) {
//                             $get_journal[] = [
//                                 'rate'   => ($value->toTaxRate->rate / 100),
//                                 'type'   => $value->type,
//                                 'coa'    => $value->toCoa->coa,
//                                 'piece'  => 'y',
//                                 'ppn'    => 'y',
//                                 'amount' => 0
//                             ];
//                         } else {
//                             if ($value->toTaxRate->count === 'y') {
//                                 // jika category === ppn dan tax rate 0%  dan count === y / ppn dibebaskan
//                                 $another_journal[] = [
//                                     'rate'   => ($value->toTaxRate->rate / 100),
//                                     'type'   => $value->type,
//                                     'coa'    => $value->toCoa->coa,
//                                     'ppn'    => 'y',
//                                     'amount' => 0
//                                 ];
//                             } else {
//                                 // jika category === ppn dan tax rate 0%  dan count === n / ppn nol (Bu Cici)
//                                 $another_journal[] = [
//                                     'rate'   => ($tax_default / 100),
//                                     'type'   => $value->type,
//                                     'coa'    => $value->toCoa->coa,
//                                     'ppn'    => 'y',
//                                     'amount' => 0
//                                 ];
//                             }
//                         }
//                     } else {
//                         $get_journal[] = [
//                             'rate'   => ($value->toTaxRate->rate / 100),
//                             'type'   => $value->type,
//                             'coa'    => $value->toCoa->coa,
//                             'piece'  => 'y',
//                             'ppn'    => 'n',
//                             'amount' => 0
//                         ];
//                     }
//                 } else {
//                     if (isset($journal_beban[$value->toCoa->coa])) {
//                         $get_journal[] = [
//                             'rate'   => null,
//                             'type'   => $value->type,
//                             'coa'    => $value->toCoa->coa,
//                             'piece'  => 'y',
//                             'ppn'    => 'n',
//                             'amount' => $journal_beban[$value->toCoa->coa]['amount']
//                         ];
//                     } else {
//                         $get_journal[] = [
//                             'rate'   => null,
//                             'type'   => $value->type,
//                             'coa'    => $value->toCoa->coa,
//                             'piece'  => "n",
//                             'ppn'    => "n",
//                             'amount' => 0
//                         ];
//                     }
//                 }
//             }
//             // end:: journal interface

//             // begin:: check transaction
//             $check_transaction = Transaction::where('reference_number', $request->reference_number)->first();
//             $hasPpn = false;
//             if ($check_transaction) {
//                 $id_journal_transaction = $check_transaction->id_journal;

//                 $journal_transaction = _get_journal($id_journal_transaction);

//                 $hasPpn = _has_ppn($journal_transaction);
//             }
//             // end:: check transaction

//             // begin:: filter hanya yang ppn = y
//             $ppn = [];
//             foreach ($get_journal as $row) {
//                 if ($row['ppn'] === 'y') {
//                     $ppn[$row['coa']] = $row['ppn'];
//                 }
//             }
//             // end:: filter hanya yang ppn = y

//             $dpp   = 0;
//             $total = 0;

//             // begin:: untuk check include, exclude or no (y, n, o)
//             if (isset($request->in_ex_tax)) {
//                 if ($request->in_ex_tax === 'y') {
//                     $total += $request->total;

//                     $dpp += $request->total;

//                     foreach ($get_journal as $key => $value) {
//                         if ($value['ppn'] === 'y') {
//                             $total += $request->total * $value['rate'];
//                         }
//                     }

//                     foreach ($another_journal as $key => $value) {
//                         if ($value['ppn'] === 'y') {
//                             $total += $request->total * $value['rate'];
//                         }
//                     }

//                     /**
//                      * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
//                      */
//                     // if (isset($request->dataBeban)) {
//                     //     if (count($request->dataBeban) > 0) {
//                     //         foreach ($request->dataBeban as $key => $value) {
//                     //             if (is_array($value)) {
//                     //                 $value = (object) $value; // Ubah array menjadi objek
//                     //             }

//                     //             $total += $value->amount;
//                     //         }
//                     //     }
//                     // }
//                 } else if ($request->in_ex_tax === 'n') {
//                     foreach ($get_journal as $key => $value) {
//                         if ($value['ppn'] === 'y') {
//                             $dpp += round($request->total / (1 + $value['rate']), 2);

//                             $total += round($request->total / (1 + $value['rate']), 2);
//                         }
//                     }

//                     foreach ($another_journal as $key => $value) {
//                         if ($value['ppn'] === 'y') {
//                             $dpp += round($request->total / (1 + $value['rate']), 2);

//                             $total += round($request->total / (1 + $value['rate']), 2);
//                         }
//                     }

//                     foreach ($get_journal as $key => $value) {
//                         if ($value['ppn'] === 'y') {
//                             $total += $total * $value['rate'];
//                         }
//                     }

//                     foreach ($another_journal as $key => $value) {
//                         if ($value['ppn'] === 'y') {
//                             $total += $total * $value['rate'];
//                         }
//                     }

//                     /**
//                      * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
//                      */
//                     // if (isset($request->dataBeban)) {
//                     //     if (count($request->dataBeban) > 0) {
//                     //         foreach ($request->dataBeban as $key => $value) {
//                     //             if (is_array($value)) {
//                     //                 $value = (object) $value; // Ubah array menjadi objek
//                     //             }

//                     //             $total += $value->amount;
//                     //         }
//                     //     }
//                     // }
//                 } else {
//                     $total = $request->total;

//                     $dpp = $request->total;

//                     $check_coa_tax = array_filter($get_journal, function ($row) {
//                         return $row['rate'] !== null;
//                     });

//                     // untuk mengecek apa bila jurnal memiliki component tax hpp & ppn
//                     if (count($check_coa_tax) > 0 && $hasPpn) {
//                         // untuk mencari dpp
//                         $dpp = round($request->total / (1 + ($tax_default / 100)), 2);

//                         $total = $request->total;
//                     }
//                 }
//             }

//             $total = round($total);

//             $debit  = [];
//             $credit = [];

//             foreach ($get_journal as $key => $value) {
//                 $amount = ($value['amount'] <= 0) ? floor(($dpp * $value['rate'])) : $value['amount'];

//                 if ($value['type'] === 'K') {
//                     $journal_credit[] = [
//                         'type'  => $value['type'],
//                         'coa'   => $value['coa'],
//                     ];

//                     if ($value['piece'] === 'y') {
//                         $credit[$key] = $amount;
//                     } else {
//                         $credit[$key] = $value['amount'];
//                     }
//                 } else {
//                     $journal_debit[] = [
//                         'type'  => $value['type'],
//                         'coa'   => $value['coa'],
//                     ];

//                     if ($value['piece'] === 'y') {
//                         $debit[$key] = $amount;
//                     } else {
//                         $debit[$key] = $value['amount'];
//                     }
//                 }
//             }

//             $get_debit  = [];
//             $get_credit = [];

//             foreach ($debit as $key => $value) {
//                 if (count($debit) > 1) {
//                     if ($value <= 0) {
//                         $value = remainder($debit, $total);

//                         $calculated = '1';
//                     } else {
//                         $value = $value;

//                         $calculated = '0';
//                     }
//                 } else {
//                     if ($request->in_ex_tax === 'y') {
//                         $value = $total;
//                     } else {
//                         $value = $request->total;
//                     }

//                     $calculated = '1';
//                 }

//                 $get_debit[$key] = [
//                     'value'      => $value,
//                     'calculated' => $calculated
//                 ];
//             }

//             foreach ($credit as $key => $value) {
//                 if (count($credit) > 1) {
//                     if ($value <= 0) {
//                         $value = remainder($credit, $total);

//                         $calculated = '1';
//                     } else {
//                         $value = $value;

//                         $calculated = '0';
//                     }
//                 } else {
//                     if ($request->in_ex_tax === 'y') {
//                         $value = $total;
//                     } else {
//                         $value = $request->total;
//                     }

//                     $calculated = '1';
//                 }

//                 $get_credit[$key] = [
//                     'value'      => $value,
//                     'calculated' => $calculated
//                 ];
//             }

//             $another_credit = [];
//             $another_debit = [];

//             foreach ($another_journal as $key => $val) {
//                 if ($val['type'] === 'K') {
//                     $journal_credit[] = [
//                         'type'  => $val['type'],
//                         'coa'   => $val['coa'],
//                     ];

//                     $another_credit[] = [
//                         'value'      => $val['amount'],
//                         'calculated' => '0'
//                     ];
//                 } else {
//                     $journal_debit[] = [
//                         'type'  => $val['type'],
//                         'coa'   => $val['coa'],
//                     ];

//                     $another_debit[] = [
//                         'value'      => $val['amount'],
//                         'calculated' => '0'
//                     ];
//                 }
//             }

//             $last_debit  = array_merge($get_debit, $another_debit);
//             $last_credit = array_merge($get_credit, $another_credit);

//             $journal_debit_sort  = array_values($journal_debit);
//             $journal_credit_sort = array_values($journal_credit);

//             foreach ($journal_debit_sort as $key => $val) {
//                 $result[] = [
//                     'coa'        => $val['coa'],
//                     'type'       => $val['type'],
//                     'value'      => $last_debit[$key]['value'],
//                     'ppn'        => $ppn[$val['coa']] ?? 'n',
//                     'calculated' => $last_debit[$key]['calculated'],
//                 ];
//             }

//             foreach ($journal_credit_sort as $key => $val) {
//                 $result[] = [
//                     'coa'        => $val['coa'],
//                     'type'       => $val['type'],
//                     'value'      => $last_credit[$key]['value'],
//                     'ppn'        => $ppn[$val['coa']] ?? 'n',
//                     'calculated' => $last_credit[$key]['calculated'],
//                 ];
//             }
//         } else {
//             foreach ($journal->toJournalSet as $key => $val) {
//                 $result[] = [
//                     'coa'        => $val->toCoa->coa,
//                     'type'       => $val->type,
//                     'value'      => $request->total,
//                     'ppn'        => $ppn[$val->toCoa->coa] ?? 'n',
//                     'calculated' => '0',
//                 ];
//             }
//         }

//         $list_debit = [];
//         $list_credit = [];

//         foreach ($result as $key => $val) {
//             if ($val['type'] === 'D') {
//                 $list_debit[] = $val['value'];
//             } else {
//                 $list_credit[] = $val['value'];
//             }
//         }

//         $sum_debit  = array_sum($list_debit);
//         $sum_credit = array_sum($list_credit);
//         $balance    = ($sum_debit - $sum_credit);

//         if (round($balance) != 0) {
//             return false;
//         } else {
//             if (count($transaction_tax) != 0) {
//                 $check_transaction_tax = TransactionTax::whereTransactionNumber($transaction_number)->get()->count();

//                 if ($check_transaction_tax > 0) {
//                     TransactionTax::whereTransactionNumber($transaction_number)->delete();
//                 }

//                 foreach ($transaction_tax as $key => $val) {
//                     $transaction_tax[$key]['transaction_number'] = $transaction_number;
//                 }

//                 TransactionTax::insert($transaction_tax);
//             }

//             return $result;
//         }
//     }
// }
if (!function_exists('makeJournalEntry')) {
    function _make_journal_entry(array $overrides): array
    {
        return array_merge([
            'rate'   => null,
            'type'   => null,
            'coa'    => null,
            'piece'  => 'y',
            'ppn'    => 'n',
            'amount' => 0,
        ], $overrides);
    }
}

if (!function_exists('_count_journal_forge')) {
    function _count_journal_forge($request, $transaction_number = null)
    {
        $journal = Journal::with([
            'toJournalSet.toCoa.toTaxCoa',
            'toJournalSet.toTaxRate',
            'toJournalSet' => function ($query) {
                $query->orderBy('serial_number', 'asc');
            },
        ])->find($request->id_journal);

        $count_journal = $journal->toJournalSet->count();

        $result = [];

        $transaction_tax = [];

        if ($count_journal > 2) {
            $get_journal     = [];
            $journal_beban   = [];
            $transaction_tax = [];
            $another_journal = [];
            $journal_debit   = [];
            $journal_credit  = [];

            $tax_default = TaxRate::find(get_arrangement('default_ppn'))->rate;

            // if (!$tax_default) {
            //     // return error
            // }

            // kondisi belum dites
            if (!empty($request->dataBeban)) {
                $journal_beban = _get_journal_beban($request->dataBeban, $$request->in_ex_tax, $tax_default, $journal_beban);
            }

            if (!empty($request->discount)) {
                $get_journal = _get_journal_discount($request->discount, $journal->category);
            }

            //kondisi belum dites
            if (!empty($request->deposit)) {
                $get_journal = [
                    ...$get_journal,
                    ..._get_journal_deposit($request->deposit, $request->deposit_total)
                ];
            }

            // $result_journal = _get_journal_interface($journal->toJournalSet, $journal_beban, $get_journal, $transaction_tax, $another_journal, $tax_default);
            $result_journal = _get_journal_interface($journal->toJournalSet, $journal_beban, $tax_default);

            // get all returned data
            $get_journal     = [...$get_journal, ...$result_journal['get_journal']];
            $another_journal = $result_journal['another_journal'];
            $transaction_tax = $result_journal['transaction_tax'];

            dd($get_journal);

            // filter hanya yang ppn = y
            $ppn = [];
            foreach ($get_journal as $row) {
                if ($row['ppn'] === 'y') {
                    $ppn[$row['coa']] = $row['ppn'];
                }
            }

            // total and dpp
            if (!empty($request->in_ex_tax)) {
                $result_td = _get_total_and_dpp($request->in_ex_tax, $request->total, $get_journal, $another_journal, $tax_default);
                $total     = $result_td->total;
                $dpp       = $result_td->dpp;
            }

            // debit credit
            $result_amount  = _get_debit_credit_amount($get_journal, $journal_debit, $journal_credit, $dpp);
            $journal_debit  = $result_amount->journal_debit;
            $journal_credit = $result_amount->journal_credit;
            $debit_amount   = $result_amount->debit_amount;
            $credit_amount  = $result_amount->credit_amount;

            $result_next = _get_debit_credit_next($debit_amount, $credit_amount, $request->total, $request->in_ex_tax, $total);
            $next_debit  = $result_next->next_debit;
            $next_credit = $result_next->next_credit;

            $result_another = _get_debit_credit_another($another_journal, $journal_debit, $journal_credit);
            $another_debit  = $result_another->another_debit;
            $another_credit = $result_another->another_credit;

            $last_debit  = array_merge($next_debit, $another_debit);
            $last_credit = array_merge($next_credit, $another_credit);

            $journal_debit_sort  = array_values($journal_debit);
            $journal_credit_sort = array_values($journal_credit);

            $result = _get_result($result, $journal_debit_sort, $journal_credit_sort, $last_debit, $last_credit);

            // dd($journal_debit_sort, $journal_credit_sort);
        } else {
            foreach ($journal->toJournalSet as $key => $val) {
                $result[] = [
                    'coa'        => $val->toCoa->coa,
                    'type'       => $val->type,
                    'value'      => $request->total,
                    'ppn'        => $ppn[$val->toCoa->coa] ?? 'n',
                    'calculated' => '0',
                ];
            }
        }

        $list_debit = [];
        $list_credit = [];

        foreach ($result as $key => $val) {
            if ($val['type'] === 'D') {
                $list_debit[] = $val['value'];
            } else {
                $list_credit[] = $val['value'];
            }
        }

        $sum_debit  = array_sum($list_debit);
        $sum_credit = array_sum($list_credit);
        $balance    = ($sum_debit - $sum_credit);

        if (round($balance) != 0) {
            return false;
        }

        if (count($transaction_tax) != 0) {
            $check_transaction_tax = TransactionTax::whereTransactionNumber($transaction_number)->get()->count();

            if ($check_transaction_tax > 0) {
                TransactionTax::whereTransactionNumber($transaction_number)->delete();
            }

            foreach ($transaction_tax as $key => $val) {
                $transaction_tax[$key]['transaction_number'] = $transaction_number;
            }

            TransactionTax::insert($transaction_tax);
        }

        return $result;
    }
}

if (!function_exists('_get_journal_beban')) {
    function _get_journal_beban(array $data_beban, string $in_ex_tax, float $tax_default, array $journal_beban): array
    {
        foreach ($data_beban as $beban) {
            $beban = is_array($beban) ? (object) $beban : $beban;

            /**
             * diubah untuk kebutuhan data beban jika lebih dari 1, untuk menghitung dpp secara otomatis dan yang diinput adalah total invoice.
             */
            $amount = ($in_ex_tax === 'n')
                ? round($beban->amount / (1 + ($tax_default / 100)))
                : $beban->amount;

            $journal_beban[$beban->coa] = [
                'amount' => $amount,
                'posisi' => $beban->posisi
            ];
        }

        return $journal_beban;
    }
}

if (!function_exists('_get_journal_discount')) {
    function _get_journal_discount(float $discount, string $category): array
    {
        if ($discount <= 0) {
            return [];
        }

        $coaId = $category === 'penerimaan'
            ? get_arrangement('receive_coa_discount')
            : get_arrangement('expense_coa_discount');

        $coa = Coa::whereIdCoa($coaId)->value('coa');

        return [[
            'rate'   => null,
            'coa'    => $coa,
            'type'   => $category === 'penerimaan' ? 'D' : 'K',
            'piece'  => 'y',
            'ppn'    => 'n',
            'amount' => $discount
        ]];
    }
}

if (!function_exists('_get_journal_deposit')) {
    function _get_journal_deposit(string $deposit, float $deposit_total): array
    {
        if ($deposit !== 'advance_payment') {
            return [];
        }

        $coaId = get_arrangement('expense_coa_deposit');
        $coa = Coa::whereIdCoa($coaId)->value('coa');

        return [[
            'rate'   => null,
            'coa'    => $coa,
            'type'   => 'D',
            'piece'  => 'y',
            'ppn'    => 'n',
            'amount' => $deposit_total
        ]];
    }
}

if (!function_exists('_get_journal_interface')) {
    function _get_journal_interface(iterable $journal_sets, array $journal_beban, float $tax_default): array
    {
        $get_journal     = [];
        $transaction_tax = [];
        $another_journal = [];

        foreach ($journal_sets as $set) {
            $coa  = $set->toCoa->coa;
            $type = $set->type;
            $rate = $set->toTaxRate->rate ?? 0;

            if ($set->toCoa->toTaxCoa) {
                $transaction_tax[] = [
                    'id_coa'      => $set->toCoa->id_coa,
                    'id_tax'      => $set->toCoa->toTaxCoa->id_tax,
                    'id_tax_rate' => $set->toCoa->toTaxCoa->id_tax_rate,
                    'rate'        => $rate,
                ];

                if ($set->toCoa->toTaxCoa->toTax->category === 'ppn') {
                    if ($rate != 0) {
                        $get_journal[] = [
                            'rate'   => ($rate / 100),
                            'type'   => $type,
                            'coa'    => $coa,
                            'piece'  => 'y',
                            'ppn'    => 'y',
                            'amount' => 0
                        ];
                    } else {
                        $another_journal[] = [
                            'rate'   => $set->toTaxRate->count === 'y' ? $rate / 100 : $tax_default / 100,
                            'type'   => $type,
                            'coa'    => $coa,
                            'ppn'    => 'y',
                            'amount' => 0
                        ];
                    }
                } else {
                    $get_journal[] = [
                        'rate'   => $rate / 100,
                        'type'   => $type,
                        'coa'    => $coa,
                        'piece'  => 'y',
                        'ppn'    => 'n',
                        'amount' => 0
                    ];
                }
            } else {
                if (isset($journal_beban[$coa])) {
                    $get_journal[] = [
                        'rate'   => null,
                        'type'   => $type,
                        'coa'    => $coa,
                        'piece'  => 'y',
                        'ppn'    => 'n',
                        'amount' => $journal_beban[$coa]['amount']
                    ];
                } else {
                    $get_journal[] = [
                        'rate'   => null,
                        'type'   => $type,
                        'coa'    => $coa,
                        'piece'  => 'n',
                        'ppn'    => 'n',
                        'amount' => 0
                    ];
                }
            }
        }

        return compact('get_journal', 'transaction_tax', 'another_journal');
    }
}

if (!function_exists('_get_total_and_dpp')) {
    function _get_total_and_dpp($in_ex_tax, $req_total, $get_journal, $another_journal, $tax_default)
    {
        $total = 0;
        $dpp = 0;

        if ($in_ex_tax === 'y') {
            $total += $req_total;
            $dpp += $req_total;

            foreach ($get_journal as $journal) {
                if ($journal['ppn'] === 'y') {
                    $total += $req_total * $journal['rate'];
                }
            }

            foreach ($another_journal as $journal) {
                if ($journal['ppn'] === 'y') {
                    $total += $req_total * $journal['rate'];
                }
            }
        } else if ($in_ex_tax === 'n') {
            foreach ($get_journal as $journal) {
                if ($journal['ppn'] === 'y') {
                    $total += round($req_total / (1 + $journal['rate']), 2);
                    $dpp += round($req_total / (1 + $journal['rate']), 2);
                }
            }

            foreach ($another_journal as $journal) {
                if ($journal['ppn'] === 'y') {
                    $total += round($req_total / (1 + $journal['rate']), 2);
                    $dpp += round($req_total / (1 + $journal['rate']), 2);
                }
            }

            foreach ($get_journal as $journal) {
                if ($journal['ppn'] === 'y') {
                    $total += $total * $journal['rate'];
                }
            }

            foreach ($another_journal as $journal) {
                if ($journal['ppn'] === 'y') {
                    $total += $total * $journal['rate'];
                }
            }
        } else {
            $total = $req_total;
            $dpp = $req_total;

            $check_coa_tax = array_filter($get_journal, function ($journal) {
                return $journal['rate'] !== null;
            });

            if (count($check_coa_tax) > 0) {
                $dpp = round($req_total / (1 + ($tax_default / 100)), 2);

                $total = $req_total;
            }
        }

        $total = round($total, 2);

        return (object) [
            'total' => $total,
            'dpp'   => $dpp
        ];
    }
}

if (!function_exists('_get_debit_credit_amount')) {
    function _get_debit_credit_amount($get_journal, $journal_debit, $journal_credit, $dpp)
    {
        foreach ($get_journal as $key => $journal) {
            $amount = ($journal['amount'] <= 0) ? floor(($dpp * $journal['rate'])) : $journal['amount'];

            if ($journal['type'] === 'K') {
                $journal_credit[] = [
                    'type'  => $journal['type'],
                    'coa'   => $journal['coa'],
                ];

                if ($journal['piece'] === 'y') {
                    $credit[$key] = $amount;
                } else {
                    $credit[$key] = $journal['amount'];
                }
            } else {
                $journal_debit[] = [
                    'type'  => $journal['type'],
                    'coa'   => $journal['coa'],
                ];

                if ($journal['piece'] === 'y') {
                    $debit[$key] = $amount;
                } else {
                    $debit[$key] = $journal['amount'];
                }
            }
        }

        return (object) [
            'journal_debit' => $journal_debit,
            'journal_credit' => $journal_credit,
            'debit_amount' => $debit,
            'credit_amount' => $credit
        ];
    }
}

if (!function_exists('_get_debit_credit_final')) {
    function _get_debit_credit_next($debit_amount, $credit_amount, $req_total, $in_ex_tax, $total)
    {
        $next_debit  = [];
        $next_credit = [];

        foreach ($debit_amount as $key => $value) {
            if (count($debit_amount) > 1) {
                if ($value <= 0) {
                    $value = remainder($debit_amount, $total);

                    $calculated = '1';
                } else {
                    $value = $value;

                    $calculated = '0';
                }
            } else {
                if ($in_ex_tax === 'y') {
                    $value = $total;
                } else {
                    $value = $req_total;
                }

                $calculated = '1';
            }

            $next_debit[$key] = [
                'value'      => $value,
                'calculated' => $calculated
            ];
        }

        foreach ($credit_amount as $key => $value) {
            if (count($credit_amount) > 1) {
                if ($value <= 0) {
                    $value = remainder($credit_amount, $total);

                    $calculated = '1';
                } else {
                    $value = $value;

                    $calculated = '0';
                }
            } else {
                if ($in_ex_tax === 'y') {
                    $value = $total;
                } else {
                    $value = $req_total;
                }

                $calculated = '1';
            }

            $next_credit[$key] = [
                'value'      => $value,
                'calculated' => $calculated
            ];
        }

        return (object) [
            'next_debit'  => $next_debit,
            'next_credit' => $next_credit
        ];
    }
}

if (!function_exists('_get_debit_credit_another')) {
    function _get_debit_credit_another($another_journal, $another_debit, $another_credit)
    {
        $another_credit = [];
        $another_debit = [];

        foreach ($another_journal as $key => $val) {
            if ($val['type'] === 'K') {
                $journal_credit[] = [
                    'type'  => $val['type'],
                    'coa'   => $val['coa'],
                ];

                $another_credit[] = [
                    'value'      => $val['amount'],
                    'calculated' => '0'
                ];
            } else {
                $journal_debit[] = [
                    'type'  => $val['type'],
                    'coa'   => $val['coa'],
                ];

                $another_debit[] = [
                    'value'      => $val['amount'],
                    'calculated' => '0'
                ];
            }
        }

        return (object) [
            'another_debit' => $another_debit,
            'another_credit' => $another_credit
        ];
    }
}

if (!function_exists('_get_result')) {
    function _get_result($result, $journal_debit_sort, $journal_credit_sort, $last_debit, $last_credit)
    {
        foreach ($journal_debit_sort as $key => $val) {
            $result[] = [
                'coa'        => $val['coa'],
                'type'       => $val['type'],
                'value'      => $last_debit[$key]['value'],
                'ppn'        => $ppn[$val['coa']] ?? 'n',
                'calculated' => $last_debit[$key]['calculated'],
            ];
        }

        foreach ($journal_credit_sort as $key => $val) {
            $result[] = [
                'coa'        => $val['coa'],
                'type'       => $val['type'],
                'value'      => $last_credit[$key]['value'],
                'ppn'        => $ppn[$val['coa']] ?? 'n',
                'calculated' => $last_credit[$key]['calculated'],
            ];
        }
        return $result;
    }
}
