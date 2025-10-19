---
trigger: manual
---

# 🧪 Laravel Testing Rules

## Test Structure & Organization

### Test Directory Structure
```
tests/
├── Unit/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   └── DTOs/
├── Feature/
│   ├── Auth/
│   ├── Task/
│   ├── Notifications/
│   └── RollCall/
├── Integration/
│   ├── API/
│   └── Database/
└── Helpers/
    ├── Factories/
    ├── Seeders/
    └── Mocks/
```

### Test Base Classes
```php
// ✅ Good - Base Test Class
<?php
declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
        
        // Clear cache
        $this->artisan('cache:clear');
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        parent::tearDown();
    }
}
```

## Unit Testing

### Service Testing
```php
// ✅ Good - Service Unit Test
<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Task\app\Services\TaskService;
use Modules\Task\app\Repositories\TaskRepositoryInterface;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\Models\Task;
use Mockery;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(TaskRepositoryInterface::class);
        $this->taskService = new TaskService($this->mockRepository);
    }

    public function testCreateTaskSuccessfully(): void
    {
        // Arrange
        $taskDTO = new TaskDTO(
            title: 'Test Task',
            description: 'Test Description',
            deadline: '2024-12-31 23:59:59',
            priority: 'high',
            creatorId: '1',
            receiverId: '2'
        );

        $expectedTask = new Task([
            'id' => '1',
            'title' => 'Test Task',
            'description' => 'Test Description',
            'deadline' => '2024-12-31 23:59:59',
            'priority' => 'high',
            'creator_id' => '1',
            'receiver_id' => '2'
        ]);

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with($taskDTO->toArray())
            ->andReturn($expectedTask);

        // Act
        $result = $this->taskService->createTask($taskDTO);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Test Task', $result->title);
        $this->assertEquals('high', $result->priority);
    }

    public function testCreateTaskWithInvalidData(): void
    {
        // Arrange
        $taskDTO = new TaskDTO(
            title: '', // Invalid empty title
            description: 'Test Description',
            deadline: '2024-12-31 23:59:59',
            priority: 'high',
            creatorId: '1',
            receiverId: '2'
        );

        $this->mockRepository
            ->shouldReceive('create')
            ->never();

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->taskService->createTask($taskDTO);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Repository Testing
```php
// ✅ Good - Repository Unit Test
<?php
declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Modules\Task\app\Repositories\TaskRepository;
use Modules\Task\app\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository(new Task());
    }

    public function testCreateTask(): void
    {
        // Arrange
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'deadline' => '2024-12-31 23:59:59',
            'priority' => 'high',
            'creator_id' => '1',
            'receiver_id' => '2'
        ];

        // Act
        $task = $this->repository->create($taskData);

        // Assert
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertDatabaseHas('tasks', $taskData);
    }

    public function testFindById(): void
    {
        // Arrange
        $task = Task::factory()->create();

        // Act
        $foundTask = $this->repository->findById($task->id);

        // Assert
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertEquals($task->id, $foundTask->id);
    }

    public function testFindByIdReturnsNullForNonExistentTask(): void
    {
        // Act
        $task = $this->repository->findById('non-existent-id');

        // Assert
        $this->assertNull($task);
    }
}
```

## Feature Testing

### API Endpoint Testing
```php
// ✅ Good - Feature Test
<?php
declare(strict_types=1);

namespace Tests\Feature\Task;

