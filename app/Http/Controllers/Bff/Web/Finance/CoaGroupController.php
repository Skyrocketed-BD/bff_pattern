<?php

namespace App\Http\Controllers\Bff\Web\Finance;

use App\Http\Controllers\Bff\BffController;
use Illuminate\Http\Request;

class CoaGroupController extends BffController
{
    public function index()
    {
        $response = $this->apiClientService->get('/coa/groups');

        return $this->handleApiResponse($response);
    }

    public function store(Request $request)
    {
        try {
            $response = $this->apiClientService->post('/coa/groups', $request->all());

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $response = $this->apiClientService->get('/coa/groups/' . $id);

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $response = $this->apiClientService->put('/coa/groups/' . $id, $request->all());

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClientService->delete('/coa/groups/' . $id);

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
