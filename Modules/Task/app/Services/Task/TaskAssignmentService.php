<?php

namespace Modules\Task\app\Services\Task;

use Modules\Task\app\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý logic gán task cho receivers
 */
class TaskAssignmentService
{
    /**
     * Thêm receivers cho task
     */
    public function addReceiversToTask(Task $task, array $receivers): void
    {
        foreach ($receivers as $receiver) {
            $receiverType = $receiver['receiver_type'];
            $receiverId = $receiver['receiver_id'];
            
            if ($receiverType === 'classes') {
                $this->assignTaskToClassStudents($task, $receiverId);
            } elseif ($receiverType === 'department') {
                $this->assignTaskToDepartmentStudents($task, $receiverId);
            } elseif ($receiverType === 'all_students') {
                $this->assignTaskToAllStudents($task);
            } elseif ($receiverType === 'all_lecturers') {
                $this->assignTaskToAllLecturers($task);
            } else {
                $task->addReceiver($receiverId, $receiverType);
            }
        }
    }

    /**
     * Gán task cho tất cả sinh viên trong lớp
     */
    public function assignTaskToClassStudents(Task $task, int $classId): void
    {
        $students = DB::table('student')
            ->where('class_id', $classId)
            ->select('id')
            ->get();

        Log::info('Assigning task to class students', [
            'task_id' => $task->id,
            'class_id' => $classId,
            'students_count' => $students->count()
        ]);

        foreach ($students as $student) {
            $task->addReceiver($student->id, 'student');
        }
        $task->addReceiver($classId, 'classes');
    }

    /**
     * Gán task cho tất cả sinh viên trong khoa
     */
    public function assignTaskToDepartmentStudents(Task $task, int $departmentId): void
    {
        $students = DB::table('student')
            ->join('class', 'student.class_id', '=', 'class.id')
            ->where('class.department_id', $departmentId)
            ->select('student.id')
            ->get();

        Log::info('Assigning task to department students', [
            'task_id' => $task->id,
            'department_id' => $departmentId,
            'students_count' => $students->count()
        ]);

        foreach ($students as $student) {
            $task->addReceiver($student->id, 'student');
        }
        $task->addReceiver($departmentId, 'department');
    }

    /**
     * Gán task cho tất cả sinh viên hệ thống
     */
    public function assignTaskToAllStudents(Task $task): void
    {
        $students = DB::table('student')->select('id')->get();

        Log::info('Assigning task to all students', [
            'task_id' => $task->id,
            'students_count' => $students->count()
        ]);

        foreach ($students as $student) {
            $task->addReceiver($student->id, 'student');
        }
        $task->addReceiver(0, 'all_students');
    }

    /**
     * Gán task cho tất cả giảng viên hệ thống
     */
    public function assignTaskToAllLecturers(Task $task): void
    {
        $lecturers = DB::table('lecturer')->select('id')->get();

        Log::info('Assigning task to all lecturers', [
            'task_id' => $task->id,
            'lecturers_count' => $lecturers->count()
        ]);

        foreach ($lecturers as $lecturer) {
            $task->addReceiver($lecturer->id, 'lecturer');
        }
        $task->addReceiver(0, 'all_lecturers');
    }

    /**
     * Cập nhật receivers cho task
     */
    public function updateReceiversForTask(Task $task, array $receivers): void
    {
        DB::transaction(function () use ($task, $receivers) {
            $oldReceivers = $task->receivers()->get(['receiver_id', 'receiver_type'])->toArray();
            $deletedCount = $task->receivers()->delete();
            $this->addReceiversToTask($task, $receivers);
            
            Log::info('Task receivers updated', [
                'task_id' => $task->id,
                'old_receivers_count' => $deletedCount,
                'new_receivers_count' => count($receivers)
            ]);
        });
    }

    /**
     * Gán task cho receiver đơn lẻ
     */
    public function assignTaskToReceiver(Task $task, int $receiverId, string $receiverType): Task
    {
        $task->addReceiver($receiverId, $receiverType);
        Log::info('Task assigned to receiver', [
            'task_id' => $task->id,
            'receiver_id' => $receiverId,
            'receiver_type' => $receiverType
        ]);
        return $task;
    }

    /**
     * Thu hồi task (xóa tất cả receivers)
     */
    public function revokeTask(Task $task): bool
    {
        $task->receivers()->delete();
        return true;
    }
}
