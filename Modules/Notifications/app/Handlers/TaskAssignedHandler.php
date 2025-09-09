<?php

namespace Modules\Notifications\app\Handlers;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Events\UserNotificationPushed;

class TaskAssignedHandler implements NotificationEventHandler
{
    public function handle(string $channel, array $data): void
    {
        // Expecting: user_id, task_name, ...
        if (!isset($data['user_id'])) {
            return;
        }

        $content = isset($data['task_name'])
            ? "Bạn vừa được giao công việc: {$data['task_name']}"
            : 'Bạn vừa được giao một công việc mới';

        broadcast(new UserNotificationPushed(
            (int) $data['user_id'],
            $data['user_type'] ?? 'user',
            $content,
            $data
        ));
    }
}


