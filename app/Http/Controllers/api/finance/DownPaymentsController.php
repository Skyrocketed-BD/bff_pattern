<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\DownPaymentRequest;
use App\Http\Resources\finance\DownPaymentResource;
use App\Models\finance\DownPayment;
use App\Models\finance\DownPaymentDetails;
use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class DownPaymentsController extends Controller
{

    /**
     * @OA\Get(
     *  path="/down-payments",
     *  summary="Get the list of down payments",
     *  tags={"Finance - Down Payments"},
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
        $query = DownPayment::query();

        $query->with([
            'toDownPaymentDetail' => function ($query) {
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

        $query->orderBy('id_down_payment', 'asc');

        $data = $query->get();

        return ApiResponseClass::sendResponse(DownPaymentResource::collection($data), 'Down Payments Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/down-payments",
     *     summary="Store Down Payment",
     *     tags={"Finance - Down Payments"},
     *     @OA\RequestBody(
     *         description="Down Payments Store",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id_kontak", type="integer", example=1),
     *             @OA\Property(property="id_invoice_bill", type="integer", example=1),
     *             @OA\Property(property="category", type="string", enum={"penerimaan", "pengeluaran"}, example="penerimaan"),
     *             @OA\Property(property="date", type="string", format="date", example="2023-01-01"),
     *             @OA\Property(property="total", type="double", example=1000.00),
     *             @OA\Property(property="description", type="string", example="Pembayaran Uang Muka"),
     *             @OA\Property(property="attachment", type="string", format="binary", example="file.pdf"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Down Payments Stored Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Down Payments Stored Successfully"),
     *         ),
     *     ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(DownPaymentRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('down_payment_details', 'transaction_number', 'DP');

            $request->merge([
                'id_journal' => get_arrangement('down_payment_deposit_journal'),
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
                $check_down_payment = DownPayment::where('id_kontak', $request->id_kontak)->first();

                if (!$check_down_payment) {
                    $down_payment = new DownPayment();
                    $down_payment->id_kontak = $request->id_kontak;
                    $down_payment->save();
                }

                $id_down_payment = $check_down_payment->id_down_payment ?? $down_payment->id_down_payment;

                $attachment = add_file($request->file('attachment'), 'down_payments/');

                $down_payment_details                     = new DownPaymentDetails();
                $down_payment_details->id_down_payment    = $id_down_payment;
                $down_payment_details->id_invoice_bill    = $request->id_invoice_bill ?? null;
                $down_payment_details->category           = 'penerimaan';
                $down_payment_details->transaction_number = $transaction_number;
                $down_payment_details->date               = $request->date;
                $down_payment_details->value              = $request->total;
                $down_payment_details->description        = $request->description;
                $down_payment_details->attachment         = $attachment;
                $down_payment_details->save();

                ActivityLogHelper::log('finance:down_payment_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                    'category'                   => 'penerimaan',
                    'total'                      => $request->total,
                ]);

                insert_general_ledger($general_ledger, $transaction_number, $transaction_number);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($down_payment_details, 'Down Payment Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:down_payment_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/down-payments/kontak/{id_kontak}",
     *  summary="Get the list of down payments by id_kontak and category",
     *  tags={"Finance - Down Payments"},
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
        $data = DownPayment::with([
            'toDownPaymentDetail' => function ($query) {
                $query->where('status', 'valid');
            }
        ])->where('id_kontak', $id_kontak)->first();

        $total = $data?->toDownPaymentDetail->sum(function ($detail) {
            return $detail->category === 'penerimaan' ? $detail->value : -$detail->value;
        }) ?? 0;

        $result = [
            'total' => $total,
        ];

        return ApiResponseClass::sendResponse($result, 'Down Payments Retrieved Successfully');
    }
}
