## format membuat controller baru untuk Bff Controller

variabel :
- path => app/Http/Controllers/Bff/Web
- url => ambil dari instruksi
- NamaController => nama controller
- Location => lokasi controller

```
<?php

namespace App\Http\Controllers\Bff\Web\{Location};

use App\Http\Controllers\Bff\BffController;
use Illuminate\Http\Request;

class {NamaController} extends BffController
{
    public function index()
    {
        $response = $this->apiClientService->get({url});

        return $this->handleApiResponse($response);
    }

    public function store(Request $request)
    {
        try {
            $response = $this->apiClientService->post({url}, $request->all());

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $response = $this->apiClientService->get({url}' . $id);

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $response = $this->apiClientService->put({url}' . $id, $request->all());

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClientService->delete({url}' . $id);

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
```