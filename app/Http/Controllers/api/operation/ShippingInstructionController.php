<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Events\KontraktorEventTriggered;
use App\Events\UserEventTriggered;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\ShippingInstructionRequest;
use App\Http\Resources\operation\ShippingInstructionResource;
use App\Models\operation\Kontraktor;
use App\Models\operation\ShippingInstruction;
use App\Models\operation\ShippingInstructionApprove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ShippingInstructionController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/shipping_instructions",
     *  summary="Get the list of shipping instructions",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Get(
     *  path="/shipping_instructions/{status}",
     *  summary="Get the list of shipping instructions",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\Parameter(
     *      name="status",
     *      in="path",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="string",
     *          enum={"0", "1", "2", "3", "4", "5"}
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request, $status = null)
    {
        $start_date     = start_date_month($request->start_date);
        $end_date       = end_date_month($request->end_date);
        $id_kontraktor  = $request->id_kontraktor;

        $query = ShippingInstruction::query();

        $query->with(['toSlot', 'toKontraktor', 'toPlanBarging.toInvoiceFob.toInvoiceBill.toTransaction', 'toShippingInstructionApprove']);

        if (isset($id_kontraktor)) {
            $query->whereKontraktor($id_kontraktor);
        }

        if ($status != null) {
            $query->where('status', $status);
        }

        $query->whereBetween('load_date_start', [$start_date, $end_date]);

        $query->orderBy('load_date_start', 'desc');

        $data = $query->get();

        return ApiResponseClass::sendResponse(ShippingInstructionResource::collection($data), 'Shipping Instruction Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/shipping_instructions",
     *  summary="Add a new shipping instruction",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_plan_barging",
     *                  type="string",
     *                  description="Id plan barging of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="consignee",
     *                  type="string",
     *                  description="Consignee of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="notify_party",
     *                  type="string",
     *                  description="Notify party of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="tug_boat",
     *                  type="string",
     *                  description="Tug boat of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="barge",
     *                  type="string",
     *                  description="Barge of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="gross_tonage",
     *                  type="string",
     *                  description="Gross tonage of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="loading_port",
     *                  type="string",
     *                  description="Loading port of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="unloading_port",
     *                  type="string",
     *                  description="Unloading port of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="load_date_start",
     *                  type="date",
     *                  description="Load date start of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="load_amount",
     *                  type="integer",
     *                  description="Load amount of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment of stok_eto (file upload)"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ShippingInstructionRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $attachment = add_file($request->attachment, 'shipping-instruction/');
            $si_number  = generateSINumber($this->id_kontraktor, $this->initial);

            $data = ShippingInstruction::create([
                'id_plan_barging'  => $request->id_plan_barging,
                'id_kontraktor'    => $this->id_kontraktor,
                'number_si'        => $si_number,
                'consignee'        => $request->consignee,
                'surveyor'         => $request->surveyor,
                'notify_party'     => $request->notify_party,
                'tug_boat'         => $request->tug_boat,
                'barge'            => $request->barge,
                'gross_tonage'     => $request->gross_tonage,
                'loading_port'     => $request->loading_port,
                'unloading_port'   => $request->unloading_port,
                'load_date_start'  => $request->load_date_start,
                'load_amount'      => $request->load_amount,
                'attachment'       => $attachment,
                'information'      => $request->information,
            ]);

            ActivityLogHelper::log('operation:shipping_instruction_create', 1, [
                'operation:shipping_instruction_number' => $data->number_si,
                'operation:contractor'                  => $data->toKontraktor->company,
            ]);

            DB::connection('operation')->commit();

            UserEventTriggered::trigger("si_request");

            return ApiResponseClass::sendResponse($data, 'Shipping Instruction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:shipping_instruction_create', 0, [ 'error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/shipping_instructions/detail/{id}",
     *  summary="Get the detail of shipping instruction",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\Parameter(
     *      name="id",
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
    public function show($id)
    {
        $data = ShippingInstruction::find($id);

        return ApiResponseClass::sendResponse(ShippingInstructionResource::make($data), 'Shipping Instruction Detail Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/shipping_instructions/{id}",
     *  summary="Update a shipping instruction",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="_method",
     *      in="query",
     *      description="HTTP Method",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          default="PUT"
     *      ),
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_plan_barging",
     *                  type="string",
     *                  description="Id plan barging of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="id_slot",
     *                  type="integer",
     *                  description="Id slot of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="consignee",
     *                  type="string",
     *                  description="Consignee of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="notify_party",
     *                  type="string",
     *                  description="Notify party of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="tug_boat",
     *                  type="string",
     *                  description="Tug boat of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="barge",
     *                  type="string",
     *                  description="Barge of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="gross_tonage",
     *                  type="string",
     *                  description="Gross tonage of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="loading_port",
     *                  type="string",
     *                  description="Loading port of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="unloading_port",
     *                  type="string",
     *                  description="Unloading port of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="load_date_start",
     *                  type="date",
     *                  description="Load date start of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="load_date_finish",
     *                  type="date",
     *                  description="Load date finish of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="load_amount",
     *                  type="integer",
     *                  description="Load amount of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="information",
     *                  type="string",
     *                  description="Information of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="mining_inspector",
     *                  type="string",
     *                  description="Mining inspector of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="color",
     *                  type="string",
     *                  description="Color of shipping instruction"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment of stok_eto (file upload)"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(ShippingInstructionRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = ShippingInstruction::find($id);

            $data->id_plan_barging  = $request->id_plan_barging;
            $data->id_slot          = $request->id_slot;
            $data->consignee        = $request->consignee;
            $data->surveyor         = $request->surveyor;
            $data->notify_party     = $request->notify_party;
            $data->tug_boat         = $request->tug_boat;
            $data->barge            = $request->barge;
            $data->gross_tonage     = $request->gross_tonage;
            $data->loading_port     = $request->loading_port;
            $data->unloading_port   = $request->unloading_port;
            $data->load_date_start  = $request->load_date_start;
            $data->load_date_finish = $request->load_date_finish;
            $data->load_amount      = $request->load_amount;
            $data->information      = $request->information;
            $data->mining_inspector = $request->mining_inspector;

            // untuk kontraktor
            if ($data->status === '1') {
                $data->status = '0';
            }

            // untuk teknisi
            if ($data->status === '3') {
                $data->status = '4';

                // KontraktorEventTriggered::trigger("new_si_slot", $data->id_kontraktor);
            }

            $data->save();

            $kontraktor = Kontraktor::find($data->id_kontraktor);

            $kontraktor->update([
                'color' => $request->color,
            ]);

            ActivityLogHelper::log('operation:shipping_instruction_update', 1, [
                'operation:shipping_instruction_number' => $data->number_si,
                'operation:contractor'                  => $data->toKontraktor->company,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse(ShippingInstructionResource::make($data), 'Shipping Instruction Pending Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:shipping_instruction_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/shipping_instructions/{id}",
     *  summary="Delete shipping instruction by id",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\Parameter(
     *      name="id",
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
    public function destroy($id)
    {
        try {
            $data = ShippingInstruction::with(['toPlanBarging.toInvoiceFob'])->find($id);

            if ($data->toPlanBarging->toInvoiceFob) {
                return Response::json(['success' => false, 'message' => 'A financial transaction already exists!'], 400);
            } else {
                $data->delete();

                ActivityLogHelper::log('operation:shipping_instruction_delete', 1, [
                    'operation:shipping_instruction_number' => $data->number_si,
                    'operation:contractor'                  => $data->toKontraktor->company,
                    'operation:consignee'                   => $data->consignee,
                    'operation:surveyor'                    => $data->surveyor,
                    'operation:notify_party'                => $data->notify_party,
                    'operation:tug_boat'                    => $data->tug_boat,
                    'operation:barge'                       => $data->barge,
                    'operation:gross_tonage'                => $data->gross_tonage,
                    'operation:loading_port'                => $data->loading_port,
                    'operation:unloading_port'              => $data->unloading_port,
                    'operation:loading_date_start'          => $data->load_date_start,
                    'operation:loading_date_end'            => $data->load_date_finish,
                    'operation:load_amount'                 => $data->load_amount,
                    'operation:information'                 => $data->information,
                    'operation:mining_inspector'            => $data->mining_inspector,
                ]);

                return ApiResponseClass::sendResponse(ShippingInstructionResource::make($data), 'Shipping Instruction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:shipping_instruction_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Put(
     *  path="/shipping_instructions/approve/{id}",
     *  summary="Approve a shipping instruction",
     *  tags={"Operation - Shipping Instruction"},
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
    public function approve(Request $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = ShippingInstruction::find($id);

            $data->update([
                'status'      => '2',
                'information' => $request->description
            ]);

            $check_shipping_instruction_approved = ShippingInstructionApprove::where('id_shipping_instruction', $id)->count();

            if ($check_shipping_instruction_approved !== 0) {
                ShippingInstructionApprove::where('id_shipping_instruction', $id)->delete();
            }

            ActivityLogHelper::log('operation:shipping_instruction_approved', 1, [
                'operation:shipping_instruction_number' => $data->number_si,
            ]);

            DB::connection('operation')->commit();

            UserEventTriggered::trigger("si_approve");

            return ApiResponseClass::sendResponse(ShippingInstructionResource::make($data), 'Shipping Instruction Approved Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:shipping_instruction_approved', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Put(
     *  path="/shipping_instructions/rejected/{id}",
     *  summary="Rejected a shipping instruction",
     *  tags={"Operation - Shipping Instruction"},
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
    public function rejected(Request $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = ShippingInstruction::find($id);

            $data->update([
                'status'        => '1',
                'reject_reason' => $request->reject_reason
            ]);

            ShippingInstructionApprove::where('id_shipping_instruction', $id)->delete();

            ActivityLogHelper::log('operation:shipping_instruction_rejected', 1, [
                'operation:shipping_instruction_number' => $data->number_si,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse(ShippingInstructionResource::make($data), 'Shipping Instruction Rejected Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:shipping_instruction_rejected', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/shipping_instructions/timeline",
     *  summary="Get the timeline of shipping instruction",
     *  tags={"Operation - Shipping Instruction"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function timeline(Request $request)
    {
        $si = ShippingInstruction::query();

        $si->with(['toSlot', 'toKontraktor']);

        if ($this->id_kontraktor != null) {
            $si->whereKontraktor($this->id_kontraktor);
        }

        $si->whereIn('status', ['4','5']);

        $data = $si->get();

        $items = ShippingInstructionResource::collection($data)->toArray($request);

        $response['items'] = $items;

        $slot = [];
        foreach ($data as $key => $value) {
            $slot[] = [
                'id_slot' => $value->toSlot->id_slot,
                'name'    => $value->toSlot->name
            ];
        }

        $slot = array_values(array_unique($slot, SORT_REGULAR));

        $response['slot'] = $slot;

        return ApiResponseClass::sendResponse($response, 'Shipping Instruction Retrieved Successfully');
    }
}
