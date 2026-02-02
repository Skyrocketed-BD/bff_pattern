<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\OperationController;
use App\Models\operation\Cog;
use App\Models\operation\StokEfo;
use App\Models\operation\StokEto;
use App\Models\operation\StokInPit;
use Illuminate\Http\Request;

class StokGlobalController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/stok_globals",
     *  summary="Get the list of stok globals",
     *  tags={"Operation - Stok Global"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_kontraktor",
     *      in="query",
     *      description="Id kontraktor",
     *      required=false,
     *      @OA\Schema(
     *          type="integer",
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="grade",
     *      in="query",
     *      description="Grade",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="dome",
     *      in="query",
     *      description="Dome",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="storage",
     *      in="query",
     *      description="Storage",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $start_date  = start_date_month($request->start_date);
        $end_date    = end_date_month($request->end_date);

        $stok_in_pit = $this->_get_stok_in_pit($start_date, $end_date);
        $stok_eto    = $this->_get_stok_eto($start_date, $end_date);
        $stok_efo    = $this->_get_stok_efo($start_date, $end_date);
        $items       = array_merge($stok_in_pit, $stok_eto, $stok_efo);

        // filter id_kontraktor
        if ($request->id_kontraktor) {
            $items = array_filter($items, function ($value) use ($request) {
                return $value['id_kontraktor'] == $request->id_kontraktor;
            });
        }

        // filter grade
        if ($request->grade) {
            $items = array_filter($items, function ($value) use ($request) {
                return $value['type'] == $request->grade;
            });
        }

        // filter dome
        if ($request->dome) {
            $items = array_filter($items, function ($value) use ($request) {
                return $value['dome'] == $request->dome;
            });
        }

        // filter storage
        if ($request->storage) {
            $items = array_filter($items, function ($value) use ($request) {
                return $value['storage'] == $request->storage;
            });
        }

        $items = array_values($items);

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

            $count[$type][] = $value['tonage'];

            $ni[]     = $value['ni'];
            $fe[]     = $value['fe'];
            $co[]     = $value['co'];
            $sio2[]   = $value['sio2'];
            $mgo2[]   = $value['mgo2'];
            $simg[]   = ($value['sio2'] == 0 || $value['mgo2'] == 0) ? 0 : ($value['sio2'] / $value['mgo2']);
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

        return ApiResponseClass::sendResponse($response, 'Stok Global Retrieved Successfully');
    }

    // get all stok in pit
    private function _get_stok_in_pit($start_date, $end_date)
    {
        $qry = StokInPit::query();

        $qry->with(['toBlock', 'toPit', 'toDomInPit']);

        $qry->whereBetween('date', [$start_date, $end_date]);

        $data = $qry->get();

        $result = [];

        $key    = 0;

        // Load all Cog once to avoid N+1 query
        $cogs = Cog::all();

        foreach ($data as $row) {
            $simg = 0;
            if ($row->mgo2 > 0) {
                $simg = ($row->sio2 / $row->mgo2);
            }

            // Find matching Cog from pre-loaded collection
            $cog = $cogs->first(function ($c) use ($row) {
                return $c->max >= $row->ni && $c->min <= $row->ni;
            });

            $type  = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

            $result[] = [
                'key'           => 'pit' . $key++,
                'id_kontraktor' => $row->id_kontraktor,
                'dome'          => $row->toDomInPit->name,
                'gudang'        => 'Stock In Pit',
                'storage'       => 'in_pit',
                'date'          => $row->date,
                'type'          => $type,
                'ni'            => $row->ni,
                'fe'            => $row->fe,
                'co'            => $row->co,
                'sio2'          => $row->sio2,
                'mgo2'          => $row->mgo2,
                'tonage'        => $row->tonage,
                'ritasi'        => $row->ritasi,
                'simg'          => round($simg, 2),
            ];
        }

        return $result;
    }

    // get all stok eto
    private function _get_stok_eto($start_date, $end_date)
    {
        $qry = StokEto::query();

        $qry->with(['toStokEtoDetail', 'toDomEto']);

        $qry->whereBetween('date_in', [$start_date, $end_date]);

        $data = $qry->get();

        $result = [];

        $key    = 0;

        // Load all Cog once to avoid N+1 query
        $cogs = Cog::all();

        foreach ($data as $row) {
            $simg = 0;
            if ($row->mgo2 > 0) {
                $simg = ($row->sio2 / $row->mgo2);
            }

            // Find matching Cog from pre-loaded collection
            $cog = $cogs->first(function ($c) use ($row) {
                return $c->max >= $row->ni && $c->min <= $row->ni;
            });

            $type  = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

            $result[] = [
                'key'           => 'eto' . $key++,
                'id_kontraktor' => $row->id_kontraktor,
                'dome'          => $row->toDomEto->name,
                'gudang'        => 'Stockpile Eto',
                'storage'       => 'eto',
                'date'          => $row->date_in,
                'type'          => $type,
                'ni'            => $row->ni,
                'fe'            => $row->fe,
                'co'            => $row->co,
                'sio2'          => $row->sio2,
                'mgo2'          => $row->mgo2,
                'tonage'        => $row->tonage_after,
                'ritasi'        => $row->ritasi,
                'simg'          => round($simg, 2),
            ];
        }

        return $result;
    }

    // get all stok efo
    private function _get_stok_efo($start_date, $end_date)
    {
        $qry = StokEfo::query();

        $qry->with(['toStokEfoDetail', 'toDomEfo']);

        $qry->whereBetween('date_in', [$start_date, $end_date]);

        $data = $qry->get();

        $result = [];

        $key    = 0;

        // Load all Cog once to avoid N+1 query
        $cogs = Cog::all();

        foreach ($data as $row) {
            $simg = 0;
            if ($row->mgo2 > 0) {
                $simg = ($row->sio2 / $row->mgo2);
            }

            // Find matching Cog from pre-loaded collection
            $cog = $cogs->first(function ($c) use ($row) {
                return $c->max >= $row->ni && $c->min <= $row->ni;
            });

            $type  = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

            $result[] = [
                'key'           => 'efo' . $key++,
                'id_kontraktor' => $row->id_kontraktor,
                'dome'          => $row->toDomEfo->name,
                'gudang'        => 'Stockpile Efo',
                'storage'       => 'efo',
                'date'          => $row->date_in,
                'type'          => $type,
                'ni'            => $row->ni,
                'fe'            => $row->fe,
                'co'            => $row->co,
                'sio2'          => $row->sio2,
                'mgo2'          => $row->mgo2,
                'tonage'        => $row->tonage_after,
                'ritasi'        => $row->ritasi,
                'simg'          => round($simg, 2),
            ];
        }

        return $result;
    }
}
