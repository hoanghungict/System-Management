<?php

use Illuminate\Support\Facades\Route;

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

// Task Module API Documentation
Route::get('/api/doc', function () {
    return view('task-api-docs');
});
