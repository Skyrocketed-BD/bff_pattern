<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionResource extends JsonResource
{
    protected static $idResolve   = null;
    protected static $nameResolve = null;

    public static function using(string|callable $idResolve, string|callable $nameResolve): string
    {
        static::$idResolve = $idResolve;
        static::$nameResolve = $nameResolve;

        return __CLASS__;
    }

    public function toArray(Request $request): array
    {
        $id = is_callable(static::$idResolve) ? call_user_func(static::$idResolve, $this) : $this->{static::$idResolve};

        $name = is_callable(static::$nameResolve) ? call_user_func(static::$nameResolve, $this) : $this->{static::$nameResolve};

        return [
            'id'       => $id,
            'name'     => $name,
        ];
    }
}
