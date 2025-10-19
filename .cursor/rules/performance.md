---
trigger: manual
---

# 🚀 Laravel Performance Rules

## Caching Strategy

### Redis Caching Implementation
```php
// ✅ Good - Cache Service
<?php
declare(strict_types=1);

namespace Modules\Task\app\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    private readonly int $defaultTtl = 3600; // 1 hour

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? $this->defaultTtl, $callback);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function forgetPattern(string $pattern): void
    {
        $keys = Redis::keys($pattern);
        if (!empty($keys)) {
            Redis::del($keys);
        }
    }

    public function flushModule(string $module): void
    {
        $this->forgetPattern("{$module}:*");
    }

    // Cache key generation
    public function generateKey(string $module, string $type, string $id): string
    {
        return "{$module}:{$type}:{$id}";
    }

    public function generateUserKey(string $module, string $type, string $userId): string
    {
        return "{$module}:{$type}:user:{$userId}";
    }
}
```

### Model Caching
```php
// ✅ Good - Cached Repository
<?php
declare(strict_types=1);

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\CacheService;

class CachedTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly Task $model,
        private readonly CacheService $cache
    ) {}

    public function findById(string $id): ?Task
    {
        $cacheKey = $this->cache->generateKey('task', 'single', $id);
        
        return $this->cache->remember($cacheKey, function () use ($id) {
            return $this->model
                ->with(['creator', 'receiver', 'attachments'])
                ->find($id);
        });
    }

    public function findByUserId(string $userId): Collection
    {
        $cacheKey = $this->cache->generateUserKey('task', 'list', $userId);
        
        return $this->cache->remember($cacheKey, function () use ($userId) {
            return $this->model
                ->where('creator_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->with(['creator', 'receiver'])
                ->orderBy('created_at', 'desc')
                ->get();
        }, 1800); // 30 minutes
    }

    public function create(array $data): Task
    {
        $task = $this->model->create($data);
        
        // Invalidate related caches
        $this->invalidateUserCaches($task->creator_id);
        $this->invalidateUserCaches($task->receiver_id);
        
        return $task;
    }

    public function update(string $id, array $data): Task
    {
        $task = $this->model->findOrFail($id);
        $task->update($data);
        
        // Invalidate caches
        $this->cache->forget($this->cache->generateKey('task', 'single', $id));
        $this->invalidateUserCaches($task->creator_id);
        $this->invalidateUserCaches($task->receiver_id);
        
        return $task->fresh();
    }

    private function invalidateUserCaches(string $userId): void
    {
        $this->cache->forgetPattern("task:*:user:{$userId}");
    }
}
```

## Database Optimization

### Query Optimization
```php
// ✅ Good - Optimized Queries
<?php
declare(strict_types=1);

namespace Modules\Task\app\Repositories;

class OptimizedTaskRepository
{
    public function getTasksWithRelations(): Collection
    {
        // ✅ Good - Eager loading to prevent N+1 queries
        return Task::with([
            'creator:id,name,email',
            'receiver:id,name,email',
            'attachments:id,task_id,filename,size'
        ])->get();
    }

    public function getTasksByStatus(string $status): Collection
    {
        // ✅ Good - Select specific columns
        return Task::select([
            'id', 'title', 'status', 'priority', 'deadline', 'creator_id', 'receiver_id'
        ])
        ->where('status', $status)
        ->with(['creator:id,name', 'receiver:id,name'])
        ->get();
    }

    public function getTasksPaginated(int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        // ✅ Good - Pagination for large datasets
        return Task::with(['creator:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getTaskStatistics(): array
    {
        // ✅ Good - Aggregation queries
        return Task::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending,
            COUNT(CASE WHEN status = "in_progress" THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            COUNT(CASE WHEN priority = "high" THEN 1 END) as high_priority
        ')->first()->toArray();
    }
}
```

### Database Indexing
```php
// ✅ Good - Migration with indexes
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->datetime('deadline');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']);
            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // ✅ Good - Composite indexes for common queries
            $table->index(['creator_id', 'status']);
            $table->index(['receiver_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['deadline', 'status']);
            $table->index(['created_at', 'status']);
        });
    }
}
```

## Background Processing

