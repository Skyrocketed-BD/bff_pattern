<?php

namespace App\Repositories\operation;

use App\Http\Resources\operation\StokEtoResource;
use App\Models\operation\Cog;
use App\Models\operation\StokEto;

class StokEtoRepository
{
    public function getAll($request, $id_kontraktor)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $stok_eto = StokEto::query();

        $stok_eto->with(['toStokEtoDetail', 'toDomEto']);

        $stok_eto->whereBetween('date_in', [$start_date, $end_date]);

        // filter id_kontraktor
        if ($id_kontraktor != null) {
            $stok_eto->whereKontraktor($id_kontraktor);
        }

        // filter id_kontraktor
        if ($request->id_kontraktor) {
            $stok_eto->whereKontraktor($request->id_kontraktor);
        }

        $stok_eto->latest();

        $data = $stok_eto->get();

        $items = StokEtoResource::collection($data)->toArray($request);

        // filter grade
        if ($request->grade) {
            $items = array_filter($items, function ($value) use ($request) {
                return $value['type'] == $request->grade;
            });

            $items = array_values($items);
        }

        $response['items'] = $items;

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

            $count[$type][] = $value['tonage_after'];

            $ni[]     = $value['ni'];
            $fe[]     = $value['fe'];
            $co[]     = $value['co'];
            $sio2[]   = $value['sio2'];
            $mgo2[]   = $value['mgo2'];
            $simg[]   = ($value['sio2'] == 0 || $value['mgo2'] == 0) ? 0 : ($value['sio2'] / $value['mgo2']);
            $tonage[] = $value['tonage_after'];
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

        return $response;
    }
}
