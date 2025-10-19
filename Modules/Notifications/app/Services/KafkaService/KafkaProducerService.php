
<?php

namespace Modules\Notifications\app\Services\KafkaService;

use Illuminate\Support\Facades\Log;
use \RdKafka\Conf;
use \RdKafka\Producer as RdKafkaProducer;
use \RdKafka\ProducerTopic;

class KafkaProducerService
{
    /** @var \RdKafka\Producer */
    protected $producer;

    public function __construct()
    {
        try {
            // Temporarily disable Kafka for development
            if (class_exists('RdKafka\Conf')) {
                $conf = new Conf();
                $conf->set('metadata.broker.list', config('kafka.brokers', 'localhost:9092'));
                $this->producer = new RdKafkaProducer($conf);
            } else {
                Log::warning('RdKafka not available, using null producer');
                $this->producer = null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to initialize Kafka producer', ['error' => $e->getMessage()]);
            // Fallback to null producer for development
            $this->producer = null;
        }
    }

    /**
     * Send a JSON message to a topic.
     * 
     * @param string $topic
     * @param array $payload
     * @param string|null $key
     * @param array $headers
     * @return void
     */
    public function send(string $topic, array $payload, ?string $key = null, array $headers = []): void
    {
        if ($this->producer === null) {
            Log::warning('Kafka producer not available, skipping message send');
            return;
        }

        try {
            $topicProducer = $this->producer->newTopic($topic);
            
            $message = json_encode($payload, JSON_UNESCAPED_UNICODE);
            
            $topicProducer->produce(RD_KAFKA_PARTITION_UA, 0, $message, $key);
            
            // Flush messages
            $this->producer->poll(0);
            
            $result = $this->producer->flush(1000);
            
            if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                Log::error('Kafka producer flush failed', ['result' => $result]);
            }
            
            Log::info('Message sent to Kafka topic', [
                'topic' => $topic,
                'key' => $key,
                'payload_size' => strlen($message)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send message to Kafka', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            // Don't throw in production to avoid breaking the application
        }
    }

    /**
     * Send multiple messages to a topic
     * 
     * @param string $topic
     * @param array $messages
     * @return void
     */
    public function sendBatch(string $topic, array $messages): void
    {
        if ($this->producer === null) {
            Log::warning('Kafka producer not available, skipping batch send');
            return;
        }

        try {
            $topicProducer = $this->producer->newTopic($topic);
            
            foreach ($messages as $message) {
                $payload = $message['payload'] ?? $message;
                $key = $message['key'] ?? null;
                
                $jsonMessage = json_encode($payload, JSON_UNESCAPED_UNICODE);
                $topicProducer->produce(RD_KAFKA_PARTITION_UA, 0, $jsonMessage, $key);
            }
            
            // Flush all messages
            $this->producer->poll(0);
            $result = $this->producer->flush(5000);
            
            if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                Log::error('Kafka producer batch flush failed', ['result' => $result]);
            }
            
            Log::info('Batch messages sent to Kafka topic', [
                'topic' => $topic,
                'count' => count($messages)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send batch messages to Kafka', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'message_count' => count($messages)
            ]);
        }
    }
}