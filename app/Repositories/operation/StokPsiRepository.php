<?php

namespace App\Repositories\operation;

use App\Http\Resources\operation\StokPsiResource;
use App\Models\operation\Cog;
use App\Models\operation\StokPsi;

class StokPsiRepository
{
    public function getAll($request, $id_kontraktor)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $stok_psi = StokPsi::query();

        $stok_psi->with([
            'toDomEto',
            'toDomEfo',
            'toPlanBargingDetailEto',
            'toPlanBargingDetailEfo'
        ]);

        $stok_psi->whereBetween('date', [$start_date, $end_date]);

        // filter id_kontraktor
        if ($id_kontraktor != null) {
            $stok_psi->whereKontraktor($id_kontraktor);
        }

        // filter id_kontraktor
        if ($request->id_kontraktor) {
            $stok_psi->whereKontraktor($request->id_kontraktor);
        }

        $stok_psi->orderBy('date', 'desc');

        $data = $stok_psi->get();

        $items = StokPsiResource::collection($data)->toArray($request);

        // apa bila tonage != 0
        $items = array_filter($items, function ($value) {
            return $value['tonage'] != 0;
        });

        // filter grade
        if ($request->grade) {
            $items = array_filter($items, function ($value) use ($request) {
                return $value['type'] == $request->grade;
            });
        }

        $items = array_values($items);

        $response['items'] = $items;

        $count  = [];
        $tonage = [];
        $ni     = [];
        $fe     = [];
        $co     = [];
        $sio2   = [];
        $mgo2   = [];
        $simg   = [];
        $mc     = [];
        $ritasi = [];

        foreach ($items as $key => $value) {
            $type = strtolower(str_replace(' Grade', '', $value['type']));

            $count[$type][] = $value['tonage'];

            $tonage[] = $value['tonage'];
            $ni[]     = $value['ni'];
            $fe[]     = $value['fe'];
            $co[]     = $value['co'];
            $sio2[]   = $value['sio2'];
            $mgo2[]   = $value['mgo2'];
            $simg[]   = ($value['sio2'] == 0 || $value['mgo2'] == 0) ? 0 : ($value['sio2'] / $value['mgo2']);
            $mc[]     = $value['mc'];
            $ritasi[] = $value['ritasi'];
        }

        $sum_simg   = array_sum($simg);
        $sum_tonage = array_sum($tonage);

        if ($sum_tonage > 0) {
            $response['total'] = [
                'tonage' => array_sum($tonage),
                'ni'     => round((sumProductArray($ni, $tonage) / $sum_tonage), 2),
                'fe'     => round((sumProductArray($fe, $tonage) / $sum_tonage), 2),
                'co'     => round((sumProductArray($co, $tonage) / $sum_tonage), 2),
                'sio2'   => round((sumProductArray($sio2, $tonage) / $sum_tonage), 2),
                'mgo2'   => round((sumProductArray($mgo2, $tonage) / $sum_tonage), 2),
                'simg'   => round((sumProductArray($simg, $tonage) / $sum_tonage), 2),
                'mc'     => round((sumProductArray($mc, $tonage) / $sum_tonage), 2),
                'ritasi' => array_sum($ritasi),
            ];
        } else {
            $response['total'] = [
                'tonage' => 0,
                'ni'     => 0,
                'fe'     => 0,
                'co'     => 0,
                'sio2'   => 0,
                'mgo2'   => 0,
                'simg'   => 0,
                'mc'     => 0,
                'ritasi' => 0
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
