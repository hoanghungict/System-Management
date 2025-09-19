<?php

namespace Modules\Notifications\app\Services\KafkaService;

use Illuminate\Support\Facades\Log;
use \RdKafka\Conf;
use \RdKafka\Producer;

class KafkaTopicManager
{
    protected $producer;

    public function __construct()
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', config('kafka.brokers'));
        $conf->set('allow.auto.create.topics', 'true');
        
        $this->producer = new Producer($conf);
    }

    /**
     * Tạo tất cả topic từ config kafka.php
     */
    public function createTopicsFromConfig(): void
    {
        $topics = config('kafka.topics', []);
        
        Log::info('KafkaTopicManager: Bắt đầu kiểm tra và tạo topics từ config', [
            'topics' => $topics
        ]);

        foreach ($topics as $key => $topicName) {
            if ($this->topicExists($topicName)) {
                Log::info('KafkaTopicManager: Topic đã tồn tại', ['topic' => $topicName]);
            } else {
                Log::info('KafkaTopicManager: Topic chưa tồn tại, đang tạo', ['topic' => $topicName]);
                $this->createTopicIfNotExists($topicName);
            }
        }

        Log::info('KafkaTopicManager: Hoàn thành kiểm tra và tạo topics');
    }

    /**
     * Tạo topic nếu chưa tồn tại bằng cách gửi một message test
     */
    protected function createTopicIfNotExists(string $topicName): void
    {
        try {
            Log::info('KafkaTopicManager: Tạo topic', ['topic' => $topicName]);
            
            // Gửi một message test để trigger tạo topic
            $topic = $this->producer->newTopic($topicName);
            $topic->produce(0, 0, json_encode([
                'test' => true,
                'created_at' => now()->toISOString(),
                'topic' => $topicName
            ]), 'topic_init_' . time());
            
            // Flush để đảm bảo message được gửi
            $this->producer->flush(1000);
            
            Log::info('KafkaTopicManager: Topic đã được tạo', ['topic' => $topicName]);
            
        } catch (\Exception $e) {
            Log::warning('KafkaTopicManager: Lỗi khi tạo topic', [
                'topic' => $topicName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Kiểm tra topic có tồn tại không
     */
    public function topicExists(string $topicName): bool
    {
        try {
            $metadata = $this->producer->getMetadata(false, null, 5000);
            $topics = $metadata->getTopics();
            
            foreach ($topics as $topic) {
                if ($topic->getTopic() === $topicName) {
                    Log::debug('KafkaTopicManager: Topic tồn tại', ['topic' => $topicName]);
                    return true;
                }
            }
            
            Log::debug('KafkaTopicManager: Topic không tồn tại', ['topic' => $topicName]);
            return false;
        } catch (\Exception $e) {
            Log::warning('KafkaTopicManager: Lỗi khi kiểm tra topic, giả định topic chưa tồn tại', [
                'topic' => $topicName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Liệt kê tất cả topic hiện có
     */
    public function listAllTopics(): array
    {
        try {
            $metadata = $this->producer->getMetadata(false, null, 5000);
            $topics = $metadata->getTopics();
            
            $topicList = [];
            foreach ($topics as $topic) {
                $topicList[] = $topic->getTopic();
            }
            
            Log::info('KafkaTopicManager: Danh sách topics', ['topics' => $topicList]);
            return $topicList;
            
        } catch (\Exception $e) {
            Log::error('KafkaTopicManager: Lỗi khi liệt kê topics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Kiểm tra và tạo topic cụ thể
     */
    public function ensureTopicExists(string $topicName): bool
    {
        if ($this->topicExists($topicName)) {
            Log::info('KafkaTopicManager: Topic đã tồn tại', ['topic' => $topicName]);
            return true;
        }

        Log::info('KafkaTopicManager: Tạo topic mới', ['topic' => $topicName]);
        $this->createTopicIfNotExists($topicName);
        
        // Kiểm tra lại sau khi tạo
        return $this->topicExists($topicName);
    }
}
