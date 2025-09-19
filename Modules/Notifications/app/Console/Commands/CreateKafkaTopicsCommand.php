<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\app\Services\KafkaService\KafkaTopicManager;

class CreateKafkaTopicsCommand extends Command
{
    protected $signature = 'kafka:create-topics';
    protected $description = 'Tạo Kafka topics từ config kafka.php';

    public function handle(): int
    {
        $this->info('Kiểm tra và tạo Kafka topics từ config...');
        
        try {
            $topicManager = app(KafkaTopicManager::class);
            
            // Hiển thị danh sách topic hiện có
            $existingTopics = $topicManager->listAllTopics();
            $this->info('Topics hiện có: ' . implode(', ', $existingTopics));
            
            // Tạo topics từ config
            $topicManager->createTopicsFromConfig();
            
            // Hiển thị danh sách topic sau khi tạo
            $topicsAfter = $topicManager->listAllTopics();
            $this->info('Topics sau khi tạo: ' . implode(', ', $topicsAfter));
            
            $this->info('Hoàn thành kiểm tra và tạo topics!');
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Lỗi khi tạo topics: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
