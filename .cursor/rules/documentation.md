---
trigger: manual
---

# 📝 Laravel Documentation Rules

## Code Documentation Standards

### PHPDoc Standards
```php
// ✅ Good - Class Documentation
<?php
declare(strict_types=1);

/**
 * Task Service
 * 
 * Handles all business logic related to task management including
 * creation, updates, status changes, and notifications.
 * 
 * @package Modules\Task\app\Services
 * @author System Management Team
 * @version 1.0.0
 * @since 2024-01-01
 */
class TaskService implements TaskServiceInterface
{
    /**
     * Create a new task
     * 
     * Creates a new task with the provided data, validates business rules,
     * sends notifications, and updates cache.
     * 
     * @param TaskDTO $taskDTO The task data transfer object
     * @return Task The created task instance
     * @throws ValidationException When task data is invalid
     * @throws PermissionException When user lacks permission
     * @throws DatabaseException When database operation fails
     * 
     * @example
     * ```php
     * $taskDTO = new TaskDTO(
     *     title: 'Complete project',
     *     description: 'Finish the project by deadline',
     *     deadline: '2024-12-31 23:59:59',
     *     priority: 'high',
     *     creatorId: '1',
     *     receiverId: '2'
     * );
     * $task = $taskService->createTask($taskDTO);
     * ```
     */
    public function createTask(TaskDTO $taskDTO): Task
    {
        // Implementation
    }

    /**
     * Update task status
     * 
     * Updates the status of an existing task and triggers appropriate
     * notifications and cache invalidation.
     * 
     * @param string $taskId The unique identifier of the task
     * @param string $status The new status (pending|in_progress|completed|cancelled)
     * @return Task The updated task instance
     * @throws TaskNotFoundException When task doesn't exist
     * @throws InvalidStatusException When status is invalid
     * @throws PermissionException When user lacks permission
     * 
     * @since 1.0.0
     * @deprecated Use updateTaskStatusWithReason() instead
     */
    public function updateTaskStatus(string $taskId, string $status): Task
    {
        // Implementation
    }
}
```

### Interface Documentation
```php
// ✅ Good - Interface Documentation
<?php
declare(strict_types=1);

/**
 * Task Service Interface
 * 
 * Defines the contract for task management operations.
 * All implementations must follow this interface to ensure
 * consistency across the application.
 * 
 * @package Modules\Task\app\Services
 * @interface TaskServiceInterface
 */
interface TaskServiceInterface
{
    /**
     * Create a new task
     * 
     * @param TaskDTO $taskDTO Task data transfer object
     * @return Task Created task instance
     * @throws ValidationException|PermissionException|DatabaseException
     */
    public function createTask(TaskDTO $taskDTO): Task;

    /**
     * Update task status
     * 
     * @param string $taskId Task identifier
     * @param string $status New status
     * @return Task Updated task instance
     * @throws TaskNotFoundException|InvalidStatusException|PermissionException
     */
    public function updateTaskStatus(string $taskId, string $status): Task;

    /**
     * Get tasks by user ID
     * 
     * @param string $userId User identifier
     * @param array $filters Optional filters
     * @return Collection Task collection
     */
    public function getTasksByUserId(string $userId, array $filters = []): Collection;
}
```

### Model Documentation
```php
// ✅ Good - Model Documentation
<?php
declare(strict_types=1);

/**
 * Task Model
 * 
 * Represents a task entity in the system. Tasks are created by lecturers
 * and assigned to students with specific deadlines and priorities.
 * 
 * @property string $id Unique task identifier
 * @property string $title Task title
 * @property string $description Task description
 * @property \DateTime $deadline Task deadline
 * @property string $priority Task priority (low|medium|high|urgent)
 * @property string $status Task status (pending|in_progress|completed|cancelled)
 * @property string $creator_id ID of the user who created the task
 * @property string $receiver_id ID of the user assigned to the task
 * @property \DateTime $created_at Creation timestamp
 * @property \DateTime $updated_at Last update timestamp
 * @property \DateTime|null $deleted_at Soft delete timestamp
 * 
 * @property-read User $creator The user who created the task
 * @property-read User $receiver The user assigned to the task
 * @property-read Collection $attachments Task attachments
 * 
 * @package Modules\Task\app\Models
 * @mixin \Eloquent
 */
class Task extends Model
{
    /**
     * The attributes that are mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'creator_id',
        'receiver_id'
    ];

    /**
     * The attributes that should be cast
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'deadline' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the user who created the task
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the user assigned to the task
     * 
     * @return BelongsTo
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get task attachments
     * 
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Scope tasks by status
     * 
     * @param Builder $query Query builder
     * @param string $status Task status
     * @return void
     */
    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope tasks by priority
     * 
     * @param Builder $query Query builder
     * @param string $priority Task priority
     * @return void
     */
    public function scopeByPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * Check if task is overdue
     * 
     * @return bool True if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->deadline < now() && $this->status !== 'completed';
    }

    /**
     * Get task completion percentage
     * 
     * @return int Completion percentage (0-100)
     */
    public function getCompletionPercentage(): int
    {
        return match($this->status) {
            'pending' => 0,
            'in_progress' => 50,
            'completed' => 100,
            'cancelled' => 0,
            default => 0
        };
    }
}
```

