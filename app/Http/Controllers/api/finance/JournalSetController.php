<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\JournalSetRequest;
use App\Http\Resources\finance\JournalSetResource;
use App\Models\finance\JournalSet;
use Illuminate\Support\Facades\DB;

class JournalSetController extends Controller
{
    /**
     * @OA\Get(
     *  path="/journal-sets/{id_journal}",
     *  summary="Get the list of journal sets",
     *  tags={"Finance - Journal Set"},
     *  @OA\Parameter(
     *      name="id_journal",
     *      description="Journal ID",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer",
     *          example=1
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index($id_journal)
    {
        $data = JournalSet::with(['toTaxRate', 'toCoa.toCoaBody.toCoaClasification'])->whereIdJournal($id_journal)->orderBy('serial_number', 'asc')->get();

        return ApiResponseClass::sendResponse(JournalSetResource::collection($data), 'Journal Set Retrieved Successfully');
    }

    /** 
     * @OA\Get(
     *  path="/journal-sets/show/{id_journal_set}",
     *  summary="Get the detail of journal set",
     *  tags={"Finance - Journal Set"},
     *  @OA\Parameter(
     *      name="id_journal_set",
     *      description="Journal Set ID",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id_journal_set)
    {
        $data = JournalSet::find($id_journal_set);

        return ApiResponseClass::sendResponse($data, 'Journal Set Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/journal-sets/update/{id_journal_set}",
     *  summary="Update a journal set",
     *  tags={"Finance - Journal Set"},
     *  @OA\Parameter(
     *      name="id_journal_set",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="COA ID"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="D or K"
     *              ),
     *              example={
     *                  "id_journal": 1,
     *                  "id_coa": 1,
     *                  "type": "D",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(JournalSetRequest $request, $id_journal_set)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = JournalSet::find($id_journal_set);

            $data->update([
                'id_journal' => $request->id_journal,
                'id_coa'     => $request->id_coa,
                'type'       => $request->type,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Journal Set Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/journal-sets/destroy/{id_journal_set}",
     *  summary="Delete a journal set",
     *  tags={"Finance - Journal Set"},
     *  @OA\Parameter(
     *      name="id_journal_set",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id_journal_set)
    {
        try {
            $data = JournalSet::find($id_journal_set);

            $data->delete();

            return ApiResponseClass::sendResponse($data, 'Journal Set Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
