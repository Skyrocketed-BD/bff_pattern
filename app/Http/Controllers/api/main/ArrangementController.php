<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\main\Arrangement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArrangementController extends Controller
{
    /**
     * @OA\Get(
     *  path="/arrangement",
     *  summary="Get the list of arrangement",
     *  tags={"Main - Arrangement"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $query = Arrangement::all();

        $data = [];
        foreach ($query as $key => $value) {
            if ($value->type == 'integer') {
                $data[$value->key] = (int) $value->value;
            } else {
                $data[$value->key] = $value->value;
            }
        }

        return ApiResponseClass::sendResponse($data, 'Arrangement Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/arrangement",
     *  summary="Update arrangement",
     *  tags={"Main - Arrangement"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="key",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="Key Arrangement"
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="array",
     *                  @OA\Items(type="integer", example=10000),
     *                  description="Value Arrangement"
     *              ),
     *              required={"key", "value"},
     *              example={
     *                  "key": {"nama", "alamat", "kode_pos"},
     *                  "value": {"value", "value", "value"}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $arrRequest = $request->payload;

            foreach ($arrRequest as $value) {
                $data = Arrangement::updateOrCreate(
                    [
                        'key' => $value->key
                    ],
                    [
                        'value' => $value->value
                    ]
                );
            }

            ActivityLogHelper::log('admin:reference_update', 1, []);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($data, 'Reference Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:reference_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/arrangement/{key}",
     *  summary="Get arrangement by key",
     *  tags={"Main - Arrangement"},
     *  @OA\Parameter(
     *      name="key",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          example="D"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($key)
    {
        $data = Arrangement::where('key', $key)->first();

        return ApiResponseClass::sendResponse($data, 'Arrangement Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/arrangement/image",
     *  summary="Store logo",
     *  tags={"Main - Arrangement"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="picture",
     *                  type="file",
     *                  description="File Image"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store_image(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $picture = add_file($request->picture, 'arrangement/');

            $data = Arrangement::updateOrCreate(
                [
                    'key' => 'logo'
                ],
                [
                    'value' => $picture
                ]
            );

            ActivityLogHelper::log('admin:reference_image_store', 1, []);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($data, 'Image Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:reference_image_store', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/arrangement/image",
     *  summary="Delete logo",
     *  tags={"Main - Arrangement"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function delete_image()
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $get = Arrangement::where('key', 'logo')->first();

            $picture = $get->value;

            del_file($picture, 'arrangement/');

            $get->update([
                'value' => null
            ]);

            ActivityLogHelper::log('admin:reference_image_delete', 1, []);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($picture, 'Image Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:reference_image_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
