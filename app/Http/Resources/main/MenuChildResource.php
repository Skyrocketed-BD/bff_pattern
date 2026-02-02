<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuChildResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_menu_child' => $this->id_menu_child,
            'id_menu_body'  => $this->id_menu_body,
            'name'          => $this->name,
            'url'           => $this->url,
        ];
    }
}
