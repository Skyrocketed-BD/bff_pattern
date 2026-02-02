<?php

namespace App\Http\Controllers\Api\Finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Resources\finance\CoaResource;
use App\Models\finance\Coa;

class CoaController extends Controller
{
    public function index()
    {
        $data = Coa::with(['toTaxCoa', 'toCoaBody.toCoaClasification'])->orderBy('id_coa', 'asc')->get();

        return ApiResponseClass::sendResponse(CoaResource::collection($data), 'Coa Retrieved Successfully');
    }

    public function show($id)
    {
        $data = Coa::find($id);

        return ApiResponseClass::sendResponse(CoaResource::make($data), 'Coa Retrieved Successfully');
    }
}
