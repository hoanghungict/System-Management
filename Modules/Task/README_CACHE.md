# 🚀 Redis Cache System - Task Module

## 📋 Tổng quan

Hệ thống cache Redis cho module Task được xây dựng tuân theo **Clean Architecture** với các layer rõ ràng và separation of concerns.

## 🏗️ Kiến trúc

### **Clean Architecture Layers**

```
┌─────────────────────────────────────┐
│           Presentation Layer         │
│  ┌─────────────────────────────────┐ │
│  │      CacheController            │ │
│  │  (HTTP Requests/Responses)      │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────┐
│         Business Logic Layer         │
│  ┌─────────────────────────────────┐ │
│  │     RedisCacheService           │ │
│  │  (Business Rules & Logic)       │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────┐
│         Data Access Layer            │
│  ┌─────────────────────────────────┐ │
│  │   RedisCacheRepository          │ │
│  │  (Data Access Operations)       │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────┐
│         Infrastructure Layer        │
│  ┌─────────────────────────────────┐ │
│  │         Redis Server            │ │
│  │  (External Infrastructure)     │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

### **Components**

| Layer | Component | Responsibility |
|-------|-----------|----------------|
| **Presentation** | `CacheController` | HTTP requests/responses |
| **Business Logic** | `RedisCacheService` | Business rules & validation |
| **Data Access** | `RedisCacheRepository` | Redis operations |
| **Infrastructure** | `CacheServiceProvider` | Dependency injection |
| **Events** | `CacheEvent` & `CacheEventListener` | Event-driven operations |
| **DTOs** | `CacheDTO` | Data transfer objects |

## 🔧 Cài đặt & Cấu hình

### **1. Environment Variables**

```env
# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

# Cache Configuration
CACHE_DRIVER=redis
CACHE_PREFIX=task_module
CACHE_MAX_VALUE_SIZE=1048576
CACHE_STATISTICS_ENABLED=true
CACHE_EVENTS_ENABLED=true
CACHE_TAGS_ENABLED=true
```

### **2. Service Provider Registration**

```php
// In module.json
{
    "providers": [
        "Modules\\Task\\Providers\\TaskServiceProvider",
        "Modules\\Task\\app\\Providers\\CacheServiceProvider"
    ]
}
```

### **3. Redis Connection**

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

## 🚀 Sử dụng

### **1. Basic Cache Operations**

```php
use Modules\Task\app\Services\Interfaces\RedisCacheServiceInterface;
use Modules\Task\app\DTOs\CacheDTO;

class TaskService
{
    public function __construct(
        private RedisCacheServiceInterface $cacheService
    ) {}

    public function getTask($id)
    {
        $cacheKey = "task:{$id}";
        
        return $this->cacheService->remember($cacheKey, function() use ($id) {
            return Task::find($id);
        }, 3600);
    }

    public function storeTask($data)
    {
        $task = Task::create($data);
        
        $cacheDTO = new CacheDTO(
            "task:{$task->id}",
            $task,
            3600,
            'important',
            ['tags' => ['tasks', 'user:' . $task->creator_id]]
        );
        
        $this->cacheService->set($cacheDTO);
        
        return $task;
    }
}
```

### **2. Cache Levels**

```php
// Critical - 5 minutes
$cacheDTO = new CacheDTO('key', $value, null, 'critical');

// Important - 15 minutes  
$cacheDTO = new CacheDTO('key', $value, null, 'important');

// Normal - 30 minutes (default)
$cacheDTO = new CacheDTO('key', $value, null, 'normal');

// Background - 1 hour
$cacheDTO = new CacheDTO('key', $value, null, 'background');

// Long term - 2 hours
$cacheDTO = new CacheDTO('key', $value, null, 'long_term');
```

### **3. Cache Tags**

```php
// Set cache with tags
$cacheDTO = new CacheDTO(
    'user:123:profile',
    $userData,
    3600,
    'normal',
    ['tags' => ['users', 'profiles', 'user:123']]
);

// Invalidate by tags
$this->cacheService->invalidateTags(['users', 'profiles']);
```

### **4. Pattern-based Operations**

```php
// Delete all user-related cache
$this->cacheService->deletePattern('user:*');

// Delete all task cache for specific user
$this->cacheService->deletePattern("task:user:{$userId}:*");
```

## 📊 API Endpoints

### **Cache Management API**

```bash
# Get cache data
GET /api/cache/get?key=task:123

# Set cache data
POST /api/cache/set
{
    "key": "task:123",
    "value": {"id": 123, "title": "Task Title"},
    "ttl": 3600,
    "level": "important",
    "tags": ["tasks", "user:1"]
}

# Delete cache
POST /api/cache/delete
{
    "key": "task:123"
}

# Delete multiple keys
POST /api/cache/delete-multiple
{
    "keys": ["task:123", "task:124", "task:125"]
}

# Delete by pattern
POST /api/cache/delete-pattern
{
    "pattern": "task:*"
}

# Check cache exists
GET /api/cache/exists?key=task:123

# Get statistics
GET /api/cache/statistics

# Ping Redis
GET /api/cache/ping

# Clear all cache
POST /api/cache/clear-all

# Warm up cache
POST /api/cache/warm-up
{
    "cache_data": [
        {
            "key": "task:123",
            "value": {"id": 123, "title": "Task"},
            "ttl": 3600,
            "level": "normal"
        }
    ]
}

# Invalidate tags
POST /api/cache/invalidate-tags
{
    "tags": ["tasks", "users"]
}
```

## 📈 Monitoring & Statistics

### **Cache Metrics**

```php
// Get cache statistics
$stats = $this->cacheService->getStatistics();

