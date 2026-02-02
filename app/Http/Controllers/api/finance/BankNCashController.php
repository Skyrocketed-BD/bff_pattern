<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\BankCashRequest;
use App\Http\Requests\finance\BankCashSearchRequest;
use App\Http\Resources\finance\BankNCashResource;
use App\Models\finance\BankNCash;
use App\Models\finance\Coa;
use Illuminate\Support\Facades\DB;

class BankNCashController extends Controller
{
    /**
     * @OA\Get(
     *  path="/bank-n-cash",
     *  summary="Get the list of bank and cash",
     *  tags={"Finance - Bank and Cash"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = BankNCash::with(['toCoa'])->orderBy('id_bank_n_cash', 'asc')->get();

        return ApiResponseClass::sendResponse(BankNCashResource::collection($data), 'Bank and Cash Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/bank-n-cash",
     *  summary="Create a new bank and cash",
     *  tags={"Finance - Bank and Cash"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="(bank, cash, petty_cash)"
     *              ),
     *              @OA\Property(
     *                  property="show",
     *                  type="string",
     *                  description="(y, n)"
     *              ),
     *              required={"id_coa", "type", "show"},
     *              example={
     *                  "id_coa": 1,
     *                  "type": "bank",
     *                  "show": "n"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(BankCashRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $bank_cash         = new BankNCash();
            $bank_cash->id_coa = $request->id_coa;
            $bank_cash->type   = $request->type;
            $bank_cash->show   = $request->show;
            $bank_cash->save();
            
            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:bank_and_cash_create', 1, [
                'finance:coa'             => Coa::find($request->id_coa)->name,
                'type'                    => $request->type,
                'finance:show_on_invoice' => $request->show == 'y' ? 'Yes' : 'No',
            ]);

            return ApiResponseClass::sendResponse($bank_cash, 'Bank and Cash Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_and_cash_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/bank-n-cash/{id}",
     *  summary="Get the detail of bank and cash",
     *  tags={"Finance - Bank and Cash"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = BankNCash::find($id);

        return ApiResponseClass::sendResponse(BankNCashResource::make($data), 'Bank and Cash Retrieved Successfully');
    }


    /**
     * @OA\Get(
     *  path="/bank-n-cash/search-by-type",
     *  summary="Search bank and cash by type",
     *  tags={"Finance - Bank and Cash"},
     *  @OA\Parameter(
     *      name="type",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      ),
     *      description="The type to filter by (e.g., 'bank', 'cash', 'petty_cash')"
     *  ),
     *  @OA\Response(response=200, description="Filtered list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function searchByType(BankCashSearchRequest $request)
    {
        $type = $request->type;

        if (!$type) {
            return ApiResponseClass::throw('Type is required', 400);
        }

        $data = BankNCash::where('type', $type)
            ->orderBy('id_bank_n_cash', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return ApiResponseClass::sendResponse([], 'No records found for the specified type');
        }

        $responseData = $data->map(function ($value) {
            return [
                "id_bank_n_cash" => $value->id_bank_n_cash,
                "id_coa"         => $value->id_coa,
                "coa_number"     => $value->toCoa->coa,
                "name"           => $value->toCoa->name,
                "type"           => $value->type,
            ];
        });

        // Return the filtered data
        return ApiResponseClass::sendResponse($responseData, 'Filtered Bank and Cash Retrieved Successfully');
    }


    /**
     * @OA\Put(
     *  path="/bank-n-cash/{id}",
     *  summary="Update the detail of bank and cash",
     *  tags={"Finance - Bank and Cash"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="(bank, cash, petty_cash)"
     *              ),
     *              @OA\Property(
     *                  property="show",
     *                  type="string",
     *                  description="(y, n)"
     *              ),
     *              required={"id_coa", "type", "show"},
     *              example={
     *                  "id_coa": 1,
     *                  "type": "bank",
     *                  "show": "n"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update($id, BankCashRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = BankNCash::find($id);

            $data->update([
                'id_coa' => $request->id_coa,
                'type'   => $request->type,
                'show'   => $request->show
            ]);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:bank_and_cash_update', 1, [
                'finance:coa'             => Coa::find($request->id_coa)->name,
                'type'                    => $request->type,
                'finance:show_on_invoice' => $request->show == 'y' ? 'Yes' : 'No',
            ]);

            return ApiResponseClass::sendResponse($data, 'Bank and Cash Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_and_cash_update', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/bank-n-cash/{id}",
     *  summary="Delete a bank and cash",
     *  tags={"Finance - Bank and Cash"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = BankNCash::find($id);

            $data->delete();
            
            ActivityLogHelper::log('finance:bank_and_cash_delete', 1, [
                'finance:coa'             => Coa::find($data->id_coa)->name,
                'type'                    => $data->type,
                'finance:show_on_invoice' => $data->show == 'y' ? 'Yes' : 'No',
            ]);

            return ApiResponseClass::sendResponse($data, 'Bank and Cash Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_and_cash_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
