<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\BankAdministrationRequest;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use App\Models\finance\BankAdministration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BankAdministrationController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/bank-administration",
     *  summary="Get the list of bank administration",
     *  tags={"Finance - Bank Administration"},
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

        $query = BankAdministration::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Bank Administration Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/bank-administration",
     *  summary="Create a new bank administration",
     *  tags={"Finance - Bank Administration"},
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
     *                  property="id_coa_bank",
     *                  type="integer",
     *                  description="ID COA Bank"
     *              ),
     *              @OA\Property(
     *                  property="bank_fee",
     *                  type="integer",
     *                  description="Ammount Bank Fee"
     *              ),
     *              required={"date", "description", "id_coa_bank", "bank_fee", "bank_interest"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "id_coa_bank": 1,
     *                  "bank_fee": 10000,
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(BankAdministrationRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'bank_administration', 'transaction_number', 'BA');

            $bank_administration                     = new BankAdministration();
            $bank_administration->transaction_number = $transaction_number;
            $bank_administration->id_coa_bank        = $request->id_coa_bank;
            $bank_administration->date               = $request->date;
            $bank_administration->description        = $request->description;
            $bank_administration->bank_fee           = $request->bank_fee;

            $id_coa_bank_fee = get_arrangement('bank_fee_coa');  // admin bank
            $id_coa_bank     = $request->id_coa_bank;            // bank

            $coa_bank_fee = Coa::whereIdCoa($id_coa_bank_fee)->first();
            $coa_bank     = Coa::whereIdCoa($id_coa_bank)->first();

            $bank_fee      = $request->bank_fee;

            $journal = [
                [
                    'coa'   => $coa_bank_fee->coa,
                    'type'  => 'D',
                    'value' => $bank_fee
                ],
                [
                    'coa'   => $coa_bank->coa,
                    'type'  => 'K',
                    'value' => $bank_fee
                ]
            ];

            foreach ($journal as $key => $value) {
                $general_ledger[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $value['coa'],
                    'type'               => $value['type'],
                    'value'              => $value['value'],
                    'description'        => $request->description,
                    'reference_number'   => $transaction_number,
                    'phase'              => 'opr',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            $bank_administration->save();

            GeneralLedger::insert($general_ledger);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:bank_administration_create', 1, [
                'finance:coa_bank'           => Coa::find($request->id_coa_bank)->name,
                'finance:bank_fee'           => $bank_fee,
                'finance:transaction_number' => $transaction_number
            ]);

            return ApiResponseClass::sendResponse($bank_administration, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_administration_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/bank-administration/{no_transaction}",
     *  summary="Delete bank administration",
     *  tags={"Finance - Bank Administration"},
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
                $check_transaction = BankAdministration::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);

                ActivityLogHelper::log('finance:bank_administration_delete', 1, [
                    'finance:transaction_number' => $no_transaction,
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_administration_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
