<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\JournalEntryRequest;
use App\Http\Resources\finance\JournalEntryResource;
use App\Models\finance\GeneralLedger;
use App\Models\finance\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class JournalEntryController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/journal-entries",
     *  summary="Get the list of journal entries",
     *  tags={"Finance - Journal Entries"},
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
     *  @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="Status",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
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

        $query = JournalEntry::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('id_journal_entry', 'asc')->get();

        return ApiResponseClass::sendResponse(JournalEntryResource::collection($data), 'Journal Entries Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/journal-entries",
     *  summary="Create a new journal entry",
     *  tags={"Finance - Journal Entries"},
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
     *                  property="coa",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="COA Code"
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
     *              required={"date", "description", "coa", "type", "amount"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "coa": {1, 2},
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
    public function store(JournalEntryRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'journal_entries', 'transaction_number', 'JU');

            $attachment = isset($request->attachment) ? add_file($request->attachment, 'journal_entries/') : null;

            $journal_entry                     = new JournalEntry();
            $journal_entry->transaction_number = $transaction_number;
            $journal_entry->date               = $request->date;
            $journal_entry->description        = $request->description;
            $journal_entry->attachment         = $attachment;

            $coa    = $request->coa;
            $type   = $request->type;
            $amount = $request->amount;

            $data   = [];
            $debit  = [];
            $credit = [];
            for ($i = 0; $i < count($coa); $i++) {
                if ($type[$i] === 'K') {
                    $credit[] = $amount[$i];
                } else {
                    $debit[] = $amount[$i];
                }

                $data[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $coa[$i],
                    'type'               => $type[$i],
                    'value'              => $amount[$i],
                    'description'        => $request->description,
                    'reference_number'   => $transaction_number,
                    'phase'              => 'opr',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            $sum_debit  = array_sum($debit);
            $sum_credit = array_sum($credit);
            $balance    = ($sum_debit - $sum_credit);
            $value      = $sum_credit;

            if ($balance != 0) {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            } else {
                $journal_entry->value = $value;
                $journal_entry->save();

                GeneralLedger::insert($data);

                ActivityLogHelper::log('finance:journal_entry_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                    'date'                       => $request->date,
                    'value'                      => $value
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($journal_entry, 'Journal Entry Created Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_entry_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/journal-entries/{no_transaction}",
     *  summary="Delete journal entry",
     *  tags={"Finance - Journal Entries"},
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
                $check_transaction = JournalEntry::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);

                ActivityLogHelper::log('finance:journal_entry_delete', 1, ['finance:reference_number' => $no_transaction]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_entry_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
