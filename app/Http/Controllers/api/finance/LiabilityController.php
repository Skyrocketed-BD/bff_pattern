<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\LiabilityRequest;
use App\Http\Resources\finance\LiabilityResource;
use App\Models\finance\Liability;
use App\Models\finance\LiabilityDetail;
use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class LiabilityController extends Controller
{
    /**
     * @OA\Get(
     *  path="/advance-payments",
     *  summary="Get the list of advance payments",
     *  tags={"Finance - Advance Payments"},
     *  @OA\Parameter(
     *      name="id_kontak_jenis",
     *      in="query",
     *      required=false,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $query = Liability::query();

        $query->with([
            'toLiabilityDetail' => function ($query) {
                $query->where('status', 'valid');
            },
            'toKontak.toKontakJenis'
        ]);

        if ($request->filled('id_kontak_jenis')) {
            $id_kontak_jenis = $request->id_kontak_jenis;

            $kontak = Kontak::whereHas('toKontakJenis', function ($qry) use ($id_kontak_jenis) {
                $qry->where('id_kontak_jenis', $id_kontak_jenis);
            })->pluck('id_kontak');

            $query->whereIn('id_kontak', $kontak);
        }

        $query->orderBy('id_liability', 'asc');

        $data = $query->get();

        return ApiResponseClass::sendResponse(LiabilityResource::collection($data), 'Advance Payments Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/advance-payments",
     *     summary="Store Advance Payment",
     *     tags={"Finance - Advance Payments"},
     *     @OA\RequestBody(
     *         description="Advance Payments Store",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id_kontak", type="integer", example=1),
     *             @OA\Property(property="id_invoice_bill", type="integer", example=1),
     *             @OA\Property(property="category", type="string", enum={"penerimaan", "pengeluaran"}, example="penerimaan"),
     *             @OA\Property(property="date", type="string", format="date", example="2023-01-01"),
     *             @OA\Property(property="total", type="double", example=1000.00),
     *             @OA\Property(property="description", type="string", example="Pembayaran Liability"),
     *             @OA\Property(property="attachment", type="string", format="binary", example="file.pdf"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Advance Payments Stored Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Advance Payments Stored Successfully"),
     *         ),
     *     ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(LiabilityRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('liability_details', 'transaction_number', 'AP');

            $request->merge([
                'id_journal' => get_arrangement('advance_payment_deposit_journal'),
                'in_ex_tax'  => 'y',
            ]);

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

                // mengecek apa bila id_kontak sudah didaftar
                $check_liability = Liability::where('id_kontak', $request->id_kontak)->first();

                if (!$check_liability) {
                    $liability = new Liability();
                    $liability->id_kontak = $request->id_kontak;
                    $liability->save();
                }

                $id_liability = $check_liability->id_liability ?? $liability->id_liability;

                $attachment = add_file($request->file('attachment'), 'liabilities/');

                $liability_details                     = new LiabilityDetail();
                $liability_details->id_liability       = $id_liability;
                $liability_details->id_invoice_bill    = $request->id_invoice_bill ?? null;
                $liability_details->category           = 'penerimaan';
                $liability_details->transaction_number = $transaction_number;
                $liability_details->date               = $request->date;
                $liability_details->value              = $request->total;
                $liability_details->description        = $request->description;
                $liability_details->attachment         = $attachment;
                $liability_details->save();

                ActivityLogHelper::log('finance:advance_payment_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                    'category'                   => 'penerimaan',
                    'total'                      => $request->total,
                ]);

                insert_general_ledger($general_ledger, $transaction_number, $transaction_number);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($liability_details, 'Advance Payment Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:advance_payment_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/advance-payments/kontak/{id_kontak}",
     *  summary="Get the list of liabilities by id_kontak and category",
     *  tags={"Finance - Advance Payments"},
     *  @OA\Parameter(
     *      name="id_kontak",
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
    public function getContactSummary($id_kontak)
    {
        $data = Liability::with([
            'toLiabilityDetail' => function ($query) {
                $query->where('status', 'valid');
            }
        ])->where('id_kontak', $id_kontak)->first();

        $total = $data?->toLiabilityDetail->sum(function ($detail) {
            return $detail->category === 'penerimaan' ? $detail->value : -$detail->value;
        }) ?? 0;

        $result = [
            'total' => $total,
        ];

        return ApiResponseClass::sendResponse($result, 'Advance Payments Retrieved Successfully');
    }
}
