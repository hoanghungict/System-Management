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
        // Ensure UTF-8 encoding for all string fields
        $title = $this->title ? mb_convert_encoding($this->title, 'UTF-8', 'auto') : null;
        $description = $this->description ? mb_convert_encoding($this->description, 'UTF-8', 'auto') : null;

        return [
            'id' => $this->id,
            'title' => $title,
            'description' => $description,
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null, // ✅ Thêm due_date
            'deadline' => $this->deadline ? $this->deadline->format('Y-m-d H:i:s') : null,
            'status' => $this->status,
            'priority' => $this->priority,
            'creator_id' => $this->creator_id,
            'creator_type' => $this->creator_type,
            'assigned_to' => $this->assigned_to, // ✅ Thêm assigned_to
            'assigned_to_id' => $this->assigned_to_id, // ✅ Thêm assigned_to_id
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null, // ✅ Thêm updated_at

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
