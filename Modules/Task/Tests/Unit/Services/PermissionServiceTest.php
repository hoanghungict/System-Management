<?php

namespace Modules\Task\tests\Unit\Services;

use Tests\TestCase;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Constants\TaskPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * ✅ PermissionService Unit Tests
 * 
 * Test coverage cho tất cả permission logic
 */
class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;
    protected $mockTaskRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTaskRepository = Mockery::mock(TaskRepositoryInterface::class);
        $this->permissionService = new PermissionService($this->mockTaskRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * ✅ Test admin role check
     */
    public function test_is_admin()
    {
        // Admin user
        $adminContext = (object) [
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];

        $this->assertTrue($this->permissionService->isAdmin($adminContext));

        // Non-admin user
        $lecturerContext = (object) [
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $this->assertFalse($this->permissionService->isAdmin($lecturerContext));
    }

    /**
     * ✅ Test lecturer role check
     */
    public function test_is_lecturer()
    {
        $lecturerContext = (object) [
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $this->assertTrue($this->permissionService->isLecturer($lecturerContext));

        $studentContext = (object) [
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];

        $this->assertFalse($this->permissionService->isLecturer($studentContext));
    }

    /**
     * ✅ Test student role check
     */
    public function test_is_student()
    {
        $studentContext = (object) [
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];

        $this->assertTrue($this->permissionService->isStudent($studentContext));

        $lecturerContext = (object) [
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $this->assertFalse($this->permissionService->isStudent($lecturerContext));
    }

    /**
     * ✅ Test can create tasks
     */
    public function test_can_create_tasks()
    {
        // Admin can create
        $adminContext = (object) [
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];
        $this->assertTrue($this->permissionService->canCreateTasks($adminContext));

        // Lecturer can create
        $lecturerContext = (object) [
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];
        $this->assertTrue($this->permissionService->canCreateTasks($lecturerContext));

        // Student cannot create
        $studentContext = (object) [
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];
        $this->assertFalse($this->permissionService->canCreateTasks($studentContext));
    }

    /**
     * ✅ Test can view task - admin
     */
    public function test_can_view_task_admin()
    {
        $adminContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];

        // Admin can view any task
        $this->assertTrue($this->permissionService->canViewTask($adminContext, 999));
    }

    /**
     * ✅ Test can view task - creator
     */
    public function test_can_view_task_creator()
    {
        $userContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $task = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER
        ]);

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($task);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertTrue($this->permissionService->canViewTask($userContext, 1));
    }

    /**
     * ✅ Test can view task - receiver
     */
    public function test_can_view_task_receiver()
    {
        $userContext = (object) [
            'id' => 2,
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];

        $task = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER
        ]);

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($task);

        $this->mockTaskRepository
            ->shouldReceive('isTaskReceiver')
            ->with($task, 2, TaskPermissions::ROLE_STUDENT)
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertTrue($this->permissionService->canViewTask($userContext, 1));
    }

    /**
     * ✅ Test can view task - unauthorized
     */
    public function test_can_view_task_unauthorized()
    {
        $userContext = (object) [
            'id' => 3,
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];

        $task = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER
        ]);

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($task);

        $this->mockTaskRepository
            ->shouldReceive('isTaskReceiver')
            ->with($task, 3, TaskPermissions::ROLE_STUDENT)
            ->once()
            ->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertFalse($this->permissionService->canViewTask($userContext, 1));
    }

    /**
     * ✅ Test can edit task - admin
     */
    public function test_can_edit_task_admin()
    {
        $adminContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];

        $this->assertTrue($this->permissionService->canEditTask($adminContext, 999));
    }

    /**
     * ✅ Test can edit task - creator
     */
    public function test_can_edit_task_creator()
    {
        $userContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $task = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER,
            'status' => 'pending'
        ]);

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($task);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertTrue($this->permissionService->canEditTask($userContext, 1));
    }

    /**
     * ✅ Test can edit task - completed task restriction
     */
    public function test_can_edit_task_completed_restriction()
    {
        $userContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $completedTask = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER,
            'status' => 'completed'
        ]);

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($completedTask);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertFalse($this->permissionService->canEditTask($userContext, 1));
    }

    /**
     * ✅ Test can delete task - admin
     */
    public function test_can_delete_task_admin()
    {
        $adminContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];

        $this->assertTrue($this->permissionService->canDeleteTask($adminContext, 999));
    }

    /**
     * ✅ Test can delete task - creator with restrictions
     */
    public function test_can_delete_task_creator_with_restrictions()
    {
        $userContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        // Task in progress - cannot delete
        $inProgressTask = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER,
            'status' => 'in_progress'
        ]);

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($inProgressTask);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertFalse($this->permissionService->canDeleteTask($userContext, 1));
    }

    /**
     * ✅ Test can manage users permission
     */
    public function test_can_manage_users()
    {
        // Admin can manage
        $adminContext = (object) [
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];
        $this->assertTrue($this->permissionService->canManageUsers($adminContext));

        // Lecturer cannot manage
        $lecturerContext = (object) [
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];
        $this->assertFalse($this->permissionService->canManageUsers($lecturerContext));

        // Student cannot manage
        $studentContext = (object) [
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];
        $this->assertFalse($this->permissionService->canManageUsers($studentContext));
    }

    /**
     * ✅ Test can generate reports permission
     */
    public function test_can_generate_reports()
    {
        // Admin can generate reports
        $adminContext = (object) [
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];
        $this->assertTrue($this->permissionService->canGenerateReports($adminContext));

        // Lecturer can generate reports
        $lecturerContext = (object) [
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];
        $this->assertTrue($this->permissionService->canGenerateReports($lecturerContext));

        // Student cannot generate reports
        $studentContext = (object) [
            'user_type' => TaskPermissions::ROLE_STUDENT,
            'role' => TaskPermissions::ROLE_STUDENT
        ];
        $this->assertFalse($this->permissionService->canGenerateReports($studentContext));
    }

    /**
     * ✅ Test permission caching
     */
    public function test_permission_caching()
    {
        $userContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_LECTURER,
            'role' => TaskPermissions::ROLE_LECTURER
        ];

        $task = new Task([
            'id' => 1,
            'creator_id' => 1,
            'creator_type' => TaskPermissions::ROLE_LECTURER
        ]);

        // First call - should hit repository
        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($task);

        // Mock cache remember
        Cache::shouldReceive('remember')
            ->with(Mockery::pattern('/view_task:lecturer:1:1/'), Mockery::any(), Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result1 = $this->permissionService->canViewTask($userContext, 1);

        // Second call should use cache (no additional repository call)
        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/view_task:lecturer:1:1/'))
            ->once()
            ->andReturn(true);

        $this->assertTrue($result1);
    }

    /**
     * ✅ Test clear permission cache
     */
    public function test_clear_permission_cache()
    {
        $task = new Task(['id' => 1]);

        Cache::shouldReceive('forget')
            ->with(Mockery::pattern('/task_permissions:\*:1/'))
            ->once();

        $this->permissionService->clearPermissionCache($task);
    }

    /**
     * ✅ Test task not found scenario
     */
    public function test_task_not_found()
    {
        $userContext = (object) [
            'id' => 1,
            'user_type' => TaskPermissions::ROLE_LECTURER
        ];

        $this->mockTaskRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->assertFalse($this->permissionService->canViewTask($userContext, 999));
    }

    /**
     * ✅ Test permission hierarchy
     */
    public function test_permission_hierarchy()
    {
        // Admin has highest permission
        $adminContext = (object) [
            'user_type' => TaskPermissions::ROLE_ADMIN,
            'role' => TaskPermissions::ROLE_ADMIN
        ];

        // Admin can do everything
        $this->assertTrue($this->permissionService->canCreateTasks($adminContext));
        $this->assertTrue($this->permissionService->canViewTask($adminContext, 1));
        $this->assertTrue($this->permissionService->canEditTask($adminContext, 1));
        $this->assertTrue($this->permissionService->canDeleteTask($adminContext, 1));
        $this->assertTrue($this->permissionService->canManageUsers($adminContext));
        $this->assertTrue($this->permissionService->canGenerateReports($adminContext));
    }
}
