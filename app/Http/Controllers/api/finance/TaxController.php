<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TaxRequest;
use App\Models\finance\Tax;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    /**
     * @OA\Get(
     *  path="/taxs",
     *  summary="Get the list of taxs",
     *  tags={"Finance - Tax"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = Tax::orderBy('id_tax', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Tax Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/taxs",
     *  summary="Add a new tax",
     *  tags={"Finance - Tax"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Tax name"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Tax category (other, ppn)"
     *              ),
     *              required={"name", "category"},
     *              example={
     *                  "name": "Tax name",
     *                  "category": "other"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TaxRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $tax           = new Tax();
            $tax->name     = $request->name;
            $tax->category = $request->category;
            $tax->save();
            
            ActivityLogHelper::log('finance:tax_create', 1, [
                'finance:tax_name'           => $tax->name,
                'finance:tax_category'       => $tax->category
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($tax, 'Tax Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/taxs/{id}",
     *  summary="Get a tax",
     *  tags={"Finance - Tax"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = Tax::find($id);

        return ApiResponseClass::sendResponse($data, 'Tax Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/taxs/{id}",
     *  summary="Update a tax",
     *  tags={"Finance - Tax"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Tax name"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Tax category (other, ppn)"
     *              ),
     *              required={"name", "category"},
     *              example={
     *                  "name": "Tax name",
     *                  "category": "other"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TaxRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = Tax::find($id);

            $data->update([
                'name'     => $request->name,
                'category' => $request->category,
            ]);

            ActivityLogHelper::log('finance:tax_update', 1, [
                'finance:tax_name'          => $data->name,
                'finance:tax_category'      => $data->category
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Tax Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/taxs/{id}",
     *  summary="Delete a tax",
     *  tags={"Finance - Tax"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = Tax::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:tax_delete', 1, [
                'finance:tax_name'      => $data->name,
                'finance:tax_category'  => $data->category
            ]);

            return ApiResponseClass::sendResponse($data, 'Tax Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:tax_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
