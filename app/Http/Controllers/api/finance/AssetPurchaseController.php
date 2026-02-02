<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\AssetPurchaseRequest;
use App\Http\Resources\finance\AssetPurchaseResource;
use App\Models\finance\AssetHead;
use App\Models\finance\AssetItem;
use App\Models\finance\Journal;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use App\Models\main\Kontak;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AssetPurchaseController extends Controller
{
    /**
     * @OA\Get(
     *  path="/asset/purchase/{is_outstanding}",
     *  summary="Get the list of asset purchase",
     *  tags={"Finance - Asset Purchase"},
     *  @OA\Parameter(
     *      name="is_outstanding",
     *      in="path",
     *      required=true,
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index($is_outstanding)
    {
        $assetHeads = AssetHead::orderBy('id_asset_head', 'asc')->get();

        if ($assetHeads->isEmpty()) {
            return ApiResponseClass::sendResponse([], 'No assets found.');
        }

        $result = [];

        foreach ($assetHeads as $asset) {
            // Check if it has a regular transaction
            if ($asset->id_transaction !== null && $is_outstanding === '1') {
                $transaction = Transaction::find($asset->id_transaction);

                if ($transaction) {
                    $resource     = new AssetPurchaseResource($transaction, $is_outstanding);
                    $data         = $resource->toArray(request());
                    $data['name'] = $asset->name;
                    $result[]     = $data;
                }
            } 
            // Check if it has a full transaction
            else if ($asset->id_transaction_full !== null && $is_outstanding === '0') {
                $transactionFull = TransactionFull::find($asset->id_transaction_full);

                if ($transactionFull) {
                    $transactionFull->reference_number = $transactionFull->invoice_number;

                    $resource     = new AssetPurchaseResource($transactionFull, $is_outstanding);
                    $data         = $resource->toArray(request());
                    $data['name'] = $asset->name;
                    $result[]     = $data;
                }
            }
        }

        return ApiResponseClass::sendResponse(
            $result,
            count($result) > 0 
                ? 'Assets retrieved successfully.' 
                : 'No Assets found for these assets.'
        );
    }
    
    /**
     * @OA\Post(
     *     path="/asset/purchase",
     *     summary="Create asset purchase",
     *     tags={"Finance - Asset Purchase"},
     *     @OA\RequestBody(
     *         description="Asset Purchase Store",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id_kontak", type="integer", example=1),
     *             @OA\Property(property="id_journal", type="integer", example=1),
     *             @OA\Property(property="reference_number", type="string", example="INV-00001"),
     *             @OA\Property(property="date", type="string", format="date", example="2023-01-01"),
     *             @OA\Property(property="total", type="double", example=1000.00),
     *             @OA\Property(property="description", type="string", example="Asset Purchase"),
     *             @OA\Property(property="is_outstanding", type="boolean", example=false),
     *             @OA\Property(property="in_ex_tax", type="string", example="y"),
     *             @OA\Property(property="efaktur_number", type="string", example="EF-00001"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id_asset_coa", type="integer", example=1),
     *                     @OA\Property(property="id_asset_group", type="integer", example=1),
     *                     @OA\Property(property="id_asset_category", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Mobil A"),
     *                     @OA\Property(property="price", type="integer", example=1000000),
     *                     @OA\Property(
     *                         property="identity_number",
     *                         type="array",
     *                         @OA\Items(type="string", example="1234567890")
     *                     ),
     *                     @OA\Property(
     *                         property="attachment",
     *                         type="array",
     *                         @OA\Items(type="string", example="attachment1.jpg")
     *                     )
     *                 )
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Asset Purchase Stored Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Asset Purchase Stored Successfully"),
     *         ),
     *     ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(AssetPurchaseRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            if ($request->is_outstanding) {
                // transaction
                $transaction_number = generateFinNumber('transaction', 'transaction_number', 'INV');
            } else {
                // transaction_full
                $transaction_number = generateFinNumber('transaction_full', 'transaction_number', 'FU');
            }

            $result = _count_journal($request, $transaction_number);

            if ($result) {
                $general_ledger = [];

                foreach ($result as $key => $val) {
                    $general_ledger[] = [
                        'id_kontak'          => $request->id_kontak,
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $val['coa'],
                        'type'               => $val['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                    ];
                }

                if ($request->is_outstanding) {
                    // transaction
                    $request->category = 'pengeluaran';
                    $request->value    = $request->total;

                    $transaction = insert_transaction($request, $transaction_number, $request->reference_number);
                } else {
                    // transaction_full
                    $journal                 = Journal::where('id_journal', $request->id_journal)->first();
                    $request->invoice_number = $request->reference_number;
                    $request->category       = $journal->category;
                    $request->record_type    = $journal->alocation;
                    $request->value          = $request->total;

                    $transaction_full = insert_transaction_full($request, $transaction_number);
                }

                $details = array_map(function ($item) {
                    return (object) $item;
                }, $request->details);

                foreach ($details as $detail) {
                    $asset_head                    = new AssetHead();
                    $asset_head->id_asset_coa      = $detail->id_asset_coa;
                    $asset_head->id_asset_group    = $detail->id_asset_group;
                    $asset_head->id_asset_category = $detail->id_asset_category;
                    if ($request->is_outstanding) {
                        $asset_head->id_transaction = $transaction->id_transaction;
                    } else {
                        $asset_head->id_transaction_full = $transaction_full->id_transaction_full;
                    }
                    $asset_head->name              = $detail->name;
                    $asset_head->tgl               = $request->date;
                    $asset_head->save();

                    $total_price = 0;
                    $total_qty   = 0;
                    foreach ($detail->identity_number as $key => $item) {
                        $asset_number = generate_number('finance', 'asset_item', 'asset_number', 'AS');
                        $file         = isset($detail->attachment[$key]) ? add_file($detail->attachment[$key], 'asset_item/') : null;

                        $total       = (1 * $detail->price);
                        $total_price = $total_price + $total;
                        $total_qty++;

                        $asset_item                  = new AssetItem();
                        $asset_item->id_asset_head   = $asset_head->id_asset_head;
                        $asset_item->asset_number    = $asset_number;
                        $asset_item->identity_number = $item;
                        $asset_item->qty             = 1;
                        $asset_item->price           = $detail->price;
                        $asset_item->total           = $total;
                        $asset_item->attachment      = $file;
                        $asset_item->save();
                    }
                }

                ActivityLogHelper::log('finance:asset_purchase_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                    'finance:asset_name'         => $asset_head->name,
                    'finance:recipient'          => Kontak::find($request->id_kontak)->name,
                    'finance:total_price'        => $total_price,
                ]);

                insert_general_ledger($general_ledger, $transaction_number, $transaction_number);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Expenditure Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_purchase_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
