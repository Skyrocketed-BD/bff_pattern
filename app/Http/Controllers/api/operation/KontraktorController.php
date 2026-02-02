<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Helpers\PushyAPI;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\KontraktorRequest;
use App\Http\Resources\operation\KontraktorResource;
use App\Models\main\User;
use App\Models\operation\Cog;
use App\Models\operation\Kontraktor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KontraktorController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/kontraktors",
     *  summary="Get the list of kontraktors",
     *  tags={"Operation - Kontraktor"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $kontraktor = User::query();

        // $kontraktor->with([
        //     'toKontraktor'
        //     => function ($query) {
        //         if ($this->id_kontraktor != null) {
        //             $query->where('id_kontraktor', $this->id_kontraktor);
        //         }
        //     }
        // ]);

        $kontraktor->where('id_role', 2);

        $data = $kontraktor->get();

        return ApiResponseClass::sendResponse(KontraktorResource::collection($data), 'Kontraktor Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/kontraktors",
     *  summary="Add a new kontraktor",
     *  tags={"Operation - Kontraktor"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="company",
     *                  type="string",
     *                  description="Company of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="leader",
     *                  type="string",
     *                  description="Leader of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="npwp",
     *                  type="string",
     *                  description="NPWP of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="telepon",
     *                  type="string",
     *                  description="Telepon of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *                  description="Address of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="capital",
     *                  type="string",
     *                  description="Capital of kontraktor"
     *              ),
     *              required={"name", "company", "email", "leader", "npwp", "telepon", "address", "capital"},
     *              example={
     *                  "name": "Kontraktor Satu",
     *                  "company": "PT. Kontraktor Satu",
     *                  "email": "testing@example.com",
     *                  "leader": "testing",
     *                  "npwp": "1234567890123456",
     *                  "telepon": "123412341234",
     *                  "address": "jl. apa saja",
     *                  "postal_code": "1234",
     *                  "website": "testing.com",
     *                  "capital": "nasional"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(KontraktorRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        DB::connection('mysql')->beginTransaction();
        try {
            $initial  = generateUniqueInitials('operation', $request->company, 'kontraktor', 'initial');
            $username = $initial . '-' . Str::password(8, true, true, false, false);

            $cogs = [
                [
                    'type' => 'Low Grade',
                    'min'  => 1.1,
                    'max'  => 1.69,
                ],
                [
                    'type' => 'Medium Grade',
                    'min'  => 1.7,
                    'max'  => 1.89,
                ],
                [
                    'type' => 'High Grade',
                    'min'  => 1.9,
                    'max'  => 2.8,
                ],
            ];

            $kontraktor = new Kontraktor();
            $kontraktor->setConnection('operation');
            $kontraktor->company     = $request->company;
            $kontraktor->leader      = $request->leader;
            $kontraktor->npwp        = $request->npwp;
            $kontraktor->telepon     = $request->telepon;
            $kontraktor->address     = $request->address;
            $kontraktor->postal_code = $request->postal_code;
            $kontraktor->email       = $request->email;
            $kontraktor->website     = $request->website;
            $kontraktor->capital     = $request->capital;
            $kontraktor->initial     = $initial;
            $kontraktor->color       = '#000000'; // default color
            $kontraktor->save();

            $user = new User();
            $user->setConnection('mysql');
            $user->id_kontraktor = $kontraktor->id_kontraktor;
            $user->id_role       = 2; // static id_role kontraktor
            $user->name          = $request->name;
            $user->username      = $username;
            $user->email         = $request->email;
            $user->password      = Hash::make($username);
            $user->save();

            foreach ($cogs as $key => $value) {
                $cog = new Cog();
                $cog->setConnection('operation');
                $cog->id_kontraktor = $kontraktor->id_kontraktor;
                $cog->type          = $value['type'];
                $cog->min           = $value['min'];
                $cog->max           = $value['max'];
                $cog->save();
            }

            DB::connection('operation')->commit();
            DB::connection('mysql')->commit();

            ActivityLogHelper::log('admin:contractor_create', 1, ['name' => $request->name]);

            return ApiResponseClass::sendResponse($kontraktor, 'Contractor Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:contractor_create', 0, ['error' => $e]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/kontraktors/{id}",
     *  summary="Get a single kontraktor",
     *  tags={"Operation - Kontraktor"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = Kontraktor::find($id);

        return ApiResponseClass::sendResponse(KontraktorResource::make($data), 'Kontraktor Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/kontraktors/{id}",
     *  summary="Update a kontraktor",
     *  tags={"Operation - Kontraktor"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="company",
     *                  type="string",
     *                  description="Company of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="leader",
     *                  type="string",
     *                  description="Leader of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="npwp",
     *                  type="string",
     *                  description="NPWP of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="telepon",
     *                  type="string",
     *                  description="Telepon of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *                  description="Address of kontraktor"
     *              ),
     *              @OA\Property(
     *                  property="capital",
     *                  type="string",
     *                  description="Capital of kontraktor"
     *              ),
     *              required={"name", "company", "email", "leader", "npwp", "telepon", "address", "capital"},
     *              example={
     *                  "name": "Kontraktor Satu",
     *                  "company": "PT Kontraktor Satu",
     *                  "email": "testing@example.com",
     *                  "leader": "testing",
     *                  "npwp": "1234567890123456",
     *                  "telepon": "123412341234",
     *                  "address": "jl. apa saja",
     *                  "postal_code": "1234",
     *                  "website": "testing.com",
     *                  "capital": "nasional"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(KontraktorRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        DB::connection('mysql')->beginTransaction();
        try {
            $kontraktor = Kontraktor::find($id);

            $initial  = generateUniqueInitials('operation', $request->company, 'kontraktor', 'initial');

            $kontraktor->setConnection('operation');
            $kontraktor->company     = $request->company;
            $kontraktor->leader      = $request->leader;
            $kontraktor->npwp        = $request->npwp;
            $kontraktor->telepon     = $request->telepon;
            $kontraktor->address     = $request->address;
            $kontraktor->postal_code = $request->postal_code;
            $kontraktor->website     = $request->website;
            $kontraktor->capital     = $request->capital;
            $kontraktor->initial     = $initial;
            $kontraktor->save();

            $user = User::where('id_kontraktor', $kontraktor->id_kontraktor)->where('id_role', 2)->first();

            if ($user) {
                $user->setConnection('mysql');
                $user->name  = $request->name;
                $user->email = $request->email;
                $user->save();
            }

            DB::connection('operation')->commit();
            DB::connection('mysql')->commit();

            // UserEventTriggered::trigger("notifikasi_dicoba");

            ActivityLogHelper::log('admin:contractor_update', 1, ['name' => $request->name]);

            return ApiResponseClass::sendResponse($kontraktor, 'Kontraktor Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:contractor_update', 0, ['error' => $e]);

            return ApiResponseClass::rollback($e);
        }
    }


    public function updateColor(Request $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $kontraktor = Kontraktor::find($id);

            $kontraktor->update([
                'color'     => $request->color,
            ]);

            DB::connection('operation')->commit();

            ActivityLogHelper::log('admin:contractor_update', 1, ['color' => $request->color]);

            return ApiResponseClass::sendResponse($kontraktor, 'Kontraktor Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:contractor_update', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/kontraktors/{id}",
     *  summary="Delete a kontraktor",
     *  tags={"Operation - Kontraktor"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $kontraktor = Kontraktor::find($id);

            ActivityLogHelper::log('admin:contractor_delete', 1, ['name' => $kontraktor->name]);

            $kontraktor->delete();

            $user = User::find($kontraktor->id_users);
            $user->delete();

            return ApiResponseClass::sendResponse($kontraktor, 'Contractor Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:contractor_delete', 0, ['error' => $e]);

            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
