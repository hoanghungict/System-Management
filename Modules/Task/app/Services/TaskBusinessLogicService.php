<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\TaskReceiver;
use Illuminate\Database\Eloquent\Collection;

/**
 * Task Business Logic Service
 * 
 * Service này chứa business logic phức tạp cho Task
 * Tuân thủ Clean Architecture: chỉ chứa business logic
 */
class TaskBusinessLogicService
{
    /**
     * Lấy tất cả students nhận task này
     */
    public function getAllStudentsForTask(Task $task): Collection
    {
        $students = collect();
        
        foreach ($task->receivers as $receiver) {
            switch ($receiver->receiver_type) {
                case 'student':
                    $student = \Modules\Auth\app\Models\Student::find($receiver->receiver_id);
                    if ($student) {
                        $students->push($student);
                    }
                    break;
                case 'class':
                    $classStudents = \Modules\Auth\app\Models\Student::where('class_id', $receiver->receiver_id)->get();
                    $students = $students->merge($classStudents);
                    break;
                case 'all_students':
                    $allStudents = \Modules\Auth\app\Models\Student::all();
                    $students = $students->merge($allStudents);
                    break;
            }
        }
        
        return $students->unique('id');
    }

    /**
     * Lấy tất cả lecturers nhận task này
     */
    public function getAllLecturersForTask(Task $task): Collection
    {
        $lecturers = collect();
        
        foreach ($task->receivers as $receiver) {
            switch ($receiver->receiver_type) {
                case 'lecturer':
                    $lecturer = \Modules\Auth\app\Models\Lecturer::find($receiver->receiver_id);
                    if ($lecturer) {
                        $lecturers->push($lecturer);
                    }
                    break;
                case 'all_lecturers':
                    $allLecturers = \Modules\Auth\app\Models\Lecturer::all();
                    $lecturers = $lecturers->merge($allLecturers);
                    break;
            }
        }
        
        return $lecturers->unique('id');
    }

    /**
     * Thêm receiver cho task
     */
    public function addReceiverToTask(Task $task, int $receiverId, string $receiverType): TaskReceiver
    {
        return TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $receiverId,
            'receiver_type' => $receiverType
        ]);
    }

    /**
     * Xóa receiver khỏi task
     */
    public function removeReceiverFromTask(Task $task, int $receiverId, string $receiverType): bool
    {
        return TaskReceiver::where('task_id', $task->id)
            ->where('receiver_id', $receiverId)
            ->where('receiver_type', $receiverType)
            ->delete() > 0;
    }

    /**
     * Kiểm tra xem một user có nhận task này không
     */
    public function isUserTaskReceiver(Task $task, int $userId, string $userType): bool
    {
        // Kiểm tra direct receiver
        $isDirectReceiver = $task->receivers()
            ->where('receiver_id', $userId)
            ->where('receiver_type', $userType)
            ->exists();

        if ($isDirectReceiver) {
            return true;
        }
        
        // Nếu user là student, kiểm tra thêm class và all_students
        if ($userType === 'student') {
            $student = \Modules\Auth\app\Models\Student::find($userId);
            if ($student) {
                $isClassReceiver = $task->receivers()
                    ->where('receiver_type', 'class')
                    ->where('receiver_id', $student->class_id)
                    ->exists();

                $isAllStudentsReceiver = $task->receivers()
                    ->where('receiver_type', 'all_students')
                    ->exists();

                return $isClassReceiver || $isAllStudentsReceiver;
            }
        }

        return false;
    }
}