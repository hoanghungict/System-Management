<?php

namespace Modules\Notifications\app\Services\KafkaService;

use Illuminate\Support\Facades\Log;

class KafkaRouterService
{
    /**
     * Resolve handler class by exact topic or wildcard pattern.
     */
    public function resolveHandler(string $topic): ?string
    {
        $config = (array) config('kafka_handle', []);
        $map = (array) ($config['handlers'] ?? $config); // Đọc từ 'handlers' key hoặc toàn bộ config

        Log::info('Router debug', [
            'topic' => $topic,
            'config' => $config,
            'map' => $map,
        ]);

        // Exact match
        if (isset($map[$topic])) {
            Log::info('Tìm thấy handler cho topic', ['handler' => $map[$topic]]);
            return $map[$topic];
        }

        // Wildcard match like task.*
        foreach ($map as $pattern => $class) {
            if ($this->topicMatchesPattern($topic, (string) $pattern)) {
                Log::info('Tìm thấy handler cho pattern', ['pattern' => $pattern, 'handler' => $class]);
                return $class;
            }
        }

        Log::warning('Không tìm thấy handler cho topic', ['topic' => $topic, 'available_handlers' => array_keys($map)]);
        return null;
    }

    protected function topicMatchesPattern(string $topic, string $pattern): bool
    {
        $regex = '/^' . str_replace(['*', '.'], ['.+', '\\.'], preg_quote($pattern, '/')) . '$/i';
        return (bool) preg_match($regex, $topic);
    }
}


