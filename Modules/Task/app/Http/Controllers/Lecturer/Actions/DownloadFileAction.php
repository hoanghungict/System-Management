<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\Request;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Models\TaskFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Action: Download a file from task
 */
class DownloadFileAction extends BaseLecturerAction
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    public function __invoke(Request $request, int $taskId, int $fileId): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $taskFile = TaskFile::where('id', $fileId)
                ->where('task_id', $taskId)
                ->first();

            if (!$taskFile) {
                return $this->notFoundResponse('File');
            }

            $path = $taskFile->path;
            
            if (!Storage::exists($path)) {
                return $this->notFoundResponse('File on disk');
            }

            $originalFilename = $taskFile->name ?? basename($path);
            $mimeType = Storage::mimeType($path);

            return Storage::download($path, $originalFilename, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $originalFilename . '"'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to download file', $e);
        }
    }
}
