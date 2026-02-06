<?php

namespace Modules\Task\tests\Unit\Services;

use Tests\TestCase;
use Modules\Task\app\Services\TaskService;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\CacheService;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\CachedReportRepositoryInterface;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * ✅ TaskService Unit Tests
 * 
 * Comprehensive test coverage cho TaskService
 * Bao gồm success cases, error cases, và edge cases
 */
class TaskServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TaskService $taskService;
    protected $mockTaskRepository;
    protected $mockUserRepository;
    protected $mockReportRepository;
    protected $mockPermissionService;
    protected $mockCacheService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockTaskRepository = Mockery::mock(TaskRepositoryInterface::class);
        $this->mockUserRepository = Mockery::mock(CachedUserRepositoryInterface::class);
        $this->mockReportRepository = Mockery::mock(CachedReportRepositoryInterface::class);
        $this->mockPermissionService = Mockery::mock(PermissionService::class);
        $this->mockCacheService = Mockery::mock(CacheService::class);

        // Inject mocks into service
        $this->taskService = new TaskService(
            $this->mockTaskRepository,
            $this->mockUserRepository,
            $this->mockReportRepository,
            $this->mockPermissionService,
            $this->mockCacheService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * ✅ Test task creation success case
     */
    public function test_create_task_success()
    {
        // Arrange
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'pending',
            'creator_id' => 1,
            'creator_type' => 'lecturer',
            'deadline' => now()->addDays(7)->toDateTimeString(),
            'receivers' => [
                ['receiver_id' => 1, 'receiver_type' => 'student'],
                ['receiver_id' => 2, 'receiver_type' => 'student']
            ]
        ];

        $userContext = (object) [
            'id' => 1,
            'user_type' => 'lecturer',
            'role' => 'lecturer'
        ];

        $expectedTask = new Task($taskData);
        $expectedTask->id = 1;

        // Mock expectations
        $this->mockPermissionService
            ->shouldReceive('canCreateTasks')
            ->with($userContext)
            ->once()
            ->andReturn(true);

        $this->mockTaskRepository
            ->shouldReceive('create')
            ->with(Mockery::subset(['title' => 'Test Task']))
            ->once()
            ->andReturn($expectedTask);

        $expectedTask->shouldReceive('load')->with('receivers')->once();
        $expectedTask->shouldReceive('addReceiver')->twice();

        $this->mockCacheService
            ->shouldReceive('forgetMultiple')
            ->once()
            ->andReturn(true);

        // Act
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $result = $this->taskService->createTask($taskData, $userContext);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Test Task', $result->title);
    }

    /**
     * ✅ Test task creation with permission denied
     */
    public function test_create_task_permission_denied()
    {
        // Arrange
        $taskData = ['title' => 'Test Task'];
        $userContext = (object) [
            'id' => 1,
            'user_type' => 'student',
            'role' => 'student'
        ];

        $this->mockPermissionService
            ->shouldReceive('canCreateTasks')
            ->with($userContext)
            ->once()
            ->andReturn(false);

        // Act & Assert
        $this->expectException(TaskException::class);
        $this->expectExceptionMessage('Unauthorized to create_task task');

        $this->taskService->createTask($taskData, $userContext);
    }

    /**
     * ✅ Test task creation validation failure
     */
    public function test_create_task_validation_failure()
    {
        // Arrange
        $invalidTaskData = [
            'title' => '', // Empty title should fail
            'priority' => 'invalid_priority',
        ];

        $userContext = (object) [
            'id' => 1,
            'user_type' => 'lecturer',
            'role' => 'lecturer'
        ];

        $this->mockPermissionService
            ->shouldReceive('canCreateTasks')
            ->with($userContext)
            ->once()
            ->andReturn(true);

        // Act & Assert
        $this->expectException(TaskException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->taskService->createTask($invalidTaskData, $userContext);
    }

    /**
     * ✅ Test task update success
     */
    public function test_update_task_success()
    {
        // Arrange
        $task = new Task([
            'id' => 1,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'creator_id' => 1,
            'creator_type' => 'lecturer'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        $userContext = (object) [
            'id' => 1,
            'user_type' => 'lecturer',
            'role' => 'lecturer'
        ];

        // Mock expectations
        $this->mockPermissionService
            ->shouldReceive('canEditTask')
            ->with($userContext, 1)
            ->once()
            ->andReturn(true);

        $task->shouldReceive('load')->with('receivers')->once();

        $this->mockTaskRepository
            ->shouldReceive('update')
            ->with($task, $updateData)
            ->once()
            ->andReturn($task);

        $this->mockCacheService
            ->shouldReceive('forgetMultiple')
            ->once()
            ->andReturn(true);

        // Act
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $result = $this->taskService->updateTask($task, $updateData, $userContext);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
    }

    /**
     * ✅ Test task deletion success
     */
    public function test_delete_task_success()
    {
        // Arrange
        $task = new Task([
            'id' => 1,
            'title' => 'Task to Delete',
            'status' => 'pending'
        ]);

        $userContext = (object) [
            'id' => 1,
            'user_type' => 'lecturer',
            'role' => 'lecturer'
        ];

        // Mock expectations
        $this->mockPermissionService
            ->shouldReceive('canDeleteTask')
            ->with($userContext, 1)
            ->once()
            ->andReturn(true);

        $task->shouldReceive('load')->with('receivers')->once();

        $this->mockTaskRepository
            ->shouldReceive('delete')
            ->with($task)
            ->once()
            ->andReturn(true);

        $this->mockCacheService
            ->shouldReceive('forgetMultiple')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->taskService->deleteTask($task, $userContext);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * ✅ Test get tasks for user
     */
    public function test_get_tasks_for_user()
    {
        // Arrange
        $userId = 1;
        $userType = 'student';
        $perPage = 15;

        $mockPaginator = Mockery::mock(\Illuminate\Pagination\LengthAwarePaginator::class);

        $this->mockTaskRepository
            ->shouldReceive('getTasksForUser')
            ->with($userId, $userType, $perPage)
            ->once()
            ->andReturn($mockPaginator);

        // Act
        $result = $this->taskService->getTasksForUser($userId, $userType, $perPage);

        // Assert
        $this->assertEquals($mockPaginator, $result);
    }

    /**
     * ✅ Test get task statistics
     */
    public function test_get_task_statistics()
    {
        // Arrange
        $userId = 1;
        $userType = 'lecturer';

        $expectedStats = [
            'total_tasks' => 10,
            'completed_tasks' => 5,
            'pending_tasks' => 3,
            'overdue_tasks' => 2,
            'completion_rate' => 50.0
        ];

        $cacheKey = "task_stats:{$userType}:{$userId}";

        $this->mockCacheService
            ->shouldReceive('remember')
            ->with($cacheKey, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($expectedStats);

        // Act
        $result = $this->taskService->getTaskStatistics($userId, $userType);

        // Assert
        $this->assertEquals($expectedStats, $result);
        $this->assertEquals(10, $result['total_tasks']);
        $this->assertEquals(50.0, $result['completion_rate']);
    }

    /**
     * ✅ Test cache invalidation on task creation
     */
    public function test_cache_invalidation_on_task_creation()
    {
        // Arrange
        $taskData = [
            'title' => 'Test Task',
            'creator_id' => 1,
            'creator_type' => 'lecturer',
            'receivers' => [
                ['receiver_id' => 1, 'receiver_type' => 'student']
            ]
        ];

        $task = new Task($taskData);
        $task->id = 1;

        $userContext = (object) [
            'id' => 1,
            'user_type' => 'lecturer'
        ];

        // Mock expectations for cache invalidation
        $this->mockPermissionService
            ->shouldReceive('canCreateTasks')
            ->andReturn(true);

        $this->mockTaskRepository
            ->shouldReceive('create')
            ->andReturn($task);

        $task->shouldReceive('load')->with('receivers');
        $task->shouldReceive('addReceiver');

        // Expect cache invalidation to be called
        $this->mockCacheService
            ->shouldReceive('forgetMultiple')
            ->with(Mockery::type('array'))
            ->once()
            ->andReturn(true);

        // Act
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->taskService->createTask($taskData, $userContext);
    }

    /**
     * ✅ Test database transaction rollback on error
     */
    public function test_database_transaction_rollback_on_error()
    {
        // Arrange
        $taskData = ['title' => 'Test Task'];
        $userContext = (object) ['id' => 1, 'user_type' => 'lecturer'];

        $this->mockPermissionService
            ->shouldReceive('canCreateTasks')
            ->andReturn(true);

        $this->mockTaskRepository
            ->shouldReceive('create')
            ->andThrow(new \Exception('Database error'));

        // Mock transaction that should rollback
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            try {
                return $callback();
            } catch (\Exception $e) {
                // Simulate rollback
                throw $e;
            }
        });

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->taskService->createTask($taskData, $userContext);
    }

    /**
     * ✅ Test receiver validation
     */
    public function test_receiver_validation()
    {
        // Arrange
        $invalidReceivers = [
            ['receiver_id' => 'invalid', 'receiver_type' => 'student'], // Invalid ID
            ['receiver_id' => 1, 'receiver_type' => 'invalid_type'], // Invalid type
        ];

        $taskData = [
            'title' => 'Test Task',
            'receivers' => $invalidReceivers
        ];

        $userContext = (object) [
            'id' => 1,
            'user_type' => 'lecturer'
        ];

        $this->mockPermissionService
            ->shouldReceive('canCreateTasks')
            ->andReturn(true);

        // Act & Assert
        $this->expectException(TaskException::class);

        $this->taskService->createTask($taskData, $userContext);
    }

    /**
     * ✅ Test permission cache clearing
     */
    public function test_permission_cache_clearing()
    {
        // Arrange
        $task = new Task(['id' => 1, 'title' => 'Test Task']);
        $userContext = (object) ['id' => 1, 'user_type' => 'lecturer'];

        $this->mockPermissionService
            ->shouldReceive('canEditTask')
            ->andReturn(true);

        $this->mockPermissionService
            ->shouldReceive('clearPermissionCache')
            ->with($task)
            ->once();

        $task->shouldReceive('load')->with('receivers');

        $this->mockTaskRepository
            ->shouldReceive('update')
            ->andReturn($task);

        $this->mockCacheService
            ->shouldReceive('forgetMultiple')
            ->andReturn(true);

        // Act
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->taskService->updateTask($task, ['title' => 'Updated'], $userContext);
    }

    /**
     * ✅ Test bulk cache invalidation efficiency
     */
    public function test_bulk_cache_invalidation_efficiency()
    {
        // Arrange
        $task = new Task([
            'id' => 1,
            'title' => 'Test Task',
            'creator_id' => 1,
            'creator_type' => 'lecturer'
        ]);

        $receivers = collect([
            (object) ['receiver_id' => 1, 'receiver_type' => 'student'],
            (object) ['receiver_id' => 2, 'receiver_type' => 'student'],
            (object) ['receiver_id' => 3, 'receiver_type' => 'student']
        ]);

        $task->shouldReceive('getAttribute')->with('receivers')->andReturn($receivers);

        // Mock cache service to expect bulk operation
        $expectedCacheKeys = [
            'task_1',
            'tasks:lecturer:1',
            'tasks:student:1',
            'tasks:student:2', 
            'tasks:student:3',
            'task_stats:global'
        ];

        $this->mockCacheService
            ->shouldReceive('forgetMultiple')
            ->with(Mockery::subset($expectedCacheKeys))
            ->once()
            ->andReturn(true);

        // Act
        $method = new \ReflectionMethod($this->taskService, 'invalidateMultipleCaches');
        $method->setAccessible(true);
        $method->invoke($this->taskService, $expectedCacheKeys);
    }
}
