<?php

namespace App\Http\Resources\Api\v1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'email' => $this->email ?? '',
            'mobile' => $this->mobile ?? '',
            'token' => $this->token ?? '',
            'profile_pic_url' => $this->profile_pic_url ?? '',
            'is_vegetarian' => $this->is_vegetarian ? true : false,
            'registered_at' => $this->created_at->format('Y-m-d H:i:s'),
            'orders' => $this->orders->count(),
        ];
    }
}
