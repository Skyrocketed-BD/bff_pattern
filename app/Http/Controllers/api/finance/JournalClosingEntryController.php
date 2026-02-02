<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\JournalClosingEntryRequest;
use App\Models\finance\JournalClosingEntry;
use App\Models\finance\JournalClosingEntrySets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class JournalClosingEntryController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/journal-closing-entry",
     *  summary="Get the list of journal closing entries",
     *  tags={"Finance - Journal Closing Entries"},
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
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $data = JournalClosingEntry::whereBetweenMonth($start_date, $end_date)->get();

        return ApiResponseClass::sendResponse($data, 'Journal Closing Entry Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/journal-closing-entry",
     *  summary="Create a new journal closing entry",
     *  tags={"Finance - Journal Closing Entries"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="ID COA"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="Type"
     *              ),
     *              @OA\Property(
     *                  property="amount",
     *                  type="array",
     *                  @OA\Items(type="integer", example=10000),
     *                  description="Amount"
     *              ),
     *              required={"date", "description", "id_coa", "type", "amount"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "id_coa": {1, 2},
     *                  "type": {"D", "K"},
     *                  "amount": {20000, 20000}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(JournalClosingEntryRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'journal_entries', 'transaction_number', 'JCLS');

            $journal_closing_entry                     = new JournalClosingEntry();
            $journal_closing_entry->transaction_number = $transaction_number;
            $journal_closing_entry->date               = $request->date;
            $journal_closing_entry->description        = $request->description;

            $id_coa = $request->id_coa;
            $type   = $request->type;
            $amount = $request->amount;

            $debit  = [];
            $credit = [];

            for ($i = 0; $i < count($id_coa); $i++) {
                if ($type[$i] === 'K') {
                    $credit[] = $amount[$i];
                } else {
                    $debit[] = $amount[$i];
                }
            }

            $sum_debit  = array_sum($debit);
            $sum_credit = array_sum($credit);
            $balance    = ($sum_debit - $sum_credit);
            $value      = $sum_credit;

            if ($balance != 0) {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            } else {
                $journal_closing_entry->value = $value;
                $journal_closing_entry->save();

                $serial_number = 1;

                foreach ($id_coa as $key => $value) {
                    $data[] = [
                        'id_journal_closing_entry' => $journal_closing_entry->id_journal_closing_entry,
                        'id_coa'                   => $id_coa[$key],
                        'type'                     => $type[$key],
                        'value'                    => $amount[$key],
                        'serial_number'            => $serial_number++,
                        'created_by'               => auth('api')->user()->id_users
                    ];
                }

                JournalClosingEntrySets::insert($data);

                ActivityLogHelper::log('finance:journal_closing_entry_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                    'date'                       => $journal_closing_entry->date,
                    'value'                      => $journal_closing_entry->value
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($data, 'Journal Closing Entry Created Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_closing_entry_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/journal-closing-entry/{no_transaction}",
     *  summary="Delete journal closing entry",
     *  tags={"Finance - Journal Closing Entries"},
     *  @OA\Parameter(
     *      name="no_transaction",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($no_transaction)
    {
        DB::connection('finance')->beginTransaction();
        try {
            // check if transaction is closed
            if ($this->isTransactionClosed($no_transaction)) {
                return Response::json(['success' => false, 'message' => 'Cannot Delete, Transaction Already Closed !'], 400);
            } else {
                $check_transaction = JournalClosingEntry::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);
                
                ActivityLogHelper::log('finance:journal_closing_entry_delete', 1, ['finance:reference_number' => $no_transaction]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Journal Closing Entry Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_closing_entry_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
