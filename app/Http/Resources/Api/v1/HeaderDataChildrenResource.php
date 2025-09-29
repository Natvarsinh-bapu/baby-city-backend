<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeaderDataChildrenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->name,
            'categoryId' => $this->id,
        ];
    }
}