// Available metrics:
[
    'total_connections_received' => 1000,
    'total_commands_processed' => 50000,
    'keyspace_hits' => 45000,
    'keyspace_misses' => 5000,
    'used_memory' => 1048576,
    'used_memory_peak' => 2097152,
    'uptime_in_seconds' => 86400,
    'connected_clients' => 10,
    'module_prefix' => 'task_module',
    'module_keys' => 150
]
```

### **Event Tracking**

```php
// Cache events are automatically tracked
CacheCreatedEvent::dispatch($key, $metadata);
CacheDeletedEvent::dispatch($key, $metadata);
CacheHitEvent::dispatch($key, $metadata);
CacheMissedEvent::dispatch($key, $metadata);
CacheInvalidatedEvent::dispatch($key, $metadata);
```

## 🔒 Security & Validation

### **Key Validation**

```php
// Keys must follow pattern: [a-zA-Z0-9:_-]+
// Max length: 250 characters
// Must not be empty

$validKey = "task:123:user:456"; // ✅ Valid
$invalidKey = "task@123"; // ❌ Invalid
```

### **Value Size Limits**

```php
// Default max size: 1MB
// Configurable via CACHE_MAX_VALUE_SIZE

$largeValue = str_repeat('a', 1048577); // ❌ Too large
$normalValue = ['id' => 123, 'title' => 'Task']; // ✅ OK
```

### **TTL Validation**

```php
// TTL must be positive integer
// Level-based TTL is automatically applied if not specified

$cacheDTO = new CacheDTO('key', $value, 3600); // ✅ Valid TTL
$cacheDTO = new CacheDTO('key', $value, -1); // ❌ Invalid TTL
```

## 🚀 Performance Optimization

### **1. Batch Operations**

```php
// Delete multiple keys efficiently
$this->cacheService->deleteMultiple([
    'task:123',
    'task:124', 
    'task:125'
]);

// Process in batches of 100 keys
```

### **2. Pattern Matching**

```php
// Use SCAN instead of KEYS for large datasets
$keys = $this->cacheService->getKeys('task:*');

// Safe pattern deletion
$this->cacheService->deletePattern('task:*');
```

### **3. Connection Pooling**

```php
// Redis connection is reused
// Automatic connection management
// Connection pooling via Laravel Redis facade
```

## 🧪 Testing

### **Unit Tests**

```php
class RedisCacheServiceTest extends TestCase
{
    public function test_cache_set_and_get()
    {
        $cacheService = app(RedisCacheServiceInterface::class);
        
        $cacheDTO = new CacheDTO('test:key', 'test_value', 3600);
        $result = $cacheService->set($cacheDTO);
        
        $this->assertTrue($result);
        
        $value = $cacheService->get('test:key');
        $this->assertEquals('test_value', $value);
    }
}
```

### **Integration Tests**

```php
class CacheControllerTest extends TestCase
{
    public function test_cache_api_endpoints()
    {
        $response = $this->postJson('/api/cache/set', [
            'key' => 'test:key',
            'value' => 'test_value',
            'ttl' => 3600
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
```

## 🔧 Troubleshooting

### **Common Issues**

1. **Redis Connection Failed**
   ```bash
   # Check Redis service
   redis-cli ping
   
   # Check configuration
   php artisan config:cache
   ```

2. **Cache Keys Not Found**
   ```php
   // Check key format
   $key = $this->cacheService->generateKey('prefix', $params);
   
   // Check if key exists
   $exists = $this->cacheService->exists($key);
   ```

3. **Memory Issues**
   ```php
   // Monitor memory usage
   $stats = $this->cacheService->getStatistics();
   echo $stats['used_memory'];
   
   // Clear old cache
   $this->cacheService->clearAll();
   ```

### **Debug Mode**

```php
// Enable debug logging
Log::debug('Cache operation', [
    'key' => $key,
    'operation' => 'get',
    'result' => $value
]);
```

## 📚 Best Practices

### **1. Key Naming Convention**

```php
// Use descriptive, hierarchical keys
$key = "task:{$taskId}:user:{$userId}:profile";
$key = "faculty:{$facultyId}:students:count";
$key = "report:monthly:{$year}:{$month}";
```

### **2. TTL Strategy**

```php
// Critical data: 5 minutes
// Frequently accessed: 15 minutes  
// Normal data: 30 minutes
// Background data: 1 hour
// Long-term data: 2 hours
```

### **3. Tag Usage**

```php
// Use tags for related data
$tags = ['tasks', 'user:' . $userId, 'faculty:' . $facultyId];

// Invalidate related cache efficiently
$this->cacheService->invalidateTags(['tasks']);
```

### **4. Error Handling**

```php
try {
    $value = $this->cacheService->get($key);
} catch (\Exception $e) {
    Log::error('Cache get failed', ['key' => $key, 'error' => $e->getMessage()]);
    // Fallback to database
    $value = $this->getFromDatabase($key);
}
```

## 🎯 Conclusion

Hệ thống cache Redis này cung cấp:

- ✅ **Clean Architecture** với separation of concerns
- ✅ **High Performance** với Redis
- ✅ **Event-driven** operations
- ✅ **Comprehensive API** cho cache management
- ✅ **Monitoring & Statistics** 
- ✅ **Security & Validation**
- ✅ **Scalable & Maintainable** code

Sử dụng hệ thống này sẽ cải thiện đáng kể performance của ứng dụng Task Management System.
