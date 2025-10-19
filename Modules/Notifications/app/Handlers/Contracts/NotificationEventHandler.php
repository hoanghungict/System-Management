<?php

namespace Modules\Notifications\app\Handlers\Contracts;

interface NotificationEventHandler
{
    /**
     * Handle the notification event
     *
     * @param string $channel The channel/topic name
     * @param array $data The event data
     * @return void
     */
    public function handle(string $channel, array $data): void;
}
