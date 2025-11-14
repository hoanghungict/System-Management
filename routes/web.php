<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Storage;

// DEBUG: Test route to check permissions
Route::get('/test-storage-debug', function () {
    $testPath = 'task-files/130/7U97oSHTShZPrl8M2C6ojfMYfzVJUiZyIsz6w90w.docx';
    $filePath = storage_path('app/public/' . $testPath);
    
    return response()->json([
        'file_path' => $filePath,
        'file_exists' => file_exists($filePath),
        'is_readable' => is_readable($filePath),
        'is_file' => is_file($filePath),
        'perms' => file_exists($filePath) ? substr(sprintf('%o', fileperms($filePath)), -4) : 'N/A',
        'owner' => file_exists($filePath) ? (function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($filePath))['name'] ?? 'unknown') : get_current_user()) : 'N/A',
        'php_user' => get_current_user(),
        'storage_path' => storage_path('app/public'),
        'storage_dir_readable' => is_readable(storage_path('app/public')),
    ]);
});

// DEBUG: Test route to see what Laravel receives
Route::get('/test-storage-route/{path}', function (string $path) {
    return response()->json([
        'route_path_param' => $path,
        'request_uri' => request()->server('REQUEST_URI', ''),
        'request_path' => request()->path(),
        'full_url' => request()->fullUrl(),
        'query_storage_path' => request()->get('storage_path'),
        'all_query' => request()->all(),
        'all_server_keys' => array_keys(request()->server->all()),
    ]);
})->where('path', '.*');

// Serve storage files with CORS headers - MUST be registered FIRST
// IMPORTANT: These routes must be registered early to catch /storage/* requests
// NOTE: NO middleware group - exclude from web middleware to avoid conflicts
Route::withoutMiddleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->group(function () {
    Route::get('/storage/{path}', function (string $path) {
        try {
            // Route parameter should already have the path from Laravel routing
            // But decode it in case it's URL encoded
            $path = urldecode($path);
            
            // Normalize path to prevent directory traversal
            $normalizedPath = str_replace('..', '', $path);
            $normalizedPath = ltrim($normalizedPath, '/');
            $filePath = storage_path('app/public/' . $normalizedPath);
            
            // Debug: Always log first
            \Log::info('Storage file access attempt', [
                'route_param' => $path,
                'normalized' => $normalizedPath,
                'full_path' => $filePath,
                'exists' => file_exists($filePath),
                'readable' => is_readable($filePath),
            ]);
            
            // Validate file exists
            if (!file_exists($filePath)) {
                \Log::warning('Storage file not found', ['path' => $filePath, 'request_path' => $path]);
                return response()->json(['error' => 'File not found', 'path' => $filePath], 404);
            }
            
            if (!is_file($filePath)) {
                \Log::warning('Storage path is not a file', ['path' => $filePath]);
                return response()->json(['error' => 'Not a file', 'path' => $filePath], 404);
            }
            
            // Try to make readable if not already
            if (!is_readable($filePath)) {
                @chmod($filePath, 0644);
                if (!is_readable($filePath)) {
                    $perms = substr(sprintf('%o', fileperms($filePath)), -4);
                    \Log::error('Storage file not readable', [
                        'path' => $filePath,
                        'perms' => $perms,
                    ]);
                    return response()->json([
                        'error' => 'File not readable',
                        'path' => $filePath,
                        'perms' => $perms,
                    ], 403);
                }
            }
            
            $allowedOrigins = [
                'http://localhost:3000',
                'http://localhost:3001',
                'http://127.0.0.1:3000',
                'http://127.0.0.1:3001',
                'http://localhost:5173',
                'http://127.0.0.1:5173',
            ];
            
            $origin = request()->header('Origin');
            $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : null;
            
            // Use Storage facade to serve file (more reliable with permissions)
            $relativePath = str_replace(storage_path('app/public/'), '', $filePath);
            
            \Log::info('Attempting to serve file', [
                'file_path' => $filePath,
                'relative_path' => $relativePath,
            ]);
            
            // Read file content directly and serve with proper CORS headers
            // This avoids any issues with StreamedResponse
            $fileContent = @file_get_contents($filePath);
            if ($fileContent === false) {
                \Log::error('Cannot read file content', ['path' => $filePath]);
                return response()->json(['error' => 'Cannot read file'], 500);
            }
            
            $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
            $fileName = basename($filePath);
            
            // Create response with file content
            $response = response($fileContent, 200);
            $response->headers->set('Content-Type', $mimeType);
            $response->headers->set('Content-Length', (string)strlen($fileContent));
            $response->headers->set('Content-Disposition', 'inline; filename="' . $fileName . '"');
            
            // Add CORS headers
            if ($allowedOrigin) {
                $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', '*');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Max-Age', '3600');
            }
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('Error serving storage file', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return JSON error instead of abort to avoid 403 page
            return response()->json([
                'error' => 'Error serving file',
                'message' => $e->getMessage(),
            ], 500);
        }
    })->where('path', '.*');
});

// Handle OPTIONS preflight for storage - separate group
Route::withoutMiddleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->group(function () {
    Route::options('/storage/{path}', function () {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];
        
        $origin = request()->header('Origin');
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : null;
        
        $response = response('', 200);
        
        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '3600');
        }
        
        return $response;
    })->where('path', '.*');
});

Route::get('/', function () {
    return view('welcome');
});

// Simple API test route
Route::get('/api-test', function () {
    return response()->json(['message' => 'Web API is working!']);
});

// Simple API POST test route
Route::post('/api-test-post', function () {
    return response()->json(['message' => 'Web API POST is working!', 'data' => request()->all()]);
});

// API Testing Tools Routes
Route::get('/api-testing', function () {
    return view('api-testing-index');
});

Route::get('/api-testing/common', function () {
    return response()->file(public_path('../api-common.html'));
});

Route::get('/api-testing/admin', function () {
    return response()->file(public_path('../api-admin.html'));
});

Route::get('/api-testing/student', function () {
    return response()->file(public_path('../api-student.html'));
});

Route::get('/api-testing/lecturer', function () {
    return response()->file(public_path('../api-lecturer.html'));
});

// Broadcasting auth routes (required for Reverb WebSocket authentication)
Broadcast::routes(['middleware' => ['web', 'jwt']]);

// Alternative API route for frontend - with JWT middleware
Route::post('/api/broadcasting/auth', function (Illuminate\Http\Request $request) {
    // Set a fake user for broadcasting auth
    // PusherBroadcaster needs an authenticated user
    $userId = $request->attributes->get('jwt_user_id');
    $userType = $request->attributes->get('jwt_user_type');
    
    if ($userId) {
        // Create a fake user object for broadcasting
        $user = new class($userId, $userType) {
            public $id;
            public $user_type;
            
            public function __construct($id, $type) {
                $this->id = $id;
                $this->user_type = $type;
            }
            
            public function getAuthIdentifier() {
                return $this->id;
            }
        };
        
        // Set the user for this request
        $request->setUserResolver(function() use ($user) {
            return $user;
        });
    }
    
    return Broadcast::auth($request);
})->middleware('jwt');