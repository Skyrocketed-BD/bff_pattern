<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\AssetItemRequest;
use App\Http\Resources\finance\AssetHeadResource;
use App\Http\Resources\finance\AssetItemResource;
use App\Http\Resources\main\SelectResource;
use App\Models\finance\AssetHead;
use App\Models\finance\AssetItem;
use App\Models\finance\ClosingDepreciation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AssetItemController extends Controller
{
    /**
     * @OA\Get(
     *  path="/asset/item",
     *  summary="Get the list of asset item",
     *  tags={"Finance - Asset Item"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);
        $slug       = $request->slug;

        $query = AssetHead::query();

        $query->with([
            'toAssetGroup',
            'toAssetCategory',
            'toAssetItem' => function ($query) {
                $query->where('disposal', '0');
            }
        ]);

        $query->whereBetween('tgl', [$start_date, $end_date]);

        if (($slug != 'undefined')) {
            $query->where('id_asset_coa', $slug);
        }

        $data = $query->get();

        return ApiResponseClass::sendResponse(AssetHeadResource::collection($data), 'Asset Item Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/asset/item",
     *  summary="Create new asset item",
     *  tags={"Finance - Asset Item"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_asset_coa",
     *                  type="integer",
     *                  description="Asset coa id"
     *              ),
     *              @OA\Property(
     *                  property="id_asset_group",
     *                  type="integer",
     *                  description="Asset group id"
     *              ),
     *              @OA\Property(
     *                  property="id_asset_category",
     *                  type="integer",
     *                  description="Asset category id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Asset name"
     *              ),
     *              @OA\Property(
     *                  property="tgl",
     *                  type="string",
     *                  format="date",
     *                  description="Asset date"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="integer",
     *                  description="Asset price"
     *              ),
     *              @OA\Property(
     *                  property="identity_number[]",
     *                  type="array",
     *                  @OA\Items(type="string"),
     *                  description="Asset identity number must be unique"
     *              ),
     *              @OA\Property(
     *                  property="attachment[]",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      format="binary",
     *                      description="Asset attachment"
     *                  ),
     *                  description="Asset attachment (multiple files allowed)"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(AssetItemRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $date  = $request->tgl;
            $year  = Carbon::parse($date)->format('Y');
            $month = Carbon::parse($date)->format('m');

            $check_closing_depreciation = ClosingDepreciation::where('month', $month)->where('year', $year)->first();

            if ($check_closing_depreciation) {
                return Response::json(['message' => 'Already Closed on Selected Period !'], 400);
            } else {
                $identity_number = $request->identity_number;
                $attachment      = $request->attachment;

                $asset_head = new AssetHead();
                $asset_head->id_asset_coa      = $request->id_asset_coa;
                $asset_head->id_asset_group    = $request->id_asset_group;
                $asset_head->id_asset_category = $request->id_asset_category;
                $asset_head->name              = $request->name;
                $asset_head->tgl               = $request->tgl;
                $asset_head->save();

                $total_price = 0;
                $total_qty   = 0;

                foreach ($identity_number as $key => $value) {
                    $asset_number = generate_number('finance', 'asset_item', 'asset_number', 'AS');
                    $file         = isset($attachment[$key]) ? add_file($attachment[$key], 'asset_item/') : null;

                    $total       = (1 * $request->price);
                    $total_price = $total_price + $total;
                    $total_qty++;

                    $asset_item = new AssetItem();
                    $asset_item->id_asset_head   = $asset_head->id_asset_head;
                    $asset_item->asset_number    = $asset_number;
                    $asset_item->identity_number = $value;
                    $asset_item->qty             = 1;
                    $asset_item->price           = $request->price;
                    $asset_item->total           = $total;
                    $asset_item->attachment      = $file;
                    $asset_item->save();
                }

                DB::connection('finance')->commit();

                ActivityLogHelper::log('finance:asset_item_create', 1, ['name' => $request->name, 'finance:price' => $request->price, 'finance:quantity' => $total_qty, 'finance:total' => $total_price]);

                return ApiResponseClass::sendResponse($asset_head, 'Asset Item Created Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_item_create', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/asset/item/{id}",
     *  summary="Get the detail of asset item",
     *  tags={"Finance - Asset Item"},
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
        $data = AssetItem::find($id);

        return ApiResponseClass::sendResponse(AssetItemResource::make($data), 'Asset Item Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/asset/item/{id}",
     *  summary="Update the asset item",
     *  tags={"Finance - Asset Item"},
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
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_asset_coa",
     *                  type="integer",
     *                  description="Asset coa id"
     *              ),
     *              @OA\Property(
     *                  property="id_asset_group",
     *                  type="integer",
     *                  description="Asset group id"
     *              ),
     *              @OA\Property(
     *                  property="id_asset_category",
     *                  type="integer",
     *                  description="Asset category id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Asset name"
     *              ),
     *              @OA\Property(
     *                  property="tgl",
     *                  type="string",
     *                  format="date",
     *                  description="Asset date"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="integer",
     *                  description="Asset price"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Asset total"
     *              ),
     *              @OA\Property(
     *                  property="identity_number[]",
     *                  type="array",
     *                  @OA\Items(type="string"),
     *                  description="Asset identity number must be unique"
     *              ),
     *              @OA\Property(
     *                  property="attachment[]",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      format="binary",
     *                      description="Asset attachment"
     *                  ),
     *                  description="Asset attachment (multiple files allowed)"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Asset item updated successfully"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(AssetItemRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = AssetItem::find($id);

            $data->update([
                'id_asset_coa'   => $request->id_asset_coa,
                'id_asset_group' => $request->id_asset_group,
                'name'           => $request->name,
                'tgl'            => $request->tgl,
                'qty'            => $request->qty,
                'price'          => $request->price,
                'total'          => $request->total,
            ]);

            ActivityLogHelper::log('finance:asset_item_update', 1, [
                'name'             => $request->name,
                'finance:price'    => $request->price,
                'finance:quantity' => $request->qty,
                'finance:total'    => $request->total
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Asset Item Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_item_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/asset/item/{id}",
     *  summary="Delete the asset item",
     *  tags={"Finance - Asset Item"},
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
        DB::connection('finance')->beginTransaction();
        try {
            $data = AssetHead::where('id_asset_head', $id)->with(['toAssetItem'])->first();

            $date  = $data->tgl;
            $year  = Carbon::parse($date)->format('Y');
            $month = Carbon::parse($date)->format('m');

            $check_closing_depreciation = ClosingDepreciation::where('month', $month)->where('year', $year)->first();

            if ($check_closing_depreciation) {
                return ApiResponseClass::throw('Item Already Closed on Selected Period !', 400);
            }

            delete_transaction($data->id_transaction);
            
            delete_transaction_full($data->id_transaction_full);

            $data->delete();

            ActivityLogHelper::log('finance:asset_item_delete', 1, [
                'name'             => $data->name,
                'finance:price'    => $data->toAssetItem[0]->price,
                'finance:quantity' => $data->toAssetItem[0]->qty,
                'finance:total'    => $data->toAssetItem[0]->total
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Asset Item Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_item_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/asset/item/filter/{id_asset_category}",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Asset Item"},
     *  @OA\Parameter(
     *      name="id_asset_category",
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
    public function filter($id_asset_category)
    {
        $asset_head = AssetHead::with(['toAssetItem'])->where('id_asset_category', $id_asset_category)->whereHas('toAssetItem', function ($query) {
            $query->where('disposal', '0');
        })->get();

        $set_date   = Carbon::now()->format('Y-m-d');

        $result = [];
        foreach ($asset_head as $key => $value) {
            if ($value->toAssetItem) {
                foreach ($value->toAssetItem as $key2 => $value2) {
                    $first_date    = $value->tgl;
                    $current_date  = Carbon::now()->format('Y-m-d');
                    $dayDifference = Carbon::parse($first_date)->diffInDays(Carbon::parse($current_date));
                    $lifespan      = get_arrangement('lifespan');

                    $start_month     = Carbon::parse($first_date)->floorMonth();
                    $end_month       = Carbon::parse($set_date)->floorMonth();
                    $monthDifference = $start_month->diffInMonths($end_month);

                    $rate                = ($value2->toAssetHead->toAssetGroup->rate / 100);
                    $depreciation        = round(($value2->total * $rate) * (1 / 12), 0);
                    $depreciation_amount = ($depreciation * $monthDifference);
                    $gl                  = ($value2->total - $depreciation_amount);

                    $result[] = [
                        'id_asset_head'       => $value->id_asset_head,
                        'id_asset_coa'        => $value->id_asset_coa,
                        'id_asset_group'      => $value->id_asset_group,
                        'id_asset_category'   => $value->id_asset_category,
                        'id_asset_item'       => $value2->id_asset_item,
                        'identity_number'     => $value2->identity_number,
                        'asset_number'        => $value2->asset_number,
                        'name'                => $value->name . ' - ' . $value2->identity_number,
                        'tgl'                 => $value->tgl,
                        'qty'                 => $value2->qty,
                        'price'               => $value2->price,
                        'total'               => $value2->total,
                        'group'               => $value2->toAssetHead->toAssetGroup->name,
                        'rate'                => $value2->toAssetHead->toAssetGroup->rate . '%',
                        'depreciation'        => ($depreciation),
                        'depreciation_amount' => ($depreciation_amount),
                        'gl'                  => ($gl),
                    ];
                }
            }
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/asset/item/not/disposed",
     *  summary="Get the list of asset items not disposed",
     *  tags={"Finance - Asset Item"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function assetItemNotDisposed()
    {
        $data = AssetItem::with(['toAssetHead'])
            ->where('disposal', '0')
            ->orderBy('id_asset_item', 'asc')
            ->get();

        return ApiResponseClass::sendResponse(
            SelectResource::using(
                'id_asset_item', 
                fn($m) => $m->toAssetHead->name // or whatever column is inside toAssetHead
            )::collection($data),
            'Asset Item Not Disposed Retrieved Successfully'
        );
    }
}
