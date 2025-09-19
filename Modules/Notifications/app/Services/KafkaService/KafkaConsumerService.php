<?php

namespace Modules\Notifications\app\Services\KafkaService;

use Illuminate\Support\Facades\Log;
use \RdKafka\KafkaConsumer as RdKafkaConsumer;
use \RdKafka\Conf;
use Modules\Notifications\app\Services\KafkaService\KafkaRouterService;
use Modules\Notifications\app\Services\KafkaService\KafkaTopicManager;

class KafkaConsumerService
{
    protected $consumer;
    protected $router;
    public function __construct(KafkaRouterService $router)
    {
        $this->router = $router;
        $conf = new Conf();
        $conf->set('metadata.broker.list' , config('kafka.brokers'));
        $conf->set('group.id' , config('kafka.group_id'));
        $conf->set('enable.partition.eof', 'true');
        $conf->set('allow.auto.create.topics', 'true');
        $conf->set('metadata.max.age.ms', '10000'); // Refresh metadata every 10s

        $this->consumer = new RdKafkaConsumer($conf);
    }

    public function handle() {
        // Tạo topics từ config trước khi subscribe
        $this->createTopicsFromConfig();
        
        // Subscribe topics sau khi consumer đã khởi tạo
        $topics = array_values((array) config('kafka.topics'));
        if (!empty($topics)) {
            $this->consumer->subscribe($topics);
            // Đợi metadata update
            sleep(2);
        }

        while(true)
        {
            $message = $this->consumer->consume(120 * 1000);

            switch($message->err) {
                case \RD_KAFKA_RESP_ERR_NO_ERROR:
                    Log::info('Nhận được message từ Kafka', [
                        'topic' => $message->topic_name ?? 'unknown',
                        'partition' => $message->partition ?? 'unknown',
                        'offset' => $message->offset ?? 'unknown'
                    ]);
                    $this->processMessage($message);
                    break;
                case \RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // EOF partition, continue
                    break;
                case \RD_KAFKA_RESP_ERR__TIMED_OUT:
                    // timeout, continue
                    break;
                case \RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART:
                    Log::info('Topic chưa tồn tại, đang tạo...', ['topic' => $message->topic_name ?? 'unknown']);
                    // Đợi topic được tạo
                    sleep(3);
                    break;
                default:
                    // Error
                    throw new \Exception($message->errstr(), $message->err);
            }
        }
    }

    protected function processMessage($message){
        $rawPayload = (string) ($message->payload ?? '');
        $data = json_decode($rawPayload, true);
        if (!is_array($data)) {
            Log::warning('Giải mã JSON từ Kafka thất bại', [
                'topic' => $message->topic_name ?? null,
                'partition' => $message->partition ?? null,
                'offset' => $message->offset ?? null,
                'loi' => json_last_error_msg(),
            ]);
            return;
        }

        $topic = (string) ($message->topic_name ?? '');
        $meta = [
            'key' => $message->key ?? null,
            'partition' => $message->partition ?? null,
            'offset' => $message->offset ?? null,
            'timestamp' => $message->timestamp ?? null,
        ];

        $this->dispatchToHandler($topic, $data, $meta);
    }

    protected function dispatchToHandler(string $topic, array $data, array $meta = []): void
    {
        $handlerClass = $this->router->resolveHandler($topic);
        Log::info('dispatchToHandler: Tìm thấy handler cho topic', ['topic' => $topic, 'handler' => $handlerClass]);
        if (!$handlerClass) {
            Log::warning('Không tìm thấy handler phù hợp cho topic Kafka', ['topic' => $topic]);
            return;
        }
        Log::info('dispatchToHandler: Thực thi handler', ['topic' => $topic, 'handler' => $handlerClass]);
        try {
            $handler = app($handlerClass);
            $handler->handle($topic, $data);
            Log::info('dispatchToHandler: Thực thi handler thành công', ['topic' => $topic, 'handler' => $handlerClass]);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi thực thi handler Kafka', [
                'topic' => $topic,
                'loi' => $e->getMessage(),
            ]);
            Log::error('dispatchToHandler: Lỗi khi thực thi handler Kafka', ['topic' => $topic, 'handler' => $handlerClass, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Tạo topics từ config kafka.php
     */
    protected function createTopicsFromConfig(): void
    {
        try {
            $topicManager = app(KafkaTopicManager::class);
            $topicManager->createTopicsFromConfig();
        } catch (\Exception $e) {
            Log::error('KafkaConsumerService: Lỗi khi tạo topics từ config', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Pattern matching moved to KafkaRouterService
}
