<?php

namespace Modules\Notifications\app\Handlers\Contracts;

interface NotificationEventHandler
{
    /**
     * Handle incoming event from message broker.
     *
     * @param string $channel Full channel name, e.g. task.assigned
     * @param array $data Decoded JSON payload
     */
    public function handle(string $channel, array $data): void;
}


