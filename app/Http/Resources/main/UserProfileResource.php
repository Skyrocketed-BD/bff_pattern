<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_users'       => $this->id_users,
            'name'           => $this->name,
            'username'       => $this->username,
            'email'          => $this->email,
            'gender'         => $this->gender,
            'birth_date'     => $this->birth_date,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'avatar'         => $this->avatar,
        ];
    }
}
