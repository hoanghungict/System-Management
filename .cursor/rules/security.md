---
trigger: manual
---

# 🔐 Laravel Security Rules

## Authentication & Authorization

### JWT Authentication with Firebase JWT 6.11
```php
// ✅ Good - JWT Service
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Modules\Auth\app\Models\User;

class JWTService
{
    private readonly string $secretKey;
    private readonly string $algorithm;

    public function __construct()
    {
        $this->secretKey = config('jwt.secret');
        $this->algorithm = config('jwt.algorithm', 'HS256');
    }

    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => time(),
            'exp' => time() + config('jwt.ttl', 3600),
            'sub' => $user->id,
            'user_type' => $user->user_type,
            'email' => $user->email
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    public function refreshToken(string $token): string
    {
        $payload = $this->validateToken($token);
        if (!$payload) {
            throw new InvalidTokenException('Invalid token');
        }

        $user = User::find($payload['sub']);
        return $this->generateToken($user);
    }
}
```

### Role-Based Access Control
```php
// ✅ Good - Permission Service
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Services;

use Modules\Auth\app\Models\User;

class PermissionService
{
    public function canAccessTask(User $user, string $action): bool
    {
        return match($user->user_type) {
            'admin' => true,
            'lecturer' => in_array($action, ['create', 'read', 'update', 'delete']),
            'student' => in_array($action, ['read', 'update']),
            default => false
        };
    }

    public function canAccessUser(User $user, string $action): bool
    {
        return match($user->user_type) {
            'admin' => true,
            'lecturer' => in_array($action, ['read']),
            'student' => false,
            default => false
        };
    }

    public function canAccessRollCall(User $user, string $action): bool
    {
        return match($user->user_type) {
            'admin', 'lecturer' => true,
            'student' => $action === 'read',
            default => false
        };
    }
}
```

### Middleware Implementation
```php
// ✅ Good - JWT Middleware
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\JWTService;

class JWTMiddleware
{
    public function __construct(
        private readonly JWTService $jwtService
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided'
            ], 401);
        }

        $payload = $this->jwtService->validateToken($token);
        
        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        $request->merge(['user' => $payload]);
        
        return $next($request);
    }
}
```

## Input Validation & Sanitization

### Form Request Validation
```php
// ✅ Good - Form Request
<?php
declare(strict_types=1);

namespace Modules\Task\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()['user_type'] !== 'student';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'min:3'],
            'description' => ['required', 'string', 'max:1000'],
            'deadline' => ['required', 'date', 'after:now'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'receiver_id' => ['required', 'exists:users,id'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'title.min' => 'Task title must be at least 3 characters',
            'deadline.after' => 'Deadline must be in the future',
            'receiver_id.exists' => 'Selected receiver does not exist',
            'attachments.max' => 'Maximum 5 attachments allowed',
            'attachments.*.max' => 'File size must not exceed 10MB'
        ];
    }
}
```

### SQL Injection Prevention
```php
// ✅ Good - Using Eloquent ORM
<?php
declare(strict_types=1);

namespace Modules\Task\app\Repositories;

class TaskRepository
{
    public function searchTasks(string $query, string $userId): Collection
    {
        // ✅ Good - Using Eloquent with parameterized queries
        return Task::where('creator_id', $userId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->get();

        // ❌ Bad - Raw SQL without proper escaping
        // return DB::select("SELECT * FROM tasks WHERE title LIKE '%{$query}%'");
    }

    public function getTasksByStatus(string $status, string $userId): Collection
    {
        // ✅ Good - Using Eloquent with proper parameterization
        return Task::where('status', $status)
            ->where('receiver_id', $userId)
            ->get();
    }
}
```

## Password Security

### Password Hashing
```php
// ✅ Good - User Registration
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;

class AuthService
{
    public function registerUser(array $userData): User
    {
        return User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']), // ✅ Proper hashing
            'user_type' => $userData['user_type'],
            'email_verified_at' => now()
        ]);
    }

    public function validateCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        
        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }
        
        return null;
    }
}
```

## CSRF Protection

### CSRF Middleware
```php
// ✅ Good - CSRF Middleware for forms
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyCsrfToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->is('api/*')) {
            // Skip CSRF for API routes (use JWT instead)
            return $next($request);
        }

        // Verify CSRF token for web routes
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ], 419);
        }

        return $next($request);
    }
}
```

## Rate Limiting

### API Rate Limiting
```php
// ✅ Good - Rate Limiting Middleware
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $key = 'api'): mixed
    {
        $key = $this->resolveRequestSignature($request, $key);
        
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 requests per minute
        
        return $next($request);
    }

    private function resolveRequestSignature(Request $request, string $key): string
    {
        return $key . '|' . $request->ip();
    }
}
```

## File Upload Security

### Secure File Handling
```php
// ✅ Good - File Upload Service
<?php
declare(strict_types=1);

namespace Modules\Task\app\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    private readonly array $allowedMimes = [
        'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'
    ];

    private readonly int $maxFileSize = 10240; // 10MB

    public function uploadTaskAttachment(UploadedFile $file, string $taskId): string
    {
        // Validate file
        $this->validateFile($file);
        
        // Generate secure filename
        $filename = $this->generateSecureFilename($file);
        
        // Store file in secure location
        $path = $file->storeAs(
            "tasks/{$taskId}/attachments",
            $filename,
            'private' // Store in private disk
        );
        
        return $path;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getClientOriginalExtension(), $this->allowedMimes)) {
            throw new InvalidFileTypeException('File type not allowed');
        }

        if ($file->getSize() > $this->maxFileSize * 1024) {
            throw new FileTooLargeException('File size exceeds limit');
        }

        // Additional security checks
        if ($this->containsMaliciousContent($file)) {
            throw new MaliciousFileException('File contains malicious content');
        }
    }

    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getContent() . time());
        
        return "{$hash}.{$extension}";
    }

    private function containsMaliciousContent(UploadedFile $file): bool
    {
        // Implement content scanning logic
        $content = file_get_contents($file->getPathname());
        
        // Check for common malicious patterns
        $maliciousPatterns = [
            '<?php',
            '<script',
            'javascript:',
            'eval(',
            'exec('
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (str_contains(strtolower($content), $pattern)) {
                return true;
            }
        }

        return false;
    }
}
```

## Security Headers

### Security Headers Middleware
```php
// ✅ Good - Security Headers
<?php
declare(strict_types=1);

namespace Modules\Auth\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Content Security Policy
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' https:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';"
        );

        return $response;
    }
}
```

## Implementation Guidelines

1. **Always validate and sanitize input**
2. **Use parameterized queries**
3. **Implement proper authentication**
4. **Use role-based access control**
5. **Hash passwords securely**
6. **Implement rate limiting**
7. **Validate file uploads**
8. **Use HTTPS everywhere**
9. **Set security headers**
10. **Log security events**
11. **Keep dependencies updated**
12. **Regular security audits**
