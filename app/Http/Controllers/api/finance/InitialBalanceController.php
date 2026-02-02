<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\InitialBalanceRequest;
use App\Models\finance\GeneralLedger;
use App\Models\finance\InitialBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InitialBalanceController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/initial-balances",
     *  summary="Get the list of initial balances",
     *  tags={"Finance - Initial Balances"},
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

        $query = InitialBalance::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('id_initial_balance', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Initial Balances Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/initial-balances",
     *  summary="Create a new initial balance",
     *  tags={"Finance - Initial Balances"},
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
     *  @OA\Response(response=200, description="Initial Balance Created Successfully"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(InitialBalanceRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'initial_balances', 'transaction_number', 'SA');

            $initial_balance = new InitialBalance();
            $initial_balance->transaction_number = $transaction_number;
            $initial_balance->date               = $request->date;
            $initial_balance->description        = $request->description;

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
                    'phase'              => 'int',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            $sum_debit  = array_sum($debit);
            $sum_credit = array_sum($credit);
            $value      = $sum_debit != 0 ? $sum_debit : $sum_credit;

            $initial_balance->value = $value;
            $initial_balance->save();

            GeneralLedger::insert($data);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:initial_balance_create', 1, ['finance:reference_number' => $transaction_number, 'value' => $value]);
            return ApiResponseClass::sendResponse($initial_balance, 'Journal Entry Created Successfully');
        } catch (\Exception $e) {

            ActivityLogHelper::log('finance:initial_balance_create', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/initial-balances/{no_transaction}",
     *  summary="Delete initial balance",
     *  tags={"Finance - Initial Balances"},
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
                $check_transaction = InitialBalance::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);

                DB::connection('finance')->commit();

                ActivityLogHelper::log('finance:initial_balance_delete', 1, ['finance:reference_number' => $no_transaction]);

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:initial_balance_delete', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }
}
