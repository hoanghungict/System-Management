<?php

namespace Modules\Auth\app\Http\Resources\AuthUserResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this),
            'token' => $this->token ?? null,
            'message' => 'Đăng nhập thành công'
        ];
    }
}

