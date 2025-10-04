<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\app\Services\KafkaService\KafkaTopicManager;

class ListKafkaTopicsCommand extends Command
{
    protected $signature = 'kafka:list-topics';
    protected $description = 'Liệt kê tất cả Kafka topics';

    public function handle(): int
    {
        $this->info('Liệt kê Kafka topics...');
        
        try {
            $topicManager = app(KafkaTopicManager::class);
            $topics = $topicManager->listAllTopics();
            
            if (empty($topics)) {
                $this->warn('Không có topic nào được tìm thấy.');
            } else {
                $this->info('Danh sách topics:');
                foreach ($topics as $topic) {
                    $this->line("- $topic");
                }
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Lỗi khi liệt kê topics: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

