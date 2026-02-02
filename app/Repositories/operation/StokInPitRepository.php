<?php

namespace App\Repositories\operation;

use App\Http\Resources\operation\StokInPitResource;
use App\Models\operation\Cog;
use App\Models\operation\StokInPit;

class StokInPitRepository
{
    public function getAll($request, $id_kontraktor)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $perPage = $request->per_page ?? 5;

        $stok_in_pit = StokInPit::query();

        $stok_in_pit->with(['toBlock', 'toPit', 'toDomInPit']);

        $stok_in_pit->whereBetween('date', [$start_date, $end_date]);

        // filter id_kontraktor
        if ($id_kontraktor != null) {
            $stok_in_pit->whereKontraktor($id_kontraktor);
        }

        // filter id_kontraktor
        if ($request->id_kontraktor) {
            $stok_in_pit->whereKontraktor($request->id_kontraktor);
        }

        // filter id_pit
        if ($request->id_pit) {
            $stok_in_pit->whereIdPit($request->id_pit);
        }

        // filter id_block
        if ($request->id_block) {
            $id_block = explode(',', $request->id_block);

            $stok_in_pit->whereIn('id_block', $id_block);
        }

        // filter id_dom
        if ($request->id_dom) {
            $id_dom = explode(',', $request->id_dom);

            $stok_in_pit->whereIn('id_dom_in_pit', $id_dom);
        }

        $stok_in_pit->orderBy('id_stok_in_pit', 'desc');

        $data = $stok_in_pit->paginate($perPage);

        $items = StokInPitResource::collection($data)->toArray($request); // pagination tetap utuh

        $response['items'] = $items;

        // perhitungan seperti sebelumnya (tidak diubah)
        $count  = [];
        $ni     = [];
        $fe     = [];
        $co     = [];
        $sio2   = [];
        $mgo2   = [];
        $simg   = [];
        $tonage = [];
        $ritasi = [];

        foreach ($items as $key => $value) {
            $type = strtolower(str_replace(' Grade', '', $value['type']));

            $count[$type][] = $value['tonage'];

            $ni[]     = $value['ni'];
            $fe[]     = $value['fe'];
            $co[]     = $value['co'];
            $sio2[]   = $value['sio2'];
            $mgo2[]   = $value['mgo2'];
            $simg[]   = ($value['mgo2'] != 0) ? ($value['sio2'] / $value['mgo2']) : 0;
            $tonage[] = $value['tonage'];
            $ritasi[] = $value['ritasi'];
        }

        $sum_simg   = array_sum($simg);
        $sum_tonage = array_sum($tonage);

        if ($sum_tonage > 0) {
            $response['total'] = [
                'ni'     => round((sumProductArray($ni, $tonage) / $sum_tonage), 2),
                'fe'     => round((sumProductArray($fe, $tonage) / $sum_tonage), 2),
                'co'     => round((sumProductArray($co, $tonage) / $sum_tonage), 2),
                'sio2'   => round((sumProductArray($sio2, $tonage) / $sum_tonage), 2),
                'mgo2'   => round((sumProductArray($mgo2, $tonage) / $sum_tonage), 2),
                'simg'   => round((sumProductArray($simg, $tonage) / $sum_tonage), 2),
                'tonage' => array_sum($tonage),
                'ritasi' => array_sum($ritasi),
            ];
        } else {
            $response['total'] = [
                'ni'     => 0,
                'fe'     => 0,
                'co'     => 0,
                'sio2'   => 0,
                'mgo2'   => 0,
                'simg'   => 0,
                'tonage' => 0,
                'ritasi' => 0,
            ];
        }

        $cogs = Cog::selectRaw('type')->get();
        $types = ['Waste'];

        foreach ($cogs as $key => $cog) {
            $types[] = $cog->type;
        }

        $waste = [];
        $total = [];

        foreach ($types as $key => $value) {
            $type  = strtolower(str_replace(' Grade', '', $value));
            $response['total_' . $type] = array_sum($count[$type] ?? []);

            if ($type == 'waste') {
                $waste[] = array_sum($count[$type] ?? []);
            } else {
                $total[] = array_sum($count[$type] ?? []);
            }
        }

        $sum_waste = array_sum($waste);
        $sum_total = array_sum($total);

        $response['total_sr']    = $sum_total == 0 ? 0 : round(($sum_waste / $sum_total), 2);
        $response['total_simag'] = $response['total']['simg'];

        $response['pagination'] = [
            'current_page'  => $data->currentPage(),
            'per_page'      => $data->perPage(),
            'last_page'     => $data->lastPage(),
            'total'         => $data->total(),
            'next_page_url' => $data->nextPageUrl(),
            'prev_page_url' => $data->previousPageUrl(),
            'from'          => $data->firstItem(),
            'to'            => $data->lastItem(),
        ];

        return $response;
    }
}
