---
trigger: manual
---

# 🏗️ Laravel Clean Architecture Rules

## Architecture Layers

### 1. Presentation Layer (Controllers, Requests, Middleware)
```php
// ✅ Good - Controller structure
<?php
declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers;

use Modules\Task\app\Services\TaskService;
use Modules\Task\app\Http\Requests\CreateTaskRequest;
use Modules\Task\app\DTOs\TaskDTO;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService
    ) {}

    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $taskDTO = TaskDTO::fromRequest($request);
            $task = $this->taskService->createTask($taskDTO);
            
            return response()->json([
                'success' => true,
                'data' => $task->toArray(),
                'message' => 'Task created successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

### 2. Application Layer (Use Cases, DTOs, Services)
```php
// ✅ Good - Service structure
<?php
declare(strict_types=1);

namespace Modules\Task\app\Services;

use Modules\Task\app\Repositories\TaskRepositoryInterface;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\Models\Task;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly CacheManager $cache
    ) {}

    public function createTask(TaskDTO $taskDTO): Task
    {
        return DB::transaction(function () use ($taskDTO) {
            $task = $this->taskRepository->create($taskDTO->toArray());
            
            // Invalidate cache
            $this->cache->forget("tasks:user:{$taskDTO->creatorId}");
            
            // Dispatch event
            event(new TaskCreated($task));
            
            return $task;
        });
    }
}
```

### 3. Domain Layer (Models, Entities, Value Objects)
```php
// ✅ Good - Model structure
<?php
declare(strict_types=1);

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'creator_id',
        'receiver_id'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function scopeByStatus($query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority): void
    {
        $query->where('priority', $priority);
    }
}
```

### 4. Infrastructure Layer (Repositories, External Services)
```php
// ✅ Good - Repository structure
<?php
declare(strict_types=1);

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Models\Task;
use Modules\Task\app\DTOs\TaskDTO;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly Task $model,
        private readonly CacheManager $cache
    ) {}

    public function create(array $data): Task
    {
        return $this->model->create($data);
    }

    public function findById(string $id): ?Task
    {
        return $this->cache->remember(
            "task:{$id}",
            3600,
            fn() => $this->model->with(['creator', 'receiver', 'attachments'])->find($id)
        );
    }

    public function findByUserId(string $userId): Collection
    {
        return $this->cache->remember(
            "tasks:user:{$userId}",
            1800,
            fn() => $this->model
                ->where('creator_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->with(['creator', 'receiver'])
                ->get()
        );
    }
}
```

## DTO Pattern Implementation

```php
// ✅ Good - DTO structure
<?php
declare(strict_types=1);

namespace Modules\Task\app\DTOs;

use Illuminate\Http\Request;

readonly class TaskDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public string $deadline,
        public string $priority,
        public string $creatorId,
        public string $receiverId,
        public ?array $attachments = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            description: $request->input('description'),
            deadline: $request->input('deadline'),
            priority: $request->input('priority'),
            creatorId: $request->input('creator_id'),
            receiverId: $request->input('receiver_id'),
            attachments: $request->input('attachments')
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline,
            'priority' => $this->priority,
            'creator_id' => $this->creatorId,
            'receiver_id' => $this->receiverId,
            'attachments' => $this->attachments
        ];
    }
}
```

## Interface Segregation

```php
// ✅ Good - Service interface
<?php
declare(strict_types=1);

namespace Modules\Task\app\Services;

use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskServiceInterface
{
    public function createTask(TaskDTO $taskDTO): Task;
    public function updateTask(string $id, TaskDTO $taskDTO): Task;
    public function deleteTask(string $id): bool;
    public function getTaskById(string $id): ?Task;
    public function getTasksByUserId(string $userId): Collection;
    public function updateTaskStatus(string $id, string $status): Task;
}
```

## Dependency Injection Rules

1. **Always use constructor injection**
2. **Define interfaces for all services**
3. **Use readonly properties for immutable dependencies**
4. **Register interfaces in service providers**
5. **Use method injection for optional dependencies**

## Implementation Guidelines

1. **Follow Clean Architecture principles strictly**
2. **Use dependency injection everywhere**
3. **Implement proper interfaces**
4. **Use DTOs for data transfer**
5. **Keep business logic in services**
6. **Use repositories for data access**
7. **Implement proper error handling**
8. **Add comprehensive logging**
9. **Use caching strategically**
10. **Follow SOLID principles**
