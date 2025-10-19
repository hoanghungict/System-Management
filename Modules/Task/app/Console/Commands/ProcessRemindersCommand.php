<?php

declare(strict_types=1);

namespace Modules\Task\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Task\app\Services\ReminderService;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Illuminate\Support\Facades\Log;

/**
 * Process Reminders Command
 * 
 * Command để xử lý reminders đến hạn và gửi notifications
 */
class ProcessRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reminders:process 
                            {--dry-run : Chạy thử không gửi notification thực tế}
                            {--limit=100 : Giới hạn số reminders xử lý mỗi lần}';

    /**
     * The console command description.
     */
    protected $description = 'Process due reminders and send notifications';

    public function __construct(
        private readonly ReminderService $reminderService,
        private readonly KafkaProducerService $kafkaProducer
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting reminder processing...');

        try {
            $isDryRun = $this->option('dry-run');
            $limit = (int) $this->option('limit');

            if ($isDryRun) {
                $this->warn('🧪 Running in DRY-RUN mode - no notifications will be sent');
            }

            // Get due reminders
            $dueReminders = $this->reminderService->getDueReminders();
            $totalDue = $dueReminders->count();

            if ($totalDue === 0) {
                $this->info('✅ No due reminders found');
                return Command::SUCCESS;
            }

            $this->info("📋 Found {$totalDue} due reminders");

            // Process reminders in batches
            $processedCount = 0;
            $successCount = 0;
            $errorCount = 0;

            foreach ($dueReminders->take($limit) as $reminder) {
                try {
                    $this->processReminder($reminder, $isDryRun);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("❌ Failed to process reminder {$reminder->id}: {$e->getMessage()}");
                    Log::error('ProcessRemindersCommand: Failed to process reminder', [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $processedCount++;

                // Show progress
                if ($processedCount % 10 === 0) {
                    $this->info("📊 Processed {$processedCount}/{$totalDue} reminders...");
                }
            }

            // Summary
            $this->info('📊 Processing Summary:');
            $this->info("   Total found: {$totalDue}");
            $this->info("   Processed: {$processedCount}");
            $this->info("   Success: {$successCount}");
            $this->info("   Errors: {$errorCount}");

            if ($isDryRun) {
                $this->warn('🧪 DRY-RUN completed - no actual notifications were sent');
            } else {
                $this->info('✅ Reminder processing completed');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Command failed: {$e->getMessage()}");
            Log::error('ProcessRemindersCommand: Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process a single reminder
     */
    private function processReminder($reminder, bool $isDryRun): void
    {
        $this->line("🔄 Processing reminder {$reminder->id} for user {$reminder->user_id}");

        if ($isDryRun) {
            $this->line("   📧 Would send {$reminder->reminder_type} notification");
            $this->line("   📝 Message: {$reminder->message}");
            return;
        }

        // Prepare event data for Kafka
        $eventData = [
            'reminder_id' => $reminder->id,
            'user_id' => $reminder->user_id,
            'user_type' => $reminder->user_type,
            'task_id' => $reminder->task_id,
            'reminder_type' => $reminder->reminder_type,
            'message' => $reminder->message,
            'reminder_time' => $reminder->reminder_time->toISOString(),
            'task_name' => $reminder->task->title ?? 'Task',
            'task_description' => $reminder->task->description ?? '',
            'deadline' => $reminder->task->deadline?->toISOString() ?? '',
            'user_name' => $reminder->user->name ?? 'User',
            'task_url' => config('app.url') . "/tasks/{$reminder->task_id}",
            'sender_id' => $reminder->task->creator_id ?? null,
            'sender_type' => $reminder->task->creator_type ?? 'system'
        ];

        // Determine event topic based on reminder type and timing
        $topic = $this->getEventTopic($reminder);

        // Publish event to Kafka
        $this->kafkaProducer->send($topic, $eventData);

        // Mark reminder as sent
        $reminder->markAsSent();

        $this->line("   ✅ Published event '{$topic}' and marked as sent");
    }

    /**
     * Get event topic based on reminder type and timing
     */
    private function getEventTopic($reminder): string
    {
        $deadline = $reminder->task->deadline;
        
        if ($deadline && $deadline->isPast()) {
            return 'reminder.task.overdue';
        }

        return 'reminder.task.deadline';
    }
}
