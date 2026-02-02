<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_menu_category' => $this->id_menu_category,
            'id_menu_module'   => $this->id_menu_module,
            'name'             => $this->name,
        ];
    }
}
