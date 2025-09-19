<<<<<<< HEAD
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


=======
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


>>>>>>> bd1641df13c4d5c20a66cd48866ad74131db6dc4
