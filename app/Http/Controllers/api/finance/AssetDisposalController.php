<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Models\finance\AssetDisposal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\finance\AssetDisposalResource;
use App\Models\finance\AssetDisposalItem;
use App\Models\finance\AssetItem;
use App\Models\finance\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AssetDisposalController extends Controller
{
    /**
     * @OA\Get(
     *  path="/asset/disposal",
     *  summary="Get the list of asset disposals",
     *  tags={"Finance - Asset Disposal"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = AssetDisposal::with('toAssetDisposalItems')->orderBy('id_asset_disposal', 'asc')->get();

        return ApiResponseClass::sendResponse(AssetDisposalResource::collection($data), 'Asset Disposal Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/asset/disposal",
     *     summary="Create asset disposal",
     *     tags={"Finance - Asset Disposal"},
     *     @OA\RequestBody(
     *         description="Asset Disposal Store",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id_kontak", type="integer", example=1),
     *             @OA\Property(property="id_journal", type="integer", example=1),
     *             @OA\Property(property="reference_number", type="string", example="INV-00001"),
     *             @OA\Property(property="date", type="string", format="date", example="2023-01-01"),
     *             @OA\Property(property="total", type="double", example=1000.00),
     *             @OA\Property(property="is_outstanding", type="integer", example=0),
     *             @OA\Property(property="in_ex_tax", type="string", example="y"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="description", type="string", example="Asset Disposal"),
     *                     @OA\Property(property="status", type="string", enum={"rusak", "hilang", "jual"}, example="rusak"),
     *                     @OA\Property(property="price", type="integer", example=1000000),
     *                     @OA\Property(property="attachment", type="file", example="attachment.jpg"),
     *                     @OA\Property(
     *                         property="id_asset_item",
     *                         type="array",
     *                         @OA\Items(type="integer", example=1)
     *                     ),
     *                     @OA\Property(
     *                         property="selling_price",
     *                         type="array",
     *                         @OA\Items(type="double", example=1000000.00)
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
    public function store(Request $request)
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
                    $request->category = 'penerimaan';
                    $request->value    = $request->total;

                    $transaction = insert_transaction($request, $transaction_number, $request->reference_number);
                } else {
                    // transaction_full
                    $journal                 = Journal::where('id_journal', $request->id_journal)->first();
                    $request->invoice_number = $request->reference_number;
                    $request->category       = $journal->category;
                    $request->record_type    = $journal->alocation;

                    $transaction_full = insert_transaction_full($request, $transaction_number);
                }

                $details = array_map(function ($item) {
                    return (object) $item;
                }, $request->details);

                foreach ($details as $key => $detail) {
                    $file = isset($detail->attachment) ? add_file($detail->attachment, 'asset_disposal/') : null;

                    $assetDisposal = new AssetDisposal();
                    if ($request->is_outstanding) {
                        $assetDisposal->id_transaction = $transaction->id_transaction;
                    } else {
                        $assetDisposal->id_transaction_full = $transaction_full->id_transaction_full;
                    }
                    $assetDisposal->date               = $request->date;
                    $assetDisposal->description        = $detail->description;
                    $assetDisposal->attachment         = $file;
                    $assetDisposal->status             = $detail->status;
                    $assetDisposal->save();

                    $items = [];

                    foreach ($detail->id_asset_item as $key => $item) {
                        $items[] = [
                            'id_asset_item' => intval($item),
                            'book_value'    => $detail->book_value[$key] ?? null,
                            'selling_price' => $detail->selling_price[$key] ?? null,
                        ];
                    }

                    foreach ($items as $key => $item) {
                        $assetItem = AssetItem::with(['toAssetHead.toAssetGroup'])->find($item['id_asset_item']);

                        $book_value = book_value($assetItem->toAssetHead->tgl, $assetItem->price, $assetItem->toAssetHead->toAssetGroup->rate);


                        $assetDisposalItem                    = new AssetDisposalItem();
                        $assetDisposalItem->id_asset_disposal = $assetDisposal->id_asset_disposal;
                        $assetDisposalItem->id_asset_item     = $item['id_asset_item'];
                        $assetDisposalItem->purchase_price    = $assetItem->price; // use the price from AssetItem
                        $assetDisposalItem->book_value        = $book_value;
                        $assetDisposalItem->selling_price     = $item['selling_price'];
                        $assetDisposalItem->save();

                        $assetItem->disposal = 1; // Mark the asset item as disposed
                        $assetItem->save();
                    }
                }

                ActivityLogHelper::log('finance:asset_disposal_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                ]);

                insert_general_ledger($general_ledger, $transaction_number, $transaction_number);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Expenditure Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_disposal_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
