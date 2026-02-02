<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\OperationController;
use App\Http\Resources\operation\OreShippingResource;
use App\Models\operation\ProvisionCoa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class OreShippingController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/ore_shipping",
     *  summary="Get the list of ore shipping",
     *  tags={"Operation - Ore Shipping"},
     *  @OA\Parameter(
     *      name="month",
     *      in="query",
     *      description="Month",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_kontraktor",
     *      in="query",
     *      description="Id Kontraktor",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_kontak",
     *      in="query",
     *      description="Id Kontak",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        // $start_date     = $request->start_date;
        // $end_date       = $request->end_date;
        $period         = $request->period;
        $type           = $request->type;

        $query = ProvisionCoa::query();

        $query->with([
            'toProvision.toShippingInstruction.toKontraktor',
            'toProvision.toShippingInstruction.toPlanBarging.toInvoiceFob',
            'toProvision.toKontak',
        ]);

        if ($request->id_kontraktor) {
            $query->whereHas('toProvision.toShippingInstruction', function ($q) use ($request) {
                $q->where('id_kontraktor', $request->id_kontraktor);
            });
        }

        if ($request->id_kontak) {
            $query->whereHas('toProvision', function ($q) use ($request) {
                $q->where('id_kontak', $request->id_kontak);
            });
        }

        // $query->whereHas('toProvision.toShippingInstruction', function ($q) use ($start_date, $end_date) {
        //     $q->whereBetween('departure_date', [$start_date, $end_date]);
        // });

        // if ($type == 'month') {
        //     $query->whereHas('toProvision.toShippingInstruction', function ($q) use ($period) {
        //         [$year, $month] = explode('-', $period);
        //         $q->whereYear('departure_date', $year)->whereMonth('departure_date', $month);
        //     });
        // } else {
        //     $query->whereHas('toProvision.toShippingInstruction', function ($q) use ($period) {
        //         $q->whereYear('departure_date', $period);
        //     });
        // }

        // if ($type == 'month') {
        //     [$year, $month] = explode('-', $period);
        //     $query->whereYear('date', $year)->whereMonth('date', $month);
        // } else {
        //     $query->whereYear('date', $period);
        // }
        switch ($type) {
            case 'month':
                [$year, $month] = explode('-', $period);
                $query->whereYear('date', $year)->whereMonth('date', $month);
                break;
            case 'quarter':
                [$q_start, $q_end] = explode(':', $period);
                $start_date = Carbon::createFromFormat('Y-m', $q_start)->startOfMonth()->toDateString();
                $end_date = Carbon::createFromFormat('Y-m', $q_end)->endOfMonth()->toDateString();
                $query->whereBetween('date', [$start_date, $end_date]);
                break;
            case 'year':
                $query->whereYear('date', $period);
                break;
            default:
                return Response::json(['success' => false, 'message' => 'Invalid period'], 400);
                break;
        }

        $data = $query->get();

        $oreshipping = $data->values(); // reset index
        $oreshipping = $oreshipping->map(function ($item, $index) {
            $item->key_index = $index + 1;
            return $item;
        });

        return ApiResponseClass::sendResponse(OreShippingResource::collection($oreshipping), 'Ore Shipping Retrieved Successfully');
    }
}
