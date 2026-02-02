<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\main\UserActivityLog;
use Illuminate\Http\Request;

class UserActivityLogController extends Controller
{
    /**
     * @OA\Get(
     *  path="/user_activity_logs",
     *  summary="Get the list of user activity logs",
     *  tags={"Main - User Activity Logs"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request, $type = null)
    {
        $user = auth()->user();

        $query = UserActivityLog::query();

        if ($user->toRole->id_role !== 1) {
            $query->where('id_users', $user->id_users);
        }

        if ($type !== 'all') {
            $query->limit(10);
        }

        $perPage = $request->input('per_page', 10);

        $query->latest();

        $data = $query->paginate($perPage);

        return ApiResponseClass::sendResponse($data, 'Log Activity Retrieved Successfully');
    }
}
