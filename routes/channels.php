<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Authorize private notification channels with JWT
Broadcast::channel('notifications.user.{userId}', function ($user, $userId) {
    // Cho phép nếu user đã xác thực và id khớp
    return (int) ($user->id ?? 0) === (int) $userId;
});