### Queue Jobs
```php
// ✅ Good - Background Job
<?php
declare(strict_types=1);

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Task\app\Models\Task;
use Modules\Notifications\app\Services\NotificationService;

class SendTaskNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Task $task,
        private readonly string $type
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->sendTaskNotification($this->task, $this->type);
        } catch (Exception $e) {
            // Log error and retry
            logger()->error('Task notification failed', [
                'task_id' => $this->task->id,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // This will trigger retry
        }
    }

    public function failed(Throwable $exception): void
    {
        logger()->error('Task notification job failed permanently', [
            'task_id' => $this->task->id,
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(30);
    }
}
```

### Kafka Integration
```php
// ✅ Good - Kafka Producer
<?php
declare(strict_types=1);

namespace Modules\Notifications\app\Services;

use RdKafka\Producer;
use RdKafka\ProducerTopic;

class KafkaProducerService
{
    private readonly Producer $producer;
    private readonly ProducerTopic $topic;

    public function __construct()
    {
        $this->producer = new Producer();
        $this->producer->addBrokers(config('kafka.brokers'));
        $this->topic = $this->producer->newTopic(config('kafka.topic'));
    }

    public function publishTaskEvent(string $eventType, array $data): void
    {
        $message = [
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'source' => 'task-service'
        ];

        $this->topic->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            json_encode($message),
            uniqid()
        );

        $this->producer->flush(1000); // Wait up to 1 second
    }
}
```

## Memory Management

### Memory Optimization
```php
// ✅ Good - Memory-efficient processing
<?php
declare(strict_types=1);

namespace Modules\Task\app\Services;

class TaskProcessingService
{
    public function processLargeTaskList(): void
    {
        // ✅ Good - Process in chunks to avoid memory issues
        Task::chunk(100, function ($tasks) {
            foreach ($tasks as $task) {
                $this->processTask($task);
            }
        });
    }

    public function generateTaskReport(): void
    {
        // ✅ Good - Use cursor for large datasets
        $tasks = Task::where('created_at', '>=', now()->subDays(30))
            ->cursor();

        foreach ($tasks as $task) {
            $this->processTaskForReport($task);
            
            // Free memory periodically
            if (memory_get_usage() > 128 * 1024 * 1024) { // 128MB
                gc_collect_cycles();
            }
        }
    }

    private function processTask(Task $task): void
    {
        // Process individual task
        // Unset variables after use
        unset($task);
    }
}
```

## API Performance

### Response Optimization
```php
// ✅ Good - Optimized API Controller
<?php
declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers;

use Modules\Task\app\Services\TaskService;
use Modules\Task\app\Services\CacheService;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly CacheService $cache
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cacheKey = $this->cache->generateUserKey(
            'task',
            'list',
            $request->user()['sub']
        );

        $tasks = $this->cache->remember($cacheKey, function () use ($request) {
            return $this->taskService->getUserTasks($request->user()['sub']);
        });

        // ✅ Good - Transform data efficiently
        $transformedTasks = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'deadline' => $task->deadline?->toISOString(),
                'creator' => [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name
                ],
                'receiver' => [
                    'id' => $task->receiver->id,
                    'name' => $task->receiver->name
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedTasks,
            'meta' => [
                'total' => $tasks->count(),
                'cached' => true
            ]
        ]);
    }
}
```

## Monitoring & Metrics

### Performance Monitoring
```php
// ✅ Good - Performance Middleware
<?php
declare(strict_types=1);

namespace Modules\Common\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerformanceMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;

        // Log performance metrics
        Log::info('Performance Metrics', [
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->url(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'memory_peak_bytes' => memory_get_peak_usage(),
            'status_code' => $response->getStatusCode()
        ]);

        // Add performance headers
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsed));

        return $response;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

## Implementation Guidelines

1. **Implement Redis caching strategically**
2. **Use database indexes properly**
3. **Optimize queries with eager loading**
4. **Use pagination for large datasets**
5. **Process data in chunks**
6. **Use background jobs for heavy operations**
7. **Monitor performance metrics**
8. **Optimize memory usage**
9. **Use connection pooling**
10. **Implement proper cache invalidation**
11. **Use CDN for static assets**
12. **Compress API responses**
13. **Implement request/response caching**
14. **Use database query caching**
15. **Monitor slow queries**
