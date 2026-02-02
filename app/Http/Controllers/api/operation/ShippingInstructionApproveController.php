<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Resources\operation\ShippingInstructionApproveResource;
use App\Models\operation\ShippingInstruction;
use App\Models\operation\ShippingInstructionApprove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ShippingInstructionApproveController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/shipping_instructions_approve",
     *  summary="Get the list of shipping instruction approves",
     *  tags={"Operation - Shipping Instruction Approve"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $query = ShippingInstructionApprove::query();

        $data = $query->get();

        return ApiResponseClass::sendResponse(ShippingInstructionApproveResource::collection($data), 'Shipping Instruction Approve List Retrieved Successfully');
    }
     
    /**
     * @OA\Post(
     *  path="/shipping_instructions_approve/{status}",
     *  summary="Add a new shipping instruction approve",
     *  tags={"Operation - Shipping Instruction Approve"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_shipping_instruction",
     *                  type="integer",
     *                  description="ID Shipping Instruction"
     *              ),
     *              @OA\Property(
     *                  property="id_users",
     *                  type="integer",
     *                  description="ID User"
     *              ),
     *              required={"id_shipping_instruction", "id_users"}
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request, $status)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $query = ShippingInstructionApprove::query();

            $query->where('id_shipping_instruction', $request->id_shipping_instruction);
            $query->where('id_users', $request->id_users);

            if ($query->exists()) {
                return Response::json([
                    'status'  => false,
                    'message' => 'Shipping Instruction Approve Already Exists'
                ], 400);
            }

            if ($status === 'approved') {
                $data = ShippingInstructionApprove::create([
                    'id_shipping_instruction' => $request->id_shipping_instruction,
                    'id_users'                => $request->id_users,
                    'date'                    => date('Y-m-d'),
                    'status'                  => $status
                ]);
            } else {
                $data = ShippingInstructionApprove::create([
                    'id_shipping_instruction' => $request->id_shipping_instruction,
                    'id_users'                => $request->id_users,
                    'date'                    => date('Y-m-d'),
                    'status'                  => $status,
                    'reject_reason'           => $request->reject_reason
                ]);
            }

            $check_approve = ShippingInstructionApprove::where('id_shipping_instruction', $request->id_shipping_instruction)->where('status', 'approved')->count();
            if ($check_approve === 3) {
                ShippingInstruction::where('id_shipping_instruction', $request->id_shipping_instruction)->update([
                    'status' => '3'
                ]);
            }

            ActivityLogHelper::log('operation:shipping_instruction_status', 1, [
                'operation:shipping_instruction_number' => ShippingInstruction::find($request->id_shipping_instruction)->number_si,
                'status'                                => $status,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Shipping Instruction Approve Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:shipping_instruction_status', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
