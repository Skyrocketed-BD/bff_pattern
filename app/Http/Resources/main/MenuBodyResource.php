<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuBodyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result['id_menu_body']     = $this->id_menu_body;
        $result['id_menu_category'] = $this->id_menu_category;
        $result['name']             = $this->name;
        $result['icon']             = $this->icon;
        $result['url']              = $this->url;
        $result['is_enabled']       = $this->is_enabled;
        $result['position']         = $this->position;

        if ($this->toMenuChild->count() > 0) {
            $result['sub_menu'] = MenuChildResource::collection($this->toMenuChild);
        }

        return $result;
    }
}
