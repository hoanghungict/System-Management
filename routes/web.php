<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

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
