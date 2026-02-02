<?php

namespace App\Http\Controllers\api\finance;

use App\Http\Controllers\Controller;
use App\Models\finance\Expenditure;
use Illuminate\Http\Request;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Receipts;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;

class SearchController extends Controller
{
    public function globalSearch(Request $request)
    {
        $query = $request->input('query');
        $results = [];

        // Pencarian di tabel general_ledger
        $result_not_used['general_ledger'] = GeneralLedger::when($query, function ($q) use ($query) {
            $q->where(function ($group) use ($query) {
                $group->where('description', 'like', "%{$query}%")
                    ->orWhere('transaction_number', $query)
                    ->orWhere('reference_number', $query)
                    ->orWhere('date', $query)
                    ->orWhere('value', $query);
            });
        })
            ->get()
            ->groupBy('transaction_number')
            ->map(function ($transactions, $transactionNumber) {
                return [
                    'row_key' => $transactions->first()->date . '_' . $transactionNumber,
                    'transaction_number' => $transactionNumber,
                    'date' => $transactions->first()->date,
                    'description' => $transactions->first()->description,
                    'value' => $transactions->where('type', 'D')->sum('value'),
                    'details' => $transactions->map(function ($transaction, $index) {
                        return [
                            'row_key' => $transaction->transaction_number . '_' . $index,
                            'coa_number' => $transaction->coa,
                            'coa' => $transaction->toCoa->name ?? $transaction->coa,
                            'debit' => $transaction->type === 'D' ? $transaction->value : 0,
                            'credit' => $transaction->type === 'K' ? $transaction->value : 0,
                        ];
                    })->toArray(),
                ];
            })
            ->values();


        // pencarian di GeneralLedger
        $results['general_ledger'] = GeneralLedger::when($query, function ($q) use ($query) {
            $q->where(function ($group) use ($query) {
                $group->where('description', 'like', "%{$query}%")
                    ->orWhere('transaction_number', $query)
                    ->orWhere('reference_number', $query)
                    ->orWhere('date', $query)
                    ->orWhere('value', $query);
            });
        })
            ->with(['toCoa:id_coa,coa,name'])
            ->get()
            ->groupBy('transaction_number')
            ->map(function ($transactions, $transactionNumber) {
                $description = $transactions->first()->description;
                return [
                    'row_key' => $transactions->first()->date . '_' . $transactionNumber,
                    'transaction_number' => $transactionNumber,
                    'date' => $transactions->first()->date,
                    'description' => $transactions->first()->description,
                    'value' => $transactions->where('type', 'D')->sum('value'),
                    'coas' => $transactions->groupBy('coa')
                        ->map(function ($items, $coa) {
                            $coaData = $items->first()->toCoa;
                            return [
                                'item' => $items,
                                'coa' => $coa,
                                'name' => $coaData->name ?? 'N/A',
                                // 'description' => $items->description,
                                'detail' => $items->map(function ($item, $description) {
                                    return [
                                        'date' => $item->date,
                                        'description' => $item->description,
                                        'credit' => $item->type === 'K' ? $item->value : 0,
                                        'debit' => $item->type === 'D' ? $item->value : 0,
                                    ];
                                })->toArray(),
                            ];
                        })->values(),
                ];
            })
            ->values();

        // Pencarian di tabel transaksi
        $results['transaction_outstanding'] = Transaction::when($query, function ($q) use ($query) {
            $q->where(function ($group) use ($query) {
                $group->where('description', 'like', "%{$query}%")
                    ->orWhere('transaction_number', $query)
                    ->orWhere('reference_number', $query)
                    ->orWhere('date', $query)
                    ->orWhere('from_or_to', 'like', "%{$query}%")
                    ->orWhere('value', $query);
            });
        })
            ->where('status', 'valid')
            ->get();

        // Pencarian di tabel jurnal
        $results['transaction_full'] = TransactionFull::when($query, function ($q) use ($query) {
            $q->where(function ($group) use ($query) {
                $group->where('description', 'like', "%{$query}%")
                    ->orWhere('transaction_number', $query)
                    ->orWhere('invoice_number', $query)
                    ->orWhere('date', $query)
                    ->orWhere('from_or_to', 'like', "%{$query}%")
                    ->orWhere('value', $query);;
            });
        })
            ->where('status', 'valid')
            ->get();

        $results['expenditures'] = Expenditure::when($query, function ($q) use ($query) {
            $q->where(function ($group) use ($query) {
                $group->where('description', 'like', "%{$query}%")
                    ->orWhere('transaction_number', $query)
                    ->orWhere('reference_number', $query)
                    ->orWhere('date', $query)
                    ->orWhere('outgoing_to', 'like', "%{$query}%")
                    ->orWhere('value', $query);
            });
        })
            ->where('status', 'valid')
            ->get();

        $results['receives'] = Receipts::when($query, function ($q) use ($query) {
            $q->where(function ($group) use ($query) {
                $group->where('description', 'like', "%{$query}%")
                    ->orWhere('transaction_number', $query)
                    ->orWhere('reference_number', $query)
                    ->orWhere('date', $query)
                    ->orWhere('receive_from', 'like', "%{$query}%")
                    ->orWhere('value', $query);
            });
        })
            ->where('status', 'valid')
            ->get();

        return response()->json($results);
    }
}
