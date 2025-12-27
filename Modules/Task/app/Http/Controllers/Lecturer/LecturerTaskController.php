<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Http\Requests\TaskRequest;
use Modules\Task\app\Http\Controllers\Lecturer\Actions\{
    ListTasksAction,
    ShowTaskAction,
    CreateTaskAction,
    UpdateTaskAction,
    DeleteTaskAction,
    UploadFilesAction,
    DeleteFileAction,
    DownloadFileAction,
    GetStatisticsAction,
    GetAssignedTasksAction,
    GetCreatedTasksAction,
    GetSubmissionsAction,
    GradeSubmissionAction,
    DuplicateTaskAction
};
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Slim Lecturer Task Controller - Delegates to Action classes
 * 
 * Following Single Action Controller pattern for better organization
 */
class LecturerTaskController extends Controller
{
    public function __construct(
        private readonly ListTasksAction $listTasksAction,
        private readonly ShowTaskAction $showTaskAction,
        private readonly CreateTaskAction $createTaskAction,
        private readonly UpdateTaskAction $updateTaskAction,
        private readonly DeleteTaskAction $deleteTaskAction,
        private readonly UploadFilesAction $uploadFilesAction,
        private readonly DeleteFileAction $deleteFileAction,
        private readonly DownloadFileAction $downloadFileAction,
        private readonly GetStatisticsAction $getStatisticsAction,
        private readonly GetAssignedTasksAction $getAssignedTasksAction,
        private readonly GetCreatedTasksAction $getCreatedTasksAction,
        private readonly GetSubmissionsAction $getSubmissionsAction,
        private readonly GradeSubmissionAction $gradeSubmissionAction,
        private readonly DuplicateTaskAction $duplicateTaskAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ($this->listTasksAction)($request);
    }

    public function getCreatedTasks(Request $request): JsonResponse
    {
        return ($this->getCreatedTasksAction)($request);
    }

    public function getAssignedTasks(Request $request): JsonResponse
    {
        return ($this->getAssignedTasksAction)($request);
    }

    public function store(TaskRequest $request): JsonResponse
    {
        return ($this->createTaskAction)($request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return ($this->showTaskAction)($id);
    }

    public function update(TaskRequest $request, int $id): JsonResponse
    {
        return ($this->updateTaskAction)($request, $id);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return ($this->deleteTaskAction)($request, $id);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        return ($this->duplicateTaskAction)($request, $id);
    }

    public function uploadFile(Request $request, int $task): JsonResponse
    {
        return ($this->uploadFilesAction)($request, $task);
    }

    public function uploadFiles(Request $request, int $task): JsonResponse
    {
        return ($this->uploadFilesAction)($request, $task);
    }

    public function deleteFile(Request $request, int $task, int $file): JsonResponse
    {
        return ($this->deleteFileAction)($request, $task, $file);
    }

    public function downloadFile(Request $request, int $task, int $file): StreamedResponse|JsonResponse
    {
        return ($this->downloadFileAction)($request, $task, $file);
    }

    public function getLecturerStatistics(Request $request): JsonResponse
    {
        return ($this->getStatisticsAction)($request);
    }

    public function getTaskSubmissions(Request $request, int $task): JsonResponse
    {
        return ($this->getSubmissionsAction)($request, $task);
    }

    public function gradeSubmission(Request $request, int $task, int $submission): JsonResponse
    {
        return ($this->gradeSubmissionAction)($request, $task, $submission);
    }
}
