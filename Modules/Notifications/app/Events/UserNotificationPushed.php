<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationPushed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $userType;
    public string $content;
    public array $data;
    public string $timestamp;
    public string $channelName;

    public function __construct(int $userId, string $userType, string $content, array $data = [])
    {
        $this->userId = $userId;
        $this->userType = $userType;
        $this->content = $content;
        $this->data = $data;
        $this->timestamp = now()->toISOString();
        $this->channelName = "notifications.user.{$userId}";
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->channelName);
    }

    public function broadcastAs(): string
    {
        return 'user.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_type' => $this->userType,
            'content' => $this->content,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
            'type' => 'push'
        ];
    }
}
