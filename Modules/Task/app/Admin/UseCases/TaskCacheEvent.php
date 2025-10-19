<?php

declare(strict_types=1);

namespace Modules\Task\app\Admin\UseCases;

/**
 * Simple concrete cache event for task operations
 */
class TaskCacheEvent
{
    public function __construct(
        public string $key,
        public array $metadata = []
    ) {}
}
