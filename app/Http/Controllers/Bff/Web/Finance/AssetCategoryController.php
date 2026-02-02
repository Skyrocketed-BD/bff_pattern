<?php

namespace App\Http\Controllers\Bff\Web\Finance;

use App\Http\Controllers\Bff\BffController;
use Illuminate\Http\Request;

class AssetCategoryController extends BffController
{
    public function index()
    {
        $response = $this->apiClientService->get('/asset/category');

        return $this->handleApiResponse($response);
    }

    public function store(Request $request)
    {
        try {
            $response = $this->apiClientService->post('/asset/category', $request->all());

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $response = $this->apiClientService->get('/asset/category/' . $id);

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $response = $this->apiClientService->put('/asset/category/' . $id, $request->all());

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClientService->delete('/asset/category/' . $id);

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
