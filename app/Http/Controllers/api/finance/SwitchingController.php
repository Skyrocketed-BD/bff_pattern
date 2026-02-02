<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\SwitchingRequest;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Switching;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class SwitchingController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/switching",
     *  summary="Get the list of switching",
     *  tags={"Finance - Switching"},
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

        $query = Switching::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('id_switching', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Switching Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/switching",
     *  summary="Create a new switching",
     *  tags={"Finance - Switching"},
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
     *                  property="ammount",
     *                  type="integer",
     *                  description="Ammount"
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
     *              required={"date", "description", "ammount", "coa", "type"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "ammount": 10000,
     *                  "coa": {1, 2},
     *                  "type": {"D", "K"},
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(SwitchingRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'switching', 'transaction_number', 'SW');

            $switching = new Switching();
            $switching->transaction_number = $transaction_number;
            $switching->date               = $request->date;
            $switching->description        = $request->description;

            $coa     = $request->coa;
            $type    = $request->type;
            $ammount = $request->ammount;

            // validasi saldo coa source
            $start_date         = '2001-01-01';
            $end_date           = Carbon::now()->format('Y-m-d');

            $id_coa             = $request->id_source;
            $check_coa          = Coa::whereIdCoa($id_coa)->first();
            $check_saldo_source = abs(_sum_coa_saldo($check_coa, $start_date, $end_date));

            if ($check_saldo_source < $ammount) {
                return Response::json([
                    'success' => false,
                    'message' => 'Insufficient Balance',
                ], 400);
            }

            $data   = [];
            $debit  = [];
            $credit = [];
            for ($i = 0; $i < count($coa); $i++) {
                if ($type[$i] === 'K') {
                    $credit[] = $ammount;
                } else {
                    $debit[] = $ammount;
                }

                $data[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $coa[$i],
                    'type'               => $type[$i],
                    'value'              => $ammount,
                    'description'        => $request->description,
                    'reference_number'   => $transaction_number,
                    'phase'              => 'opr',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            $sum_debit  = array_sum($debit);
            $sum_credit = array_sum($credit);
            $value      = $sum_debit != 0 ? $sum_debit : $sum_credit;

            $switching->value = $value;
            $switching->save();

            GeneralLedger::insert($data);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:account_switching_create', 1, [
                'finance:transaction_number' => $transaction_number,
                'date'                       => $request->date,
                'value'                      => $value
            ]);

            return ApiResponseClass::sendResponse($data, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:account_switching_create', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/switching/{no_transaction}",
     *  summary="Delete switching",
     *  tags={"Finance - Switching"},
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
                $check_transaction = Switching::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);

                ActivityLogHelper::log('finance:account_switching_delete', 1, ['finance:reference_number' => $no_transaction]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:account_switching_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
