<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TaxRateRequest;
use App\Http\Resources\finance\TaxRateResource;
use App\Models\finance\TaxRate;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class TaxRateController extends Controller
{
    /**
     * @OA\Get(
     *  path="/tax-rates",
     *  summary="Get the list of tax rates",
     *  tags={"Finance - Tax Rate"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $id_tax_type = $request->query('id_tax_type');

        if (isset($id_tax_type)) {
            $data = TaxRate::where('id_tax', $id_tax_type)->orderBy('kd_tax', 'asc')->get();
        } else {
            $data = TaxRate::orderBy('kd_tax', 'asc')->get();
        }

        return ApiResponseClass::sendResponse(TaxRateResource::collection($data), 'Tax Rate Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/tax-rates",
     *  summary="Add a new tax rate",
     *  tags={"Finance - Tax Rate"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_tax",
     *                  type="integer",
     *                  description="Id tax"
     *              ),
     *              @OA\Property(
     *                  property="kd_tax",
     *                  type="string",
     *                  description="Kd tax"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="rate",
     *                  type="number",
     *                  description="Rate"
     *              ),
     *              @OA\Property(
     *                  property="ref",
     *                  type="string",
     *                  description="Ref"
     *              ),
     *              @OA\Property(
     *                  property="effective_date",
     *                  type="date",
     *                  description="Effective date of tax"
     *              ),
     *              required={"id_tax", "kd_tax", "name", "rate", "ref", "effective_date"},
     *              example={
     *                  "id_tax": 1,
     *                  "kd_tax": "TAX001",
     *                  "name": "Tax 1",
     *                  "rate": 15,
     *                  "ref": "Tax 1",
     *                  "effective_date": "2020-01-01",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TaxRateRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $tax_rate                 = new TaxRate();
            $tax_rate->id_tax         = $request->id_tax;
            $tax_rate->kd_tax         = $request->kd_tax;
            $tax_rate->name           = $request->name;
            $tax_rate->rate           = $request->rate;
            $tax_rate->ref            = $request->ref;
            $tax_rate->effective_date = $request->effective_date;
            $tax_rate->save();

            ActivityLogHelper::log('finance:tax_rate_create', 1, [
                'finance:tax_name' => $tax_rate->name,
                'finance:tax_code' => $tax_rate->kd_tax,
                'finance:tax_type' => $tax_rate->toTax->name,
                'finance:rate'     => $tax_rate->rate . '%',
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($tax_rate, 'Tax Rate Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_rate_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/tax-rates/{id}",
     *  summary="Get tax rate detail",
     *  tags={"Finance - Tax Rate"},
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
        $data = TaxRate::find($id);

        return ApiResponseClass::sendResponse(TaxRateResource::make($data), 'Tax Rate Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/tax-rates/{id}",
     *  summary="Update tax rate detail",
     *  tags={"Finance - Tax Rate"},
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
     *                  description="Id tax"
     *              ),
     *              @OA\Property(
     *                  property="kd_tax",
     *                  type="string",
     *                  description="Kd tax"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="rate",
     *                  type="number",
     *                  description="Rate"
     *              ),
     *              @OA\Property(
     *                  property="ref",
     *                  type="string",
     *                  description="Ref"
     *              ),
     *              @OA\Property(
     *                  property="effective_date",
     *                  type="date",
     *                  description="Effective date of tax"
     *              ),
     *              required={"id_tax", "kd_tax", "name", "rate", "ref", "effective_date"},
     *              example={
     *                  "id_tax": 1,
     *                  "kd_tax": "TAX001",
     *                  "name": "Tax 1",
     *                  "rate": 15,
     *                  "ref": "Tax 1",
     *                  "effective_date": "2020-01-01",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TaxRateRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = TaxRate::find($id);

            $data->update([
                'id_tax'         => $request->id_tax,
                'kd_tax'         => $request->kd_tax,
                'name'           => $request->name,
                'rate'           => $request->rate,
                'ref'            => $request->ref,
                'effective_date' => $request->effective_date,
            ]);

            ActivityLogHelper::log('finance:tax_rate_update', 1, [
                'finance:tax_name' => $data->name,
                'finance:tax_code' => $data->kd_tax,
                'finance:tax_type' => $data->toTax->name,
                'finance:rate'     => $data->rate . '%',
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Tax Rate Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_rate_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/tax-rates/{id}",
     *  summary="Delete tax rate detail",
     *  tags={"Finance - Tax Rate"},
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
            $data = TaxRate::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:tax_rate_delete', 1, [
                'finance:tax_name' => $data->name,
                'finance:tax_code' => $data->kd_tax,
                'finance:tax_type' => $data->toTax->name,
                'finance:rate'     => $data->rate . '%',
            ]);

            return ApiResponseClass::sendResponse($data, 'Tax Rate Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_rate_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/tax-rates/group/{id_tax}",
     *  summary="Get tax rate group",
     *  tags={"Finance - Tax Rate"},
     *  @OA\Parameter(
     *      name="id_tax",
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
    public function group($id_tax)
    {
        $data = TaxRate::whereIdTax($id_tax)->get();

        return ApiResponseClass::sendResponse(TaxRateResource::collection($data), 'Tax Rate Retrieved Successfully');
    }
}
