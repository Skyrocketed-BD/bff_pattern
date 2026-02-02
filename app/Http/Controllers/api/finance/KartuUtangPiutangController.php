<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Resources\finance\TransactionResource;
use App\Models\finance\DownPayment;
use App\Models\finance\Expenditure;
use App\Models\finance\Receipts;
use App\Models\finance\Transaction;
use Illuminate\Http\Request;

class KartuUtangPiutangController extends Controller
{
    /**
     * @OA\Get(
     *  path="/kartu-utang-piutang/{type}/{id_kontak}",
     *  summary="Get the list of kartu utang piutang",
     *  tags={"Finance - Kartu Utang Piutang"},
     *  description="Get the list of kartu utang piutang",
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
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
    public function index(Request $request)
    {
        $type = $request->type;

        $category = $type == 'piutang' ? 'penerimaan' : 'pengeluaran';

        $query = Transaction::query();

        $query->with(['toReceipts', 'toExpenditure']);

        $query->where('category', $category);

        $query->where('status', 'valid');

        if (isset($request->id_kontak)) {
            $query->where('id_kontak', $request->id_kontak);
        }

        $result = $query->get();

        $items = TransactionResource::collection($result)->toArray($request);

        $items = array_filter($items, function ($value) use ($request) {
            return $value['sisa'] > 0;
        });

        $items = array_values($items);

        return ApiResponseClass::sendResponse($items, 'Kartu Utang Piutang Retrieved Successfully');
    }

    private function getDownPayment($id_kontak, $category)
    {
        $down_payment = DownPayment::with([
            'toDownPaymentDetail' => function ($query) use ($category) {
                $query->where('category', $category)->where('status', 'valid');
            }
        ])->where('id_kontak', $id_kontak)->first();

        $result = [];
        if (!empty($down_payment->toDownPaymentDetail)) {
            foreach ($down_payment->toDownPaymentDetail as $item) {
                $result[] = [
                    'transaction_number' => $item->transaction_number,
                    'date'               => $item->date,
                    'value'              => $item->value,
                    'posisi'             => $category == 'penerimaan' ? 'D' : 'K'
                ];
            }
        }

        return $result;
    }

    private function getTransaction($id_kontak, $category)
    {
        $transaction = Transaction::where('category', $category)->where('id_kontak', $id_kontak)->get();

        $result = [];
        if ($transaction->count() > 0) {
            foreach ($transaction as $item) {
                $result[] = [
                    'transaction_number' => $item->transaction_number,
                    'date'               => $item->date,
                    'value'              => $item->value,
                    'posisi'             => $category == 'penerimaan' ? 'D' : 'K'
                ];
            }
        }

        return $result;
    }

    private function getReceipts($id_kontak)
    {
        $receipts = Receipts::where('id_kontak', $id_kontak)->get();

        $result = [];
        if ($receipts->count() > 0) {
            foreach ($receipts as $item) {
                $result[] = [
                    'transaction_number' => $item->transaction_number,
                    'date'               => $item->date,
                    'value'              => $item->value,
                    'posisi'             => 'K'
                ];
            }
        }

        return $result;
    }

    private function getExpenditure($id_kontak)
    {
        $expenditure = Expenditure::where('id_kontak', $id_kontak)->get();

        $result = [];
        if ($expenditure->count() > 0) {
            foreach ($expenditure as $item) {
                $result[] = [
                    'transaction_number' => $item->transaction_number,
                    'date'               => $item->date,
                    'value'              => $item->value,
                    'posisi'             => 'D'
                ];
            }
        }

        return $result;
    }
}