use Tests\TestCase;
use Modules\Auth\app\Models\User;
use Modules\Task\app\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['user_type' => 'lecturer']);
        $this->token = $this->generateJWTToken($this->user);
    }

    public function testCreateTaskSuccessfully(): void
    {
        // Arrange
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'deadline' => '2024-12-31 23:59:59',
            'priority' => 'high',
            'receiver_id' => User::factory()->create()->id
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/tasks', $taskData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'deadline',
                    'creator',
                    'receiver'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Test Task',
                    'priority' => 'high'
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'creator_id' => $this->user->id
        ]);
    }

    public function testCreateTaskWithValidationErrors(): void
    {
        // Arrange
        $invalidData = [
            'title' => '', // Invalid empty title
            'description' => 'Test Description',
            'deadline' => '2024-12-31 23:59:59',
            'priority' => 'invalid_priority', // Invalid priority
            'receiver_id' => 'non-existent-id' // Invalid receiver
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/tasks', $invalidData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'priority', 'receiver_id']);
    }

    public function testGetUserTasks(): void
    {
        // Arrange
        Task::factory()->count(3)->create(['creator_id' => $this->user->id]);
        Task::factory()->count(2)->create(['receiver_id' => $this->user->id]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'deadline',
                        'creator',
                        'receiver'
                    ]
                ],
                'meta' => [
                    'total',
                    'cached'
                ]
            ]);

        $this->assertEquals(5, count($response->json('data')));
    }

    public function testUnauthorizedAccess(): void
    {
        // Act
        $response = $this->getJson('/api/tasks');

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token not provided'
            ]);
    }

    private function generateJWTToken(User $user): string
    {
        // Mock JWT token generation for testing
        return 'test-jwt-token';
    }
}
```

## Integration Testing

### Database Integration Tests
```php
// ✅ Good - Integration Test
<?php
declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use Modules\Auth\app\Models\User;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = app(TaskService::class);
    }

    public function testCompleteTaskWorkflow(): void
    {
        // Arrange
        $lecturer = User::factory()->create(['user_type' => 'lecturer']);
        $student = User::factory()->create(['user_type' => 'student']);
        
        $task = Task::factory()->create([
            'creator_id' => $lecturer->id,
            'receiver_id' => $student->id,
            'status' => 'pending'
        ]);

        // Act - Update task status
        $updatedTask = $this->taskService->updateTaskStatus($task->id, 'in_progress');

        // Assert
        $this->assertEquals('in_progress', $updatedTask->status);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress'
        ]);

        // Act - Complete task
        $completedTask = $this->taskService->updateTaskStatus($task->id, 'completed');

        // Assert
        $this->assertEquals('completed', $completedTask->status);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed'
        ]);
    }

    public function testTaskWithAttachments(): void
    {
        // Arrange
        $lecturer = User::factory()->create(['user_type' => 'lecturer']);
        $student = User::factory()->create(['user_type' => 'student']);

        // Act
        $task = $this->taskService->createTaskWithAttachments([
            'title' => 'Task with Attachments',
            'description' => 'Test Description',
            'deadline' => '2024-12-31 23:59:59',
            'priority' => 'medium',
            'creator_id' => $lecturer->id,
            'receiver_id' => $student->id,
            'attachments' => [
                ['filename' => 'test1.pdf', 'size' => 1024],
                ['filename' => 'test2.jpg', 'size' => 2048]
            ]
        ]);

        // Assert
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals(2, $task->attachments()->count());
        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'filename' => 'test1.pdf'
        ]);
    }
}
```

## Test Factories & Seeders

### Model Factories
```php
// ✅ Good - Task Factory
<?php
declare(strict_types=1);

namespace Modules\Task\database\factories;

use Modules\Task\app\Models\Task;
use Modules\Auth\app\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'deadline' => $this->faker->dateTimeBetween('now', '+1 month'),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'creator_id' => User::factory(),
            'receiver_id' => User::factory()
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending'
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed'
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high'
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent'
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'status' => 'pending'
        ]);
    }
}
```

### Test Seeders
```php
// ✅ Good - Test Seeder
<?php
declare(strict_types=1);

namespace Modules\Task\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\app\Models\User;
use Modules\Task\app\Models\Task;

class TaskTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $lecturer = User::factory()->create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@test.com',
            'user_type' => 'lecturer'
        ]);

        $student = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'user_type' => 'student'
        ]);

        // Create test tasks
        Task::factory()->count(5)->create([
            'creator_id' => $lecturer->id,
            'receiver_id' => $student->id
        ]);

        Task::factory()->count(3)->pending()->create([
            'creator_id' => $lecturer->id,
            'receiver_id' => $student->id
        ]);

        Task::factory()->count(2)->completed()->create([
            'creator_id' => $lecturer->id,
            'receiver_id' => $student->id
        ]);
    }
}
```

## Mocking & Stubbing

### Service Mocking
```php
// ✅ Good - Service Mock
<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Notifications\app\Services\NotificationService;
use Modules\Task\app\Models\Task;
use Mockery;

class NotificationServiceMockTest extends TestCase
{
    public function testTaskNotificationSent(): void
    {
        // Arrange
        $mockNotificationService = Mockery::mock(NotificationService::class);
        $task = Task::factory()->create();

        $mockNotificationService
            ->shouldReceive('sendTaskNotification')
            ->once()
            ->with($task, 'created')
            ->andReturn(true);

        // Act
        $result = $mockNotificationService->sendTaskNotification($task, 'created');

        // Assert
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

## Test Configuration

### PHPUnit Configuration
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
            <directory suffix=".php">./Modules</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

## Implementation Guidelines

1. **Write tests first (TDD approach)**
2. **Test behavior, not implementation**
3. **Use descriptive test names**
4. **Keep tests simple and focused**
5. **Mock external dependencies**
6. **Use factories for test data**
7. **Test edge cases and error scenarios**
8. **Maintain high test coverage**
9. **Use database transactions for test isolation**
10. **Clean up after tests**
11. **Use proper assertions**
12. **Test both success and failure cases**
13. **Use integration tests for workflows**
14. **Mock external services**
15. **Test API endpoints thoroughly**
