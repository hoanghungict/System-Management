<?php

namespace Modules\Task\app\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource class để transform TaskReceiver data cho API response
 * 
 * Class này chuyển đổi TaskReceiver model thành format JSON phù hợp cho API
 */
class TaskReceiverResource extends JsonResource
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
            'task_id' => $this->task_id,
            'receiver_id' => $this->receiver_id,
            'receiver_type' => $this->receiver_type,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            
            // Relationships (nếu được load)
            'student' => $this->when($this->receiver_type === 'student' && $this->relationLoaded('student'), function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'email' => $this->student->email,
                ];
            }),
            'lecturer' => $this->when($this->receiver_type === 'lecturer' && $this->relationLoaded('lecturer'), function () {
                return [
                    'id' => $this->lecturer->id,
                    'name' => $this->lecturer->name,
                    'email' => $this->lecturer->email,
                ];
            }),
            'classroom' => $this->when($this->receiver_type === 'class' && $this->relationLoaded('classroom'), function () {
                return [
                    'id' => $this->classroom->id,
                    'name' => $this->classroom->name,
                ];
            }),
        ];
    }
}
