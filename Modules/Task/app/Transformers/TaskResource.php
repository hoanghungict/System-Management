<?php

namespace Modules\Task\app\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource class để transform Task data cho API response
 * 
 * Class này chuyển đổi Task model thành format JSON phù hợp cho API
 * Tuân thủ Clean Architecture: chỉ xử lý data transformation, không chứa business logic
 */
class TaskResource extends JsonResource
{
    /**
     * Chuyển đổi resource thành array để trả về JSON
     * 
     * @param Request $request Request hiện tại
     * @return array Array chứa dữ liệu đã được transform
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline ? $this->deadline->format('Y-m-d H:i:s') : null,
            'status' => $this->status,
            'priority' => $this->priority,
            'creator_id' => $this->creator_id,
            'creator_type' => $this->creator_type,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            
            // Relationships
            'receivers' => TaskReceiverResource::collection($this->whenLoaded('receivers')),
            'files' => TaskFileResource::collection($this->whenLoaded('files')),
            
            // Computed attributes
            'receivers_count' => $this->whenLoaded('receivers', function () {
                return $this->receivers->count();
            }),
            'files_count' => $this->whenLoaded('files', function () {
                return $this->files->count();
            }),
        ];
    }
}
