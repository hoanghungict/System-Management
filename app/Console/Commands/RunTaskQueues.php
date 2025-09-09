<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Command chạy queue worker cho Task module
 * 
 * Command này khởi động các queue workers cho các loại job khác nhau của Task module
 */
class RunTaskQueues extends Command
{
    /**
     * Tên command
     *
     * @var string
     */
    protected $signature = 'task:queues {--daemon : Chạy ở chế độ daemon}';

    /**
     * Mô tả command
     *
     * @var string
     */
    protected $description = 'Chạy queue workers cho Task module';

    /**
     * Thực thi command
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Khởi động Task Queue Workers...');
        
        $daemon = $this->option('daemon');
        $daemonFlag = $daemon ? '--daemon' : '';
        
        // Chạy high priority queue
        $this->info('📈 Khởi động High Priority Queue...');
        $this->runQueueWorker('high', $daemonFlag);
        
        // Chạy files processing queue
        $this->info('📁 Khởi động Files Processing Queue...');
        $this->runQueueWorker('files', $daemonFlag);
        
        // Chạy reports queue
        $this->info('📊 Khởi động Reports Queue...');
        $this->runQueueWorker('reports', $daemonFlag);
        
        // Chạy sync queue
        $this->info('🔄 Khởi động Sync Queue...');
        $this->runQueueWorker('sync', $daemonFlag);
        
        // Chạy cleanup queue
        $this->info('🧹 Khởi động Cleanup Queue...');
        $this->runQueueWorker('cleanup', $daemonFlag);
        
        $this->info('✅ Tất cả Task Queue Workers đã được khởi động!');
        
        return 0;
    }
    
    /**
     * Chạy queue worker cho một queue cụ thể
     * 
     * @param string $queue
     * @param string $daemonFlag
     * @return void
     */
    protected function runQueueWorker(string $queue, string $daemonFlag): void
    {
        $command = "queue:work database --queue={$queue} --tries=3 --timeout=300 {$daemonFlag}";
        
        if ($daemonFlag) {
            // Chạy ở background nếu là daemon mode
            $this->info("   Chạy: {$command}");
            Artisan::call($command);
        } else {
            // Chạy ở foreground
            $this->info("   Chạy: {$command}");
            Artisan::call($command);
        }
    }
}
