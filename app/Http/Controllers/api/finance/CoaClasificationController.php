<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\finance\CoaClasification;

class CoaClasificationController extends Controller
{
    /**
     * @OA\Get(
     *  path="/coa/clasifications",
     *  summary="Get the list of coa clasifications",
     *  tags={"Finance - Coa Clasification"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = CoaClasification::orderBy('id_coa_clasification', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Coa Clasification Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/coa/clasifications/{slug}",
     *  summary="Get the list of coa clasifications",
     *  tags={"Finance - Coa Clasification"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  @OA\Parameter(
     *      name="slug",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filter($slug)
    {
        $data = CoaClasification::with(['toCoaBody.toCoa'])->where('slug', $slug)->first();

        $result = [];

        foreach ($data->toCoaBody as $key => $value) {
            if ($value->toCoa) {
                foreach ($value->toCoa as $key => $value2) {
                    $result[] = [
                        'id_coa'      => $value2->id_coa,
                        'id_coa_body' => $value->id_coa_body,
                        'name'        => $value2->name,
                        'coa'         => $value2->coa
                    ];
                }
            }
        }

        return ApiResponseClass::sendResponse($result, 'Coa Clasification Retrieved Successfully');
    }
}
