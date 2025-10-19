<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\app\Services\KafkaService\KafkaTopicManager;

class CreateKafkaTopicsCommand extends Command
{
    protected $signature = 'kafka:create-topics';
    protected $description = 'Tạo tất cả Kafka topics từ config';

    public function handle(): int
    {
        $this->info('Tạo Kafka topics từ config...');

        try {
            $topicManager = app(KafkaTopicManager::class);
            $topicManager->createTopicsFromConfig();

            $this->info('Đã tạo thành công tất cả topics từ config.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Lỗi khi tạo topics: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

