<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\app\Services\KafkaService\KafkaConsumerService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class KafkaConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Tên và chữ ký của lệnh bảng điều khiển.
     */
    protected $signature = 'kafka:consume';

    /**
     * The console command description.
     * Mô tả lệnh bảng điều khiển.
     */
    protected $description = 'Kafka Consume Command';

    /**
     * Create a new command instance.
     * Khởi tạo Contructer mới.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * Thực thi lệnh bảng điều khiển.
     */
    public function handle(KafkaConsumerService $consumer) {
        $this->info("Bắt đầu chạy Kafka consumer...");
        $consumer->handle();
        return self::SUCCESS;
    }

    /**
     * Get the console command arguments.
     * Lấy các đối số lệnh bảng điều khiển.
     */
    protected function getArguments(): array
    {
        return [];
    }

    /**
     * Get the console command options.
     * Lấy các tùy chọn lệnh bảng điều khiển.
     */
    protected function getOptions(): array
    {
        return [];
    }
}
