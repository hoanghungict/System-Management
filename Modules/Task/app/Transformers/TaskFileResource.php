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
        // Determine base URL for download endpoint based on route context
        $taskId = $this->task_id;
        $fileId = $this->id;
        
        // Get base URL from request or config
        $baseUrl = $request->getSchemeAndHttpHost() ?: config('app.url', 'http://localhost');
        
        // Build download URLs for different user roles
        // FE có thể dùng bất kỳ endpoint nào tùy theo user role
        $downloadUrls = [
            'common' => "{$baseUrl}/api/v1/tasks/{$taskId}/files/{$fileId}/download",
            'lecturer' => "{$baseUrl}/api/v1/lecturer-tasks/{$taskId}/files/{$fileId}/download",
            'admin' => "{$baseUrl}/api/v1/admin-tasks/{$taskId}/files/{$fileId}/download",
        ];
        
        // Default download URL (common endpoint works for everyone)
        $downloadUrl = $downloadUrls['lecturer']; // Default to lecturer endpoint
        
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'file_name' => $this->name ?? $this->file_name, // Tên file gốc từ column 'name'
            'file_url' => $this->file_url,                   // URL để preview/view (giữ nguyên)
            'download_url' => $downloadUrl,                  // URL để download với tên gốc
            'download_urls' => $downloadUrls,                // All available download endpoints
            'size' => $this->size,                           // Kích thước file
            'path' => $this->path,                           // Đường dẫn trong storage
            'created_at' => $this->created_at?->toDateTimeString(),
            
            // Relationships
            'task' => new TaskResource($this->whenLoaded('task')),
        ];
    }
}
