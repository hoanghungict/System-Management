<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerProfileRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Lecturer Profile Use Case
 */
class LecturerProfileUseCase
{
    protected $lecturerProfileRepository;

    public function __construct(LecturerProfileRepository $lecturerProfileRepository)
    {
        $this->lecturerProfileRepository = $lecturerProfileRepository;
    }

    public function getProfile($lecturerId)
    {
        try {
            $profile = $this->lecturerProfileRepository->getProfile($lecturerId);
            return $profile;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer profile: ' . $e->getMessage(), 500);
        }
    }

    public function updateProfile($lecturerId, $data)
    {
        try {
            $profile = $this->lecturerProfileRepository->updateProfile($lecturerId, $data);
            return $profile;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to update lecturer profile: ' . $e->getMessage(), 500);
        }
    }
}