## API Documentation

### Swagger/OpenAPI Documentation
```php
// ✅ Good - API Controller Documentation
<?php
declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers;

/**
 * @OA\Tag(
 *     name="Tasks",
 *     description="Task management endpoints"
 * )
 */
class TaskController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Create a new task",
     *     description="Creates a new task and assigns it to a user",
     *     operationId="createTask",
     *     tags={"Tasks"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "deadline", "priority", "receiver_id"},
     *             @OA\Property(property="title", type="string", example="Complete project report"),
     *             @OA\Property(property="description", type="string", example="Finish the quarterly project report"),
     *             @OA\Property(property="deadline", type="string", format="date-time", example="2024-12-31T23:59:59Z"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}, example="high"),
     *             @OA\Property(property="receiver_id", type="string", example="2"),
     *             @OA\Property(property="attachments", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Task"),
     *             @OA\Property(property="message", type="string", example="Task created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token not provided")
     *         )
     *     )
     * )
     */
    public function store(CreateTaskRequest $request): JsonResponse
    {
        // Implementation
    }

    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Get user tasks",
     *     description="Retrieves all tasks for the authenticated user",
     *     operationId="getUserTasks",
     *     tags={"Tasks"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by task status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "in_progress", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by task priority",
     *         required=false,
     *         @OA\Schema(type="string", enum={"low", "medium", "high", "urgent"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tasks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Task")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Implementation
    }
}
```

### Schema Definitions
```php
// ✅ Good - Schema Definitions
/**
 * @OA\Schema(
 *     schema="Task",
 *     type="object",
 *     title="Task",
 *     description="Task entity",
 *     required={"id", "title", "description", "deadline", "priority", "status", "creator_id", "receiver_id"},
 *     @OA\Property(property="id", type="string", example="1"),
 *     @OA\Property(property="title", type="string", example="Complete project report"),
 *     @OA\Property(property="description", type="string", example="Finish the quarterly project report"),
 *     @OA\Property(property="deadline", type="string", format="date-time", example="2024-12-31T23:59:59Z"),
 *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}, example="high"),
 *     @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "cancelled"}, example="pending"),
 *     @OA\Property(property="creator_id", type="string", example="1"),
 *     @OA\Property(property="receiver_id", type="string", example="2"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="creator", ref="#/components/schemas/User"),
 *     @OA\Property(property="receiver", ref="#/components/schemas/User"),
 *     @OA\Property(property="attachments", type="array", @OA\Items(ref="#/components/schemas/TaskAttachment"))
 * )
 */

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User entity",
 *     required={"id", "name", "email", "user_type"},
 *     @OA\Property(property="id", type="string", example="1"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="user_type", type="string", enum={"student", "lecturer", "admin"}, example="lecturer"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Pagination Meta",
 *     description="Pagination metadata",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="last_page", type="integer", example=7),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=15)
 * )
 */
```

## README Documentation

### Module README Template
```markdown
# Task Module

## Overview
The Task Module handles all task management functionality including creation, assignment, status updates, and notifications.

## Features
- ✅ Create and assign tasks
- ✅ Update task status
- ✅ File attachments
- ✅ Priority management
- ✅ Deadline tracking
- ✅ Notifications
- ✅ Search and filtering
- ✅ Pagination

## Architecture
This module follows Clean Architecture principles with the following layers:

```
Controllers (Presentation Layer)
    ↓
Services (Application Layer)
    ↓
Models (Domain Layer)
    ↓
