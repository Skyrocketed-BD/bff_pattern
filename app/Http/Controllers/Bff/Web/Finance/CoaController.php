<?php

namespace App\Http\Controllers\Bff\Web\Finance;

use App\Http\Controllers\Bff\BffController;

class CoaController extends BffController
{
    public function index()
    {
        $response = $this->apiClientService->get('/coas');

        return $this->handleApiResponse($response);
    }

    public function show($id)
    {
        $response = $this->apiClientService->get("/coas/" . $id);

        return $this->handleApiResponse($response);
    }
}
