<?php

namespace Modules\Notifications\app\Services\KafkaService;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Services\KafkaService\KafkaRouterService;
use Modules\Notifications\app\Services\KafkaService\KafkaTopicManager;

// Định nghĩa các constants cho Kafka nếu chưa được define
if (!defined('RD_KAFKA_RESP_ERR_NO_ERROR')) {
    define('RD_KAFKA_RESP_ERR_NO_ERROR', 0);
}
if (!defined('RD_KAFKA_RESP_ERR__PARTITION_EOF')) {
    define('RD_KAFKA_RESP_ERR__PARTITION_EOF', -191);
}
if (!defined('RD_KAFKA_RESP_ERR__TIMED_OUT')) {
    define('RD_KAFKA_RESP_ERR__TIMED_OUT', -185);
}
if (!defined('RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART')) {
    define('RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART', 3);
}

/**
 * KafkaConsumerService - Xử lý việc lắng nghe và tiêu thụ messages từ Kafka
 */
class KafkaConsumerService
{
    protected $consumer;
    protected $router;
    protected $isRunning = false;
    
    public function __construct(KafkaRouterService $router)
    {
        $this->router = $router;
        $this->initializeConsumer();
    }
    
    /**
     * Khởi tạo Kafka Consumer với cấu hình
     */
    protected function initializeConsumer(): void
    {
        try {
            // Kiểm tra extension rdkafka có được cài đặt không
            if (!extension_loaded('rdkafka')) {
                Log::warning('KafkaConsumerService: Extension rdkafka chưa được cài đặt, chạy ở chế độ fallback');
                $this->consumer = null;
                return;
            }
            
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', config('kafka.brokers', 'localhost:9092'));
            $conf->set('group.id', config('kafka.group_id', 'default-group'));
            $conf->set('enable.partition.eof', 'true');
            $conf->set('allow.auto.create.topics', 'true');
            $conf->set('metadata.max.age.ms', '10000'); // Refresh metadata every 10s
            $conf->set('auto.offset.reset', 'earliest');
            $conf->set('enable.auto.commit', 'false');
            $conf->set('auto.commit.interval.ms', '1000');
            $conf->set('session.timeout.ms', '30000');
            $conf->set('heartbeat.interval.ms', '10000');
            
            $this->consumer = new \RdKafka\KafkaConsumer($conf);
            Log::info('KafkaConsumerService: Khởi tạo consumer thành công');
            
        } catch (\Exception $e) {
            Log::error('KafkaConsumerService: Lỗi khởi tạo consumer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->consumer = null;
        }
    }

    /**
     * Bắt đầu lắng nghe messages từ Kafka
     */
    public function handle(): void
    {
        if ($this->consumer === null) {
            Log::warning('KafkaConsumerService: Consumer không được khởi tạo, bỏ qua việc lắng nghe');
            return;
        }
        
        try {
            $this->isRunning = true;
            
            // Tạo topics từ config trước khi subscribe
            $this->createTopicsFromConfig();
            
            // Subscribe topics sau khi consumer đã khởi tạo
            $topics = array_values((array) config('kafka.topics', []));
            if (empty($topics)) {
                Log::warning('KafkaConsumerService: Không có topics để subscribe');
                return;
            }
            
            // Log::info('KafkaConsumerService: Đang subscribe vào topics', ['topics' => $topics]);
            $this->consumer->subscribe($topics);
            
            // Đợi metadata update
            sleep(2);
            
            // Log::info('KafkaConsumerService: Bắt đầu lắng nghe messages...');
            
            while ($this->isRunning) {
                $message = $this->consumer->consume(120 * 1000); // 2 minutes timeout
                
                if (!$message) {
                    continue;
                }
                
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        /* Log::info('KafkaConsumerService: Nhận được message', [ 
                            'topic' => $message->topic_name ?? 'unknown', 
                            'partition' => $message->partition ?? 'unknown', 
                            'offset' => $message->offset ?? 'unknown', 
                            'key' => $message->key ?? null 
                        ]); */
                        $this->processMessage($message);

                        //Commit offset sau khi xử lý thành công
                        $this->consumer->commit($message);

                        /* Log::debug('KafkaConsumerService: Commit offset thành công', [
                            'topic' => $message->topic_name,
                            'partition' => $message->partition,
                            'offset' => $message->offset
                        ]); */
                        break;
                        
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        // EOF partition, continue
                        /* Log::debug('KafkaConsumerService: Đạt EOF partition', [
                            'topic' => $message->topic_name ?? 'unknown',
                            'partition' => $message->partition ?? 'unknown'
                        ]); */
                        break;
                        
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        // timeout, continue
                        // Log::debug('KafkaConsumerService: Timeout khi consume message');
                        break;
                        
                    case RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART:
                        Log::warning('KafkaConsumerService: Topic chưa tồn tại', [
                            'topic' => $message->topic_name ?? 'unknown'
                        ]);
                        // Đợi topic được tạo
                        sleep(3);
                        break;
                        
                    default:
                        Log::error('KafkaConsumerService: Lỗi khi consume message', [
                            'error_code' => $message->err,
                            'error_message' => $message->errstr() ?? 'Unknown error',
                            'topic' => $message->topic_name ?? 'unknown'
                        ]);
                        // Không throw exception để consumer tiếp tục chạy
                        break;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('KafkaConsumerService: Lỗi nghiêm trọng trong handle', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Xử lý message từ Kafka
     */
    protected function processMessage($message): void
    {
        try {
            $rawPayload = (string) ($message->payload ?? '');
            
            if (empty($rawPayload)) {
                Log::warning('KafkaConsumerService: Message payload trống', [
                    'topic' => $message->topic_name ?? null,
                    'partition' => $message->partition ?? null,
                    'offset' => $message->offset ?? null
                ]);
                return;
            }
            
            // Đảm bảo encoding UTF-8
            if (!mb_check_encoding($rawPayload, 'UTF-8')) {
                $rawPayload = mb_convert_encoding($rawPayload, 'UTF-8', 'auto');
            }
            
            $data = json_decode($rawPayload, true);
            
            if (!is_array($data)) {
                Log::warning('KafkaConsumerService: Giải mã JSON thất bại', [
                    'topic' => $message->topic_name ?? null,
                    'partition' => $message->partition ?? null,
                    'offset' => $message->offset ?? null,
                    'json_error' => json_last_error_msg(),
                    'payload_preview' => substr($rawPayload, 0, 200)
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
            
            /* Log::info('KafkaConsumerService: Bắt đầu xử lý message', [
                'topic' => $topic,
                'data_keys' => array_keys($data),
                'meta' => $meta
            ]); */
            
            $this->dispatchToHandler($topic, $data, $meta);
            
        } catch (\Exception $e) {
            Log::error('KafkaConsumerService: Lỗi khi xử lý message', [
                'topic' => $message->topic_name ?? null,
                'partition' => $message->partition ?? null,
                'offset' => $message->offset ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Dispatch message đến handler tương ứng
     */
    protected function dispatchToHandler(string $topic, array $data, array $meta = []): void
    {
        try {
            $handlerClass = $this->router->resolveHandler($topic);
            
            if (!$handlerClass) {
                Log::warning('KafkaConsumerService: Không tìm thấy handler cho topic', [
                    'topic' => $topic
                ]);
                return;
            }
            
            /* Log::info('KafkaConsumerService: Tìm thấy handler cho topic', [
                'topic' => $topic, 
                'handler' => $handlerClass
            ]); */
            
            // Kiểm tra handler class tồn tại
            if (!class_exists($handlerClass)) {
                Log::error('KafkaConsumerService: Handler class không tồn tại', [
                    'topic' => $topic,
                    'handler' => $handlerClass
                ]);
                return;
            }
            
            $handler = app($handlerClass);
            
            // Kiểm tra handler có method handle
            if (!method_exists($handler, 'handle')) {
                Log::error('KafkaConsumerService: Handler không có method handle', [
                    'topic' => $topic,
                    'handler' => $handlerClass
                ]);
                return;
            }
            
            /* Log::info('KafkaConsumerService: Bắt đầu thực thi handler', [
                'topic' => $topic, 
                'handler' => $handlerClass
            ]); */
            
            $handler->handle($topic, $data, $meta);
            
            /* Log::info('KafkaConsumerService: Thực thi handler thành công', [
                'topic' => $topic, 
                'handler' => $handlerClass
            ]); */
            
        } catch (\Throwable $e) {
            Log::error('KafkaConsumerService: Lỗi khi thực thi handler', [
                'topic' => $topic,
                'handler' => $handlerClass ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Tạo topics từ config kafka.php
     */
    protected function createTopicsFromConfig(): void
    {
        try {
            // Log::info('KafkaConsumerService: Bắt đầu tạo topics từ config');
            $topicManager = app(KafkaTopicManager::class);
            $topicManager->createTopicsFromConfig();
            // Log::info('KafkaConsumerService: Hoàn thành tạo topics từ config');
        } catch (\Exception $e) {
            Log::error('KafkaConsumerService: Lỗi khi tạo topics từ config', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    /**
     * Dừng consumer
     */
    public function stop(): void
    {
        $this->isRunning = false;
        // Log::info('KafkaConsumerService: Đã nhận lệnh dừng consumer');
    }
    
    /**
     * Kiểm tra consumer có đang chạy không
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }
    
    /**
     * Cleanup resources
     */
    protected function cleanup(): void
    {
        try {
            if ($this->consumer) {
                $this->consumer->close();
                // Log::info('KafkaConsumerService: Đã đóng consumer');
            }
        } catch (\Exception $e) {
            Log::error('KafkaConsumerService: Lỗi khi cleanup', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Destructor để cleanup
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