Repositories (Infrastructure Layer)
```

## Installation
1. Ensure the module is enabled in `config/modules.php`
2. Run migrations: `php artisan migrate`
3. Seed test data: `php artisan db:seed --class=TaskTestSeeder`

## Configuration
Configure the module in `Modules/Task/config/task.php`:

```php
return [
    'max_attachments' => 5,
    'max_file_size' => 10240, // KB
    'allowed_file_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    'notification_channels' => ['email', 'kafka'],
    'cache_ttl' => 3600, // seconds
];
```

## API Endpoints

### Tasks
- `POST /api/tasks` - Create a new task
- `GET /api/tasks` - Get user tasks
- `GET /api/tasks/{id}` - Get specific task
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task
- `PUT /api/tasks/{id}/status` - Update task status

### Task Attachments
- `POST /api/tasks/{id}/attachments` - Upload attachment
- `GET /api/tasks/{id}/attachments` - Get task attachments
- `DELETE /api/tasks/{id}/attachments/{attachmentId}` - Delete attachment

## Usage Examples

### Creating a Task
```php
use Modules\Task\app\Services\TaskService;
use Modules\Task\app\DTOs\TaskDTO;

$taskService = app(TaskService::class);

$taskDTO = new TaskDTO(
    title: 'Complete project report',
    description: 'Finish the quarterly project report',
    deadline: '2024-12-31 23:59:59',
    priority: 'high',
    creatorId: '1',
    receiverId: '2'
);

$task = $taskService->createTask($taskDTO);
```

### Updating Task Status
```php
$task = $taskService->updateTaskStatus('task-id', 'in_progress');
```

## Testing
Run tests with:
```bash
php artisan test --filter=Task
```

## Dependencies
- Auth Module (for user management)
- Notifications Module (for notifications)
- Handle Module (for file processing)

## Changelog
- v1.0.0 - Initial release
- v1.1.0 - Added file attachments
- v1.2.0 - Added notifications
```

## Code Comments Standards

### Inline Comments
```php
// ✅ Good - Explaining why, not what
public function createTask(TaskDTO $taskDTO): Task
{
    // Validate business rules before creating task
    $this->validateTaskBusinessRules($taskDTO);
    
    // Use transaction to ensure data consistency
    return DB::transaction(function () use ($taskDTO) {
        $task = $this->taskRepository->create($taskDTO->toArray());
        
        // Invalidate cache to ensure fresh data on next request
        $this->cache->forget("tasks:user:{$taskDTO->creatorId}");
        
        // Dispatch event for notification system
        event(new TaskCreated($task));
        
        return $task;
    });
}

// ❌ Bad - Explaining what the code does
public function createTask(TaskDTO $taskDTO): Task
{
    // Call validateTaskBusinessRules method
    $this->validateTaskBusinessRules($taskDTO);
    
    // Start database transaction
    return DB::transaction(function () use ($taskDTO) {
        // Create task in repository
        $task = $this->taskRepository->create($taskDTO->toArray());
        
        // Remove cache entry
        $this->cache->forget("tasks:user:{$taskDTO->creatorId}");
        
        // Fire event
        event(new TaskCreated($task));
        
        return $task;
    });
}
```

### Complex Logic Documentation
```php
// ✅ Good - Complex algorithm documentation
/**
 * Calculate task priority score based on multiple factors
 * 
 * This algorithm considers:
 * 1. Deadline proximity (closer deadline = higher score)
 * 2. Task dependencies (more dependencies = higher score)
 * 3. User workload (higher workload = lower score)
 * 4. Historical completion rate (better rate = higher score)
 * 
 * Score range: 0-100 (higher = more urgent)
 */
public function calculatePriorityScore(Task $task, User $user): int
{
    $deadlineScore = $this->calculateDeadlineScore($task->deadline);
    $dependencyScore = $this->calculateDependencyScore($task);
    $workloadScore = $this->calculateWorkloadScore($user);
    $completionScore = $this->calculateCompletionScore($user);
    
    // Weighted average with business-determined weights
    $score = ($deadlineScore * 0.4) + 
             ($dependencyScore * 0.3) + 
             ($workloadScore * 0.2) + 
             ($completionScore * 0.1);
    
    return min(100, max(0, (int) $score));
}
```

## Implementation Guidelines

1. **Document all public methods and classes**
2. **Use descriptive variable and method names**
3. **Explain complex business logic**
4. **Provide usage examples**
5. **Document API endpoints thoroughly**
6. **Keep documentation up to date**
7. **Use consistent documentation style**
8. **Include error scenarios**
9. **Document configuration options**
10. **Provide migration guides for breaking changes**
11. **Include performance considerations**
12. **Document security requirements**
13. **Provide troubleshooting guides**
14. **Include code examples**
15. **Document dependencies and requirements**
