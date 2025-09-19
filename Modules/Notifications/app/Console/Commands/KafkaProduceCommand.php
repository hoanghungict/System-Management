<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;

class KafkaProduceCommand extends Command
{
    protected $signature = 'kafka:produce {topic} {--json=} {--key=}';

    protected $description = 'Gửi một message JSON tới Kafka topic';

    public function handle(KafkaProducerService $producer)
    {
        $topic = (string) $this->argument('topic');
        $json = (string) ($this->option('json') ?? '');
        $key = $this->option('key');

        if ($json === '') {
            $this->error('Vui lòng cung cấp --json payload');
            return self::FAILURE;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->error('JSON không hợp lệ: ' . json_last_error_msg());
            return self::FAILURE;
        }

        $producer->send($topic, $data, $key);
        $this->info('Đã gửi message tới topic: ' . $topic);
        return self::SUCCESS;
    }
}


