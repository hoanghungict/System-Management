<?php

namespace Modules\Notifications\app\Services\KafkaService;

use Illuminate\Support\Facades\Log;
use \RdKafka\Conf;
use \RdKafka\Producer as RdKafkaProducer;

class KafkaProducerService
{
    /** @var \RdKafka\Producer */
    protected $producer;

    public function __construct()
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', config('kafka.brokers'));
        $this->producer = new RdKafkaProducer($conf);
    }

    /**
     * Send a JSON message to a topic.
     */
    public function send(string $topic, array $payload, ?string $key = null, array $headers = []): void
    {
        $topicHandle = $this->producer->newTopic($topic);

        $data = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($data === false) {
            throw new \InvalidArgumentException('Mã hóa JSON payload thất bại: ' . json_last_error_msg());
        }

        try {
            $topicHandle->producev(RD_KAFKA_PARTITION_UA, 0, $data, $key, $headers);
        } catch (\Throwable $e) {
            $topicHandle->produce(RD_KAFKA_PARTITION_UA, 0, $data, $key);
        }

        $this->producer->poll(0);

        $result = $this->producer->flush(10000);
        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            Log::warning('Flush producer Kafka không hoàn tất sạch sẽ', ['ket_qua' => $result]);
        }
    }
}


