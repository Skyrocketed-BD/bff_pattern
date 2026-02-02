<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\StokPsiRequest;
use App\Models\operation\Kontraktor;
use App\Models\operation\StokEfo;
use App\Models\operation\StokEto;
use App\Models\operation\StokPsi;
use App\Repositories\operation\StokPsiRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokPsiController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/stok_psis",
     *  summary="Get the list of stok psis",
     *  tags={"Operation - Stok Psi"},
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
     *      description="Id Kontraktor",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="grade",
     *      in="query",
     *      description="Grade",
     *      required=false,
     *      @OA\Schema(
     *          type="string"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request, StokPsiRepository $stok_psi_repository)
    {
        $data = $stok_psi_repository->getAll($request, $this->id_kontraktor);

        return ApiResponseClass::sendResponse($data, 'Stok Psi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_psis",
     *  summary="Add a new stok psis",
     *  tags={"Operation - Stok Psi"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment of stok_psi"
     *              ),
     *              @OA\Property(
     *                  property="id_stok_eto[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="id_stok_efo[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_ni[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_ni[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_fe[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_fe[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_co[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_co[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_sio2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_sio2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_mgo2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_mgo2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_tonage[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_tonage[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_ritasi[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_ritasi[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_mc[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_mc[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }} 
     * )
     */
    public function store(StokPsiRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $attachment = add_file($request->attachment, 'stok_psi/');

            $stok_psi = [];

            // untuk input stok  eto
            if (isset($request->id_stok_eto)) {
                $id_stok_eto = $request->id_stok_eto;
                $eto_ni      = $request->eto_ni;
                $eto_fe      = $request->eto_fe;
                $eto_co      = $request->eto_co;
                $eto_sio2    = $request->eto_sio2;
                $eto_mgo2    = $request->eto_mgo2;
                $eto_tonage  = $request->eto_tonage;
                $eto_ritasi  = $request->eto_ritasi;
                $eto_mc      = $request->eto_mc;

                foreach ($id_stok_eto as $key => $value) {
                    $stok_eto = StokEto::whereIdStokEto($value)->first();

                    $stok_eto->update([
                        'date_out' => $request->date,
                    ]);

                    $stok_psi[] = [
                        'id_kontraktor' => $this->id_kontraktor,
                        'id_stok_eto'   => $value,
                        'id_stok_efo'   => null,
                        'id_dom_eto'    => $stok_eto->id_dom_eto,
                        'id_dom_efo'    => null,
                        'date'          => $request->date,
                        'attachment'    => $attachment,
                        'type'          => 'eto',
                        'ni'            => $eto_ni[$key],
                        'fe'            => $eto_fe[$key],
                        'co'            => $eto_co[$key],
                        'sio2'          => $eto_sio2[$key],
                        'mgo2'          => $eto_mgo2[$key],
                        'tonage'        => $eto_tonage[$key],
                        'ritasi'        => $eto_ritasi[$key],
                        'mc'            => $eto_mc[$key],
                    ];
                }

                StokEto::whereIn('id_stok_eto', $request->id_stok_eto)->delete();
            }

            // untuk input stok efo
            if (isset($request->id_stok_efo)) {
                $id_stok_efo = $request->id_stok_efo;
                $efo_ni      = $request->efo_ni;
                $efo_fe      = $request->efo_fe;
                $efo_co      = $request->efo_co;
                $efo_sio2    = $request->efo_sio2;
                $efo_mgo2    = $request->efo_mgo2;
                $efo_tonage  = $request->efo_tonage;
                $efo_ritasi  = $request->efo_ritasi;
                $efo_mc      = $request->efo_mc;

                foreach ($id_stok_efo as $key => $value) {
                    $stok_efo = StokEfo::whereIdStokEfo($value)->first();

                    $stok_efo->update([
                        'date_out' => $request->date,
                    ]);

                    $stok_psi[] = [
                        'id_kontraktor' => $this->id_kontraktor,
                        'id_stok_eto'   => null,
                        'id_stok_efo'   => $value,
                        'id_dom_eto'    => null,
                        'id_dom_efo'    => $stok_efo->id_dom_efo,
                        'date'          => $request->date,
                        'attachment'    => $attachment,
                        'type'          => 'efo',
                        'ni'            => $efo_ni[$key],
                        'fe'            => $efo_fe[$key],
                        'co'            => $efo_co[$key],
                        'sio2'          => $efo_sio2[$key],
                        'mgo2'          => $efo_mgo2[$key],
                        'tonage'        => $efo_tonage[$key],
                        'ritasi'        => $efo_ritasi[$key],
                        'mc'            => $efo_mc[$key],
                    ];
                }

                StokEfo::whereIn('id_stok_efo', $request->id_stok_efo)->delete();
            }

            foreach ($stok_psi as $key => $value) {
                $psi_stok                = new StokPsi();
                $psi_stok->id_kontraktor = $value['id_kontraktor'];
                $psi_stok->id_stok_eto   = $value['id_stok_eto'];
                $psi_stok->id_stok_efo   = $value['id_stok_efo'];
                $psi_stok->id_dom_eto    = $value['id_dom_eto'];
                $psi_stok->id_dom_efo    = $value['id_dom_efo'];
                $psi_stok->date          = $value['date'];
                $psi_stok->attachment    = $value['attachment'];
                $psi_stok->type          = $value['type'];
                $psi_stok->ni            = $value['ni'];
                $psi_stok->fe            = $value['fe'];
                $psi_stok->co            = $value['co'];
                $psi_stok->sio2          = $value['sio2'];
                $psi_stok->mgo2          = $value['mgo2'];
                $psi_stok->tonage        = $value['tonage'];
                $psi_stok->ritasi        = $value['ritasi'];
                $psi_stok->mc            = $value['mc'];
                $psi_stok->save();
            }

            ActivityLogHelper::log('operation:stock_psi_create', 1, [
                'operation:contractor' => Kontraktor::find($this->id_kontraktor)->company,
                'date'                 => $request->date,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_psi, 'Stok Psi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_psi_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
