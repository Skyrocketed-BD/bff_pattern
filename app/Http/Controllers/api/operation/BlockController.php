<?php

namespace App\Http\Controllers\api\operation;

use App\Models\operation\Block;
use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\BlockRequest;
use App\Http\Resources\operation\BlockResource;

class BlockController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/blocks",
     *  summary="Get the list of blocks",
     *  tags={"Operation - Block"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $blocks = Block::query();

        if ($this->id_kontraktor != null) {
            $blocks->whereKontraktor($this->id_kontraktor);

            $blocks->orWhereNull('id_kontraktor');
        }

        $data = $blocks->get();

        return ApiResponseClass::sendResponse(BlockResource::collection($data), 'Block Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/blocks",
     *  summary="Add a new block",
     *  tags={"Operation - Block"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of block"
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of block"
     *              ),
     *              required={"name", "file"},
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(BlockRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $file = add_file($request->file, 'block/');

            $block                = new Block();
            $block->id_kontraktor = $this->id_kontraktor;
            $block->name          = $request->name;
            $block->file          = $file;
            $block->save();

            ActivityLogHelper::log('operation:block_create', 1, [
                'name'          => $request->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($block, 'Block Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:block_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/blocks/{id}",
     *  summary="Get a specific block",
     *  tags={"Operation - Block"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $block = Block::find($id);

        return ApiResponseClass::sendResponse($block, 'Block Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/blocks/{id}",
     *  summary="Update a block",
     *  tags={"Operation - Block"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of block"
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of block"
     *              ),
     *              required={"name", "file"},
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(BlockRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $block = Block::find($id);

            if ($request->hasFile('file')) {
                $file = upd_file($request->file, $block->file, 'block/');
            } else {
                $file = $block->file;
            }

            $block->update([
                'name' => $request->name,
                'file' => $file,
            ]);

            ActivityLogHelper::log('operation:block_update', 1, [
                'name'           => $request->name,
            ]);
            
            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($block, 'Block Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:block_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/blocks/{id}",
     *  summary="Delete a block",
     *  tags={"Operation - Block"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = Block::find($id);

            del_file($data->file, 'block/');

            $data->delete();

            ActivityLogHelper::log('operation:block_delete', 1, [
                'name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Block Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:block_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/blocks/maps/{id_kontraktor}",
     *  summary="Get a specific block",
     *  tags={"Operation - Block"},
     *  @OA\Parameter(
     *      name="id_kontraktor",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function maps($id_kontraktor)
    {
        $blocks = Block::whereIdKontraktor($id_kontraktor)->get();

        $data = [];

        foreach ($blocks as $row) {
            $data[] = asset_upload('file/block/' . $row->file);
        }

        return ApiResponseClass::sendResponse($data, 'Block Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/blocks/list",
     *  summary="Get the list of blocks",
     *  tags={"Operation - Block"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function list()
    {
        $blocks = Block::query();

        if ($this->id_kontraktor != null) {
            $blocks->whereKontraktor($this->id_kontraktor);
        }

        $data = $blocks->get();

        return ApiResponseClass::sendResponse(BlockResource::collection($data), 'Block Retrieved Successfully');
    }
}
