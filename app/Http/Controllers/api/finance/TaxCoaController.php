<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TaxCoaRequest;
use App\Http\Resources\finance\TaxCoaResource;
use App\Models\finance\TaxCoa;
use Illuminate\Support\Facades\DB;

class TaxCoaController extends Controller
{
    /**
     * @OA\Get(
     *  path="/tax-coas",
     *  summary="Get the list of tax_coas",
     *  tags={"Finance - Tax Coa"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = TaxCoa::get();

        $sortedData = $data->sortBy(function ($item) {
            return $item->toCoa->name;
        });

        return ApiResponseClass::sendResponse(TaxCoaResource::collection($sortedData), 'Tax Coa Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/tax-coas",
     *  summary="Add a new tax_coa",
     *  tags={"Finance - Tax Coa"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_tax",
     *                  type="integer",
     *                  description="Tax id"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              required={"id_tax", "id_coa"},
     *              example={
     *                  "id_tax": 1,
     *                  "id_coa": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TaxCoaRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $tax_coa         = new TaxCoa();
            $tax_coa->id_tax = $request->id_tax;
            $tax_coa->id_coa = $request->id_coa;
            $tax_coa->save();

            ActivityLogHelper::log('finance:tax_coa_create', 1, [
                'finance:tax' => $tax_coa->toTax->name,
                'finance:coa' => $tax_coa->toCoa->name
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($tax_coa, 'Tax Coa Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_coa_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/tax-coas/{id}",
     *  summary="Get tax_coa by id",
     *  tags={"Finance - Tax Coa"},
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
        $data = TaxCoa::find($id);

        return ApiResponseClass::sendResponse(TaxCoaResource::make($data), 'Tax Coa Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/tax-coas/{id}",
     *  summary="Update a tax_coa",
     *  tags={"Finance - Tax Coa"},
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
     *                  property="id_tax",
     *                  type="integer",
     *                  description="Tax id"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              required={"id_tax", "id_coa"},
     *              example={
     *                  "id_tax": 1,
     *                  "id_coa": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TaxCoaRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = TaxCoa::find($id);

            $data->update([
                'id_tax' => $request->id_tax,
                'id_coa' => $request->id_coa,
            ]);

            ActivityLogHelper::log('finance:tax_coa_update', 1, [
                'finance:tax' => $data->toTax->name,
                'finance:coa' => $data->toCoa->name
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse(TaxCoaResource::make($data), 'Tax Coa Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_coa_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/tax-coas/{id}",
     *  summary="Delete a tax_coa",
     *  tags={"Finance - Tax Coa"},
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
            $data = TaxCoa::findOrFail($id);

            $data->delete();

            ActivityLogHelper::log('finance:tax_coa_delete', 1, [
                'finance:tax' => $data->toTax->name,
                'finance:coa' => $data->toCoa->name
            ]);

            return ApiResponseClass::sendResponse(TaxCoaResource::make($data), 'Tax Coa Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_coa_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
