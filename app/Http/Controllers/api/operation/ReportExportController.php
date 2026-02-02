<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Exports\Operation\ContactExport;
use App\Exports\Operation\ContractorExport;
use App\Exports\Operation\OreShippingExport;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\main\Kontak;
use App\Models\main\User;
use App\Models\operation\ProvisionCoa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{

    public function all(Request $request)
    {
        $title        = 'ORE SHIPPING';
        $current      = Carbon::now()->format('YmdHis');

        $period         = $request->period;
        $type           = $request->type;

        $query = ProvisionCoa::query();

        $query->with([
            'toProvision.toShippingInstruction.toKontraktor',
            'toProvision.toShippingInstruction.toPlanBarging.toInvoiceFob',
            // 'toProvision.toShippingInstruction.toPlanBarging.toInvoiceFob.toKontak',
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

        $datas = $query->get();
        $result = [];

        if ($datas) {
            foreach ($datas as $key => $value) {
                $result[] = [
                    'id_kontak'       => $value->toProvision->toKontak->id_kontak ?? null,
                    'id_kontraktor'   => $value->toProvision->toShippingInstruction->id_kontraktor,
                    'kontraktor'      => $value->toProvision->toShippingInstruction->toKontraktor->company,
                    'number_si'       => $value->toProvision->toShippingInstruction->number_si ?? null,
                    'buyer'           => $value->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->toKontak->name ?? '-',
                    'initial'         => $value->toProvision->toShippingInstruction->toKontraktor->initial,
                    'tug_boat'        => $value->toProvision->toShippingInstruction->tug_boat,
                    'barge'           => $value->toProvision->toShippingInstruction->barge,
                    'consignee'       => $value->toProvision->toShippingInstruction->consignee,
                    'date'            => $value->date,
                    'departure_date'  => $value->toProvision->departure_date,
                    'load_amount'     => $value->toProvision->toShippingInstruction->load_amount,
                    'ni_provision'    => $value->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->ni,
                    'mc_provision'    => $value->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->mc,
                    'price_provision' => $value->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->price,
                    'inv_final'       => $value->no_invoice,
                    'ni_final'        => $value->ni_final,
                    'mc_final'        => $value->mc_final,
                    'tonage_final'    => $value->tonage_final,
                    'price_final'     => $value->price
                ];
            }
        }
        // return $result;
        $export = new OreShippingExport($result);
        $export->setTitle($title);
        $export->setPeriode($period);
        $export->setType($type);

        ActivityLogHelper::log('report_export', 1, [
            'title'     => $title,
            'period'    => $period,
            'type'      => $type,
        ]);

        return Excel::download($export, 'OreShipping-'.$current.'.xlsx');

        $file_name = str_replace(' ', '', $title) . '-' . $current . '.xlsx';

        return Excel::download($export, $file_name, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Disposition' => 'attachment; filename="\$file_name\"',
        ]);
    }
    
    /**
     * @OA\GET(
     *     path="/export/contractor",
     *     tags={"Operation - Report Export"},
     *     summary="Export Data Kontraktor",
     *     description="Export Data Kontraktor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Export Data Kontraktor"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function contractor()
    {
        $title        = 'KONTRAKTOR';
        $current      = Carbon::now()->format('YmdHis'); 

        $query = User::query();

        $query->where('id_role', 2);

        $data = $query->get();

        $result = [];

        if ($data) {
            $result = $data->map(function ($value) {
                return [
                    'company'       => $value->toKontraktor->company,
                    'leader'        => $value->toKontraktor->leader,
                    'npwp'          => $value->toKontraktor->npwp,
                    'telepon'       => $value->toKontraktor->telepon,
                    'email'         => $value->toKontraktor->email,
                    'address'       => $value->toKontraktor->address,
                    'postal_code'   => $value->toKontraktor->postal_code,
                    'website'       => $value->toKontraktor->website,
                    'initial'       => $value->toKontraktor->initial,
                    'capital'       => $value->toKontraktor->capital
                ];
            })->toArray();
        }

        $export = new ContractorExport($result, $title);

        ActivityLogHelper::log('report_export', 1, [
            'title' => $title,
        ]);

        return Excel::download($export, 'Contractor-'.$current.'.xlsx');
    }

    /**
     * @OA\GET(
     *     path="/export/contact",
     *     tags={"Operation - Report Export"},
     *     summary="Export Data Kontak",
     *     description="Export Data Kontak",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Export Data Kontak"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function contact()
    {
        $title        = 'KONTAK';
        $current      = Carbon::now()->format('YmdHis');  

        $query = Kontak::query();

        $query->with(['toKontrak']);

        if (isset($request->jenis)) {
            $query->where('jenis', $request->jenis);
        }

        if (isset($request->is_company)) {
            $query->where('is_company', $request->is_company);
        }

        $query->orderBy('name', 'asc');

        $data = $query->get();

        $result = [];

        if ($data) {
            $result = $data->map(function ($value) {
                return [
                    'name'            => $value->name,
                    'contract_number' => $value->toKontrak->no_kontrak ?? '-',
                    'email'           => $value->email,
                    'telepon'         => $value->phone,
                    'address'         => $value->address,
                    'type'            => $value->is_company ? 'Company' : 'Personal',
                    'postal_code'     => $value->postal_code,
                ];
            })->toArray();
        }

        ActivityLogHelper::log('report_export', 1, [
            'title' => $title,
        ]);

        return Excel::download(new ContactExport($result, $title), 'Contact-'.$current.'.xlsx');
    }
}
