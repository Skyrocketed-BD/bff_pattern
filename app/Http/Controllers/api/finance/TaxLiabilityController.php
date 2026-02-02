<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\TaxLiabilityRequest;
use App\Http\Resources\finance\TaxLiabilityResource;
use App\Models\finance\GeneralLedger;
use App\Models\finance\TaxLiability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class TaxLiabilityController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/tax-liability",
     *  summary="Tax Liability List",
     *  tags={"Finance - Tax Liability"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $data = TaxLiability::whereBetween('date', [$start_date, $end_date])
            ->when(isset($request->status), function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->orderBy('date', 'asc')
            ->get();

        return ApiResponseClass::sendResponse(TaxLiabilityResource::collection($data), 'Tax Liability Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/tax-liability",
     *  summary="Add a new tax liability",
     *  tags={"Finance - Tax Liability"},
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
     *                  property="rows",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="coa",
     *                          type="string",
     *                          description="COA"
     *                      ),
     *                      @OA\Property(
     *                          property="liability_amount",
     *                          type="number",
     *                          description="Liability Amount"
     *                      )
     *                  ),
     *                  description="Rows"
     *              ),
     *              @OA\Property(
     *                  property="total_expense",
     *                  type="number",
     *                  description="Total Expense"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="number",
     *                  description="ID COA"
     *              )
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TaxLiabilityRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'tax_liability', 'transaction_number', 'CB');

            $tax_liability                     = new TaxLiability();
            $tax_liability->date               = $request->date;
            $tax_liability->transaction_number = $transaction_number;
            $tax_liability->id_coa             = $request->id_coa_expense;
            $tax_liability->value              = $request->total_expense;
            $tax_liability->description        = $request->description;

            if (isset($request->attachment)) {
                $attachment                = add_file($request->attachment, 'tax_liability/');
                $tax_liability->attachment = $attachment;
            }

            // insert ke table
            $tax_liability->save();

            // buat journal pembayaran utang sisi Debit
            foreach ($request->rows as $row) {
                $journals[] = [
                    'coa'   => $row['coa'],
                    'type'  => 'D',
                    'value' => $row['liability_amount'],
                ];
            }

            // buat journal pembayaran utang sisi Kredit
            $journals[] = [
                'coa'   => $request->coa_expense,
                'type'  => 'K',
                'value' => $request->total_expense
            ];

            // buat array untuk insert ke GL
            foreach ($journals as $journal) {
                $general_ledger[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $journal['coa'],
                    'type'               => $journal['type'],
                    'value'              => $journal['value'],
                    'description'        => $request->description,
                    'reference_number'   => $transaction_number,
                    'phase'              => 'opr',
                    'created_by'         => auth('api')->user()->id_users,
                ];
            }

            // insert ke GL
            GeneralLedger::insert($general_ledger);

            ActivityLogHelper::log('finance:tax_liability_create', 1, [
                'finance:transaction_number' => $transaction_number,
                'finance:payment_source'     => $tax_liability->toCoa->name,
                'total'                      => $tax_liability->value
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($tax_liability, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_liability_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/tax-liability/{no_transaction}",
     *  summary="Delete tax liability",
     *  tags={"Finance - Tax Liability"},
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
                $check_transaction = TaxLiability::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);

                ActivityLogHelper::log('finance:tax_liability_delete', 1, ['finance:reference_number' => $no_transaction]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_liability_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
