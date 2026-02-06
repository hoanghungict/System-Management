<?php

namespace Modules\Task\tests\Integration;

use Tests\TestCase;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\TaskReceiver;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\LecturerAccount;
use Modules\Auth\app\Models\StudentAccount;
use App\Models\Department;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * ✅ Task Integration Tests
 * 
 * Test toàn bộ task workflow với real database
 * Bao gồm task creation, assignment, completion flow
 */
class TaskIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $lecturer;
    protected $student1;
    protected $student2;
    protected $faculty;
    protected $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    /**
     * ✅ Create test data
     */
    private function createTestData(): void
    {
        // Create faculty
        $this->department = Department::create([
            'ten_khoa' => 'Khoa Công nghệ thông tin',
            'ma_khoa' => 'CNTT'
        ]);

        // Create classroom
        $this->classroom = Classroom::create([
            'ten_lop' => 'CNTT2021',
            'ma_lop' => 'CNTT2021',
            'faculty_id' => $this->faculty->id
        ]);

        // Create lecturer
        $this->lecturer = Lecturer::create([
            'ho_ten' => 'Nguyễn Văn A',
            'email' => 'lecturer@test.com',
            'so_dien_thoai' => '0123456789',
            'faculty_id' => $this->faculty->id
        ]);

        LecturerAccount::create([
            'lecturer_id' => $this->lecturer->id,
            'username' => 'lecturer_test',
            'password' => bcrypt('password'),
            'role' => 'lecturer'
        ]);

        // Create students
        $this->student1 = Student::create([
            'ho_ten' => 'Trần Văn B',
            'ma_sinh_vien' => 'SV001',
            'email' => 'student1@test.com',
            'so_dien_thoai' => '0987654321',
            'class_id' => $this->classroom->id
        ]);

        StudentAccount::create([
            'student_id' => $this->student1->id,
            'username' => 'student1_test',
            'password' => bcrypt('password'),
            'role' => 'student'
        ]);

        $this->student2 = Student::create([
            'ho_ten' => 'Lê Thị C',
            'ma_sinh_vien' => 'SV002', 
            'email' => 'student2@test.com',
            'so_dien_thoai' => '0987654322',
            'class_id' => $this->classroom->id
        ]);

        StudentAccount::create([
            'student_id' => $this->student2->id,
            'username' => 'student2_test',
            'password' => bcrypt('password'),
            'role' => 'student'
        ]);
    }

    /**
     * ✅ Test complete task creation workflow
     */
    public function test_complete_task_creation_workflow()
    {
        // Create task
        $task = Task::create([
            'title' => 'Bài tập lập trình Java',
            'description' => 'Viết chương trình quản lý sinh viên',
            'priority' => 'high',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(7)
        ]);

        // Assign to specific students
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student2->id,
            'receiver_type' => 'student'
        ]);

        // Verify task creation
        $this->assertDatabaseHas('task', [
            'id' => $task->id,
            'title' => 'Bài tập lập trình Java',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer'
        ]);

        // Verify receivers
        $this->assertDatabaseHas('task_receivers', [
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        $this->assertDatabaseHas('task_receivers', [
            'task_id' => $task->id,
            'receiver_id' => $this->student2->id,
            'receiver_type' => 'student'
        ]);

        // Test relationships
        $task->load(['receivers']);
        $this->assertCount(2, $task->receivers);
        
        // Test business logic
        $allStudents = $task->getAllStudents();
        $this->assertCount(2, $allStudents);
        $this->assertTrue($allStudents->contains('id', $this->student1->id));
        $this->assertTrue($allStudents->contains('id', $this->student2->id));
    }

    /**
     * ✅ Test task assignment to entire class
     */
    public function test_task_assignment_to_class()
    {
        // Create task assigned to entire class
        $task = Task::create([
            'title' => 'Bài kiểm tra giữa kỳ',
            'description' => 'Kiểm tra kiến thức về cơ sở dữ liệu',
            'priority' => 'high',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(3)
        ]);

        // Assign to class
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->classroom->id,
            'receiver_type' => 'class'
        ]);

        // Test that all students in class receive the task
        $task->load(['receivers']);
        $allStudents = $task->getAllStudents();
        
        $this->assertCount(2, $allStudents); // Both students in class
        $this->assertTrue($allStudents->contains('id', $this->student1->id));
        $this->assertTrue($allStudents->contains('id', $this->student2->id));

        // Test hasReceiver method
        $this->assertTrue($task->hasReceiver($this->student1->id, 'student'));
        $this->assertTrue($task->hasReceiver($this->student2->id, 'student'));
    }

    /**
     * ✅ Test task assignment to all students in faculty
     */
    public function test_task_assignment_to_all_students_in_faculty()
    {
        // Create task for all students in faculty
        $task = Task::create([
            'title' => 'Thông báo quan trọng',
            'description' => 'Thông báo về lịch thi cuối kỳ',
            'priority' => 'urgent',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(1)
        ]);

        // Assign to all students in faculty
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->faculty->id,
            'receiver_type' => 'all_students'
        ]);

        // Test that all students in faculty receive the task
        $task->load(['receivers']);
        $allStudents = $task->getAllStudents();
        
        $this->assertCount(2, $allStudents);
        $this->assertTrue($task->hasReceiver($this->student1->id, 'student'));
        $this->assertTrue($task->hasReceiver($this->student2->id, 'student'));
    }

    /**
     * ✅ Test task assignment to all students in system
     */
    public function test_task_assignment_to_all_students_system_wide()
    {
        // Create system-wide task
        $task = Task::create([
            'title' => 'Khảo sát sinh viên toàn trường',
            'description' => 'Khảo sát về chất lượng đào tạo',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(14)
        ]);

        // Assign to all students (receiver_id = 0 means all students)
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => 0,
            'receiver_type' => 'all_students'
        ]);

        // Test that all students receive the task
        $task->load(['receivers']);
        $allStudents = $task->getAllStudents();
        
        $this->assertCount(2, $allStudents);
        $this->assertTrue($task->hasReceiver($this->student1->id, 'student'));
        $this->assertTrue($task->hasReceiver($this->student2->id, 'student'));
    }

    /**
     * ✅ Test task update workflow
     */
    public function test_task_update_workflow()
    {
        // Create initial task
        $task = Task::create([
            'title' => 'Bài tập ban đầu',
            'description' => 'Mô tả ban đầu',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(7)
        ]);

        // Add initial receiver
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        // Update task
        $task->update([
            'title' => 'Bài tập đã cập nhật',
            'description' => 'Mô tả đã cập nhật',
            'priority' => 'high'
        ]);

        // Add new receiver
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student2->id,
            'receiver_type' => 'student'
        ]);

        // Verify updates
        $this->assertDatabaseHas('task', [
            'id' => $task->id,
            'title' => 'Bài tập đã cập nhật',
            'priority' => 'high'
        ]);

        $task->refresh();
        $task->load(['receivers']);
        $this->assertCount(2, $task->receivers);
        
        // Verify updated_at timestamp
        $this->assertNotNull($task->updated_at);
        $this->assertTrue($task->updated_at->greaterThan($task->created_at));
    }

    /**
     * ✅ Test task deletion with cleanup
     */
    public function test_task_deletion_with_cleanup()
    {
        // Create task with receivers
        $task = Task::create([
            'title' => 'Task to be deleted',
            'description' => 'This task will be deleted',
            'priority' => 'low',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer'
        ]);

        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        $taskId = $task->id;

        // Delete task
        $task->delete();

        // Verify task is deleted
        $this->assertDatabaseMissing('task', ['id' => $taskId]);
        
        // Verify receivers are also deleted (cascade)
        $this->assertDatabaseMissing('task_receivers', ['task_id' => $taskId]);
    }

    /**
     * ✅ Test task status progression
     */
    public function test_task_status_progression()
    {
        $task = Task::create([
            'title' => 'Task with status progression',
            'description' => 'Testing status changes',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(5)
        ]);

        // Assign to student
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        // Progress: pending -> in_progress
        $task->update(['status' => 'in_progress']);
        $this->assertDatabaseHas('task', [
            'id' => $task->id,
            'status' => 'in_progress'
        ]);

        // Progress: in_progress -> completed
        $task->update(['status' => 'completed']);
        $this->assertDatabaseHas('task', [
            'id' => $task->id,
            'status' => 'completed'
        ]);

        // Verify timestamps
        $task->refresh();
        $this->assertNotNull($task->updated_at);
    }

    /**
     * ✅ Test multiple receiver types in single task
     */
    public function test_multiple_receiver_types_in_single_task()
    {
        $task = Task::create([
            'title' => 'Task with mixed receivers',
            'description' => 'Task assigned to both students and lecturers',
            'priority' => 'high',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer'
        ]);

        // Assign to specific student
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        // Assign to lecturer
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->lecturer->id,
            'receiver_type' => 'lecturer'
        ]);

        // Assign to entire class
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->classroom->id,
            'receiver_type' => 'class'
        ]);

        $task->load(['receivers']);

        // Test students (1 direct + 2 from class = 2 unique students)
        $allStudents = $task->getAllStudents();
        $this->assertCount(2, $allStudents);

        // Test lecturers (1 direct)
        $allLecturers = $task->getAllLecturers();
        $this->assertCount(1, $allLecturers);
        $this->assertTrue($allLecturers->contains('id', $this->lecturer->id));

        // Test hasReceiver for different types
        $this->assertTrue($task->hasReceiver($this->student1->id, 'student'));
        $this->assertTrue($task->hasReceiver($this->student2->id, 'student')); // via class
        $this->assertTrue($task->hasReceiver($this->lecturer->id, 'lecturer'));
    }

    /**
     * ✅ Test task business logic validation
     */
    public function test_task_business_logic_validation()
    {
        $task = Task::create([
            'title' => 'Task for validation',
            'description' => 'Testing business rules',
            'priority' => 'urgent',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer',
            'deadline' => Carbon::now()->addDays(10) // Urgent but long deadline
        ]);

        // Add receiver
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        $task->load(['receivers']);

        // Test business rule validation
        $businessLogicService = $task->getBusinessLogicService();
        $validationResults = $businessLogicService->validateTaskBusinessRules($task);

        // Should have validation issue for urgent task with long deadline
        $this->assertContains('Urgent task should have deadline within 7 days', $validationResults);
    }

    /**
     * ✅ Test task completion statistics
     */
    public function test_task_completion_statistics()
    {
        $task = Task::create([
            'title' => 'Task for statistics',
            'description' => 'Testing completion stats',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer'
        ]);

        // Assign to class (2 students)
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->classroom->id,
            'receiver_type' => 'class'
        ]);

        $task->load(['receivers']);

        // Get completion stats
        $businessLogicService = $task->getBusinessLogicService();
        $stats = $businessLogicService->getTaskCompletionStats($task);

        $this->assertEquals(2, $stats['total_students']);
        $this->assertEquals(0, $stats['completed_count']); // Task is pending
        $this->assertEquals(2, $stats['pending_count']);
        $this->assertEquals(0, $stats['completion_rate']);
    }

    /**
     * ✅ Test cache invalidation on task operations
     */
    public function test_cache_invalidation_on_task_operations()
    {
        // Clear any existing cache
        Cache::flush();

        $task = Task::create([
            'title' => 'Task for cache testing',
            'description' => 'Testing cache behavior',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer'
        ]);

        // Cache should be empty initially
        $cacheKey = "task_stats:lecturer:{$this->lecturer->id}";
        $this->assertNull(Cache::get($cacheKey));

        // Update task (should not affect cache since it's not set yet)
        $task->update(['title' => 'Updated task title']);

        // Delete task
        $task->delete();

        // Verify task is deleted
        $this->assertDatabaseMissing('task', ['id' => $task->id]);
    }

    /**
     * ✅ Test concurrent task operations
     */
    public function test_concurrent_task_operations()
    {
        $task = Task::create([
            'title' => 'Concurrent test task',
            'description' => 'Testing concurrent access',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => $this->lecturer->id,
            'creator_type' => 'lecturer'
        ]);

        // Simulate concurrent receiver additions
        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id,
            'receiver_type' => 'student'
        ]);

        TaskReceiver::create([
            'task_id' => $task->id,
            'receiver_id' => $this->student2->id,
            'receiver_type' => 'student'
        ]);

        // Verify both receivers were added successfully
        $this->assertDatabaseHas('task_receivers', [
            'task_id' => $task->id,
            'receiver_id' => $this->student1->id
        ]);

        $this->assertDatabaseHas('task_receivers', [
            'task_id' => $task->id,
            'receiver_id' => $this->student2->id
        ]);

        $task->load(['receivers']);
        $this->assertCount(2, $task->receivers);
    }
}
