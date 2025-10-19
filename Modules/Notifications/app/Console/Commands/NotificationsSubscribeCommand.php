<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NotificationsSubscribeCommand extends Command
{
    protected $signature = 'notifications:subscribe {--patterns=* : Redis channels/patterns to psubscribe}';

    protected $description = 'Subscribe to Redis pub/sub patterns and dispatch notification handlers';

    public function handle(): int
    {
        $patterns = $this->option('patterns');
        if (empty($patterns)) {
            $patterns = config('notifications-events.patterns', []);
        }

        if (empty($patterns)) {
            $this->error('No patterns configured. Set notifications-events.patterns or pass --patterns');
            return self::FAILURE;
        }

        $this->info('Subscribing to patterns: ' . implode(', ', $patterns));

        Redis::psubscribe($patterns, function (string $message, string $channel) {
            try {
                $payload = json_decode($message, true) ?? [];
                $this->dispatchToHandler($channel, $payload);
            } catch (\Throwable $e) {
                Log::error('Notifications subscriber error', [
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return self::SUCCESS;
    }

    private function dispatchToHandler(string $channel, array $data): void
    {
        $map = config('notifications-events.handlers', []);

        $handlerClass = $map[$channel] ?? null;
        if (!$handlerClass) {
            // Try wildcard matches like task.*
            foreach ($map as $pattern => $class) {
                if ($this->matchWildcard($pattern, $channel)) {
                    $handlerClass = $class;
                    break;
                }
            }
        }

        if (!$handlerClass) {
            Log::info('No handler matched for channel', ['channel' => $channel]);
            return;
        }

        $handler = app($handlerClass);
        if (method_exists($handler, 'handle')) {
            $handler->handle($channel, $data);
        }
    }

    private function matchWildcard(string $pattern, string $value): bool
    {
        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/';
        return (bool) preg_match($regex, $value);
    }
}
