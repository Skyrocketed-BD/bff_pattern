<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleAccessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_role_access'     => $this->id_role_access,
            'id_role'            => $this->id_role,
            'id_menu_body'       => $this->id_menu_body,
            'name_menu_body'     => $this->toMenuBody->name,
            'id_menu_category'   => $this->toMenuBody->id_menu_category,
            'name_menu_category' => $this->toMenuBody->toMenuCategory->name,
            'action'             => $this->action
        ];
    }
}
