<?php

namespace Modules\Task\app\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource class để chuyển đổi dữ liệu TaskFile cho API response
 * 
 * Class này chuyển đổi TaskFile model thành format JSON phù hợp cho API
 * Tuân thủ Clean Architecture: chỉ xử lý data transformation, không chứa business logic
 */
class TaskFileResource extends JsonResource
{
    /**
     * Chuyển đổi resource thành array để trả về JSON
     * 
     * @param Request $request Request hiện tại
     * @return array Array chứa dữ liệu đã được chuyển đổi
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_url' => $this->file_url,
            
            // Relationships
            'task' => new TaskResource($this->whenLoaded('task')),
        ];
    }
}
