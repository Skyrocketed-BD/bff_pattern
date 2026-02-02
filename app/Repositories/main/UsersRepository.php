<?php

namespace App\Repositories\main;

use App\Http\Resources\main\UserResource;
use App\Models\main\User;

class UsersRepository
{
    public function getAll($id_kontraktor = null)
    {
        $users = User::query();

        if ($id_kontraktor != null) {
            $users->where('id_kontraktor', $id_kontraktor);

            $users->where('id_role', 5);
        }

        $data = $users->get();

        return UserResource::collection($data);
    }
}
