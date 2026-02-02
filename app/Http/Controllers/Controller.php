<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *    title="OpenAPI For Skyrocketed",
 *    version="1.0.0",
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST
 * )
 */

abstract class Controller
{
    public $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
}
