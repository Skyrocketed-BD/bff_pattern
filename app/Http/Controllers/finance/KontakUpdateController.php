<?php

namespace App\Http\Controllers\finance;

use App\Http\Controllers\Controller;
use App\Models\finance\Expenditure;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Receipts;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use Illuminate\Support\Facades\DB;

class KontakUpdateController extends Controller
{
    public function index()
    {

        $models = [
            Transaction::class,
            TransactionFull::class,
            Receipts::class,
            Expenditure::class,
        ];

        $results = [];

        DB::connection('finance')->transaction(function () use ($models, &$results) {
            foreach ($models as $model) {
                $records = $model::select('transaction_number', 'id_kontak')->get();

                if ($records->isNotEmpty()) {
                    foreach ($records as $record) {
                        GeneralLedger::where('transaction_number', $record->transaction_number)
                            ->update(['id_kontak' => $record->id_kontak]);
                    }

                    $results = array_merge($results, $records->toArray());
                }
            }
        });

        dd($results);
    }
}
