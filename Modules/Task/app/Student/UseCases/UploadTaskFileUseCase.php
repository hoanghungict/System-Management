<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Upload Task File Use Case
 */
class UploadTaskFileUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    public function execute($taskId, $file, $studentId)
    {
        try {
            $uploadedFile = $this->studentTaskRepository->uploadTaskFile($taskId, $file, $studentId);
            return $uploadedFile;
        } catch (\Exception $e) {
            throw StudentTaskException::fileUploadFailed($file->getClientOriginalName());
        }
    }
}
