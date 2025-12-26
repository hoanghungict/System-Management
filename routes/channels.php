<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Authorize private notification channels with JWT
Broadcast::channel('notifications.user.{userId}', function ($user, $userId) {
    // Debug: Log authorization attempt
    Log::info('Channel authorization attempt', [
        'user' => $user,
        'user_id' => $user->id ?? null,
        'target_userId' => $userId,
    ]);
    
    // Check if user is authenticated and matches the channel user ID
    if ($user && isset($user->id)) {
        $authorized = (int) $user->id === (int) $userId;
        Log::info('Channel authorization result', [
            'user_id' => $user->id,
            'target_userId' => $userId,
            'authorized' => $authorized
        ]);
        return $authorized;
    }
    
    // Log failure
    Log::warning('Channel authorization failed - no user', [
        'user' => $user,
        'target_userId' => $userId,
    ]);
    
    return false;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) ($user->id ?? 0) === (int) $id;
});

// Authorize private import job channels
Broadcast::channel('import-job.{importJobId}', function ($user, $importJobId) {
    if (!$user || !isset($user->id)) {
        return false;
    }
    
    // Check if user is the owner of the import job
    $importJob = \Modules\Auth\app\Models\ImportJob::find($importJobId);
    
    if (!$importJob) {
        return false;
    }
    
    return (int) $user->id === (int) $importJob->user_id;
});
