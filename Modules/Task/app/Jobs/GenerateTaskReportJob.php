<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Task\app\Services\ReportService;

/**
 * Job tạo báo cáo Task
 * 
 * Job này xử lý việc tạo các loại báo cáo khác nhau cho Task system
 */
class GenerateTaskReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần retry tối đa
     */
    public $tries = 3;

    /**
     * Timeout cho job (giây)
     */
    public $timeout = 600;

    /**
     * Loại báo cáo cần tạo
     */
    protected $reportType;

    /**
     * Tham số cho báo cáo
     */
    protected $reportParams;

    /**
     * Danh sách người nhận báo cáo
     */
    protected $recipients;

    /**
     * Khởi tạo job
     * 
     * @param string $reportType Loại báo cáo
     * @param array $reportParams Tham số báo cáo
     * @param array $recipients Danh sách người nhận
     */
    public function __construct(string $reportType = 'daily', array $reportParams = [], array $recipients = [])
    {
        $this->reportType = $reportType;
        $this->reportParams = $reportParams;
        $this->recipients = $recipients;
    }

    /**
     * Thực thi job
     * 
     * @param ReportService $reportService
     * @return void
     */
    public function handle(ReportService $reportService): void
    {
        try {
            Log::info('GenerateTaskReportJob started', [
                'report_type' => $this->reportType,
                'recipients_count' => count($this->recipients)
            ]);

            $report = null;

            switch ($this->reportType) {
                case 'daily':
                    $report = $this->generateDailyReport($reportService);
                    break;
                    
                case 'weekly':
                    $report = $this->generateWeeklyReport($reportService);
                    break;
                    
                case 'monthly':
                    $report = $this->generateMonthlyReport($reportService);
                    break;
                    
                case 'custom':
                    $report = $this->generateCustomReport($reportService);
                    break;
                    
                case 'performance':
                    $report = $this->generatePerformanceReport($reportService);
                    break;
                    
                case 'analytics':
                    $report = $this->generateAnalyticsReport($reportService);
                    break;
                    
                default:
                    $report = $this->generateDefaultReport($reportService);
                    break;
            }

            // Gửi báo cáo qua email nếu có recipients
            if ($report && !empty($this->recipients)) {
                $this->sendReportByEmail($reportService, $report);
            }

            Log::info('GenerateTaskReportJob completed successfully', [
                'report_type' => $this->reportType,
                'report_generated' => $report !== null,
                'recipients_count' => count($this->recipients)
            ]);

        } catch (\Exception $e) {
            Log::error('GenerateTaskReportJob failed', [
                'report_type' => $this->reportType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Tạo báo cáo hàng ngày
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generateDailyReport(ReportService $reportService): ?array
    {
        Log::info('Generating daily task report');
        
        $report = $reportService->generateDailyReport($this->reportParams);
        
        if ($report) {
            Log::info('Daily report generated successfully', [
                'total_tasks' => $report['total_tasks'] ?? 0,
                'completed_tasks' => $report['completed_tasks'] ?? 0
            ]);
        }
        
        return $report;
    }

    /**
     * Tạo báo cáo hàng tuần
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generateWeeklyReport(ReportService $reportService): ?array
    {
        Log::info('Generating weekly task report');
        
        $report = $reportService->generateWeeklyReport($this->reportParams);
        
        if ($report) {
            Log::info('Weekly report generated successfully', [
                'total_tasks' => $report['total_tasks'] ?? 0,
                'completed_tasks' => $report['completed_tasks'] ?? 0
            ]);
        }
        
        return $report;
    }

    /**
     * Tạo báo cáo hàng tháng
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generateMonthlyReport(ReportService $reportService): ?array
    {
        Log::info('Generating monthly task report');
        
        $report = $reportService->generateMonthlyReport($this->reportParams);
        
        if ($report) {
            Log::info('Monthly report generated successfully', [
                'total_tasks' => $report['total_tasks'] ?? 0,
                'completed_tasks' => $report['completed_tasks'] ?? 0
            ]);
        }
        
        return $report;
    }

    /**
     * Tạo báo cáo custom
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generateCustomReport(ReportService $reportService): ?array
    {
        Log::info('Generating custom task report', $this->reportParams);
        
        $report = $reportService->generateCustomReport($this->reportParams);
        
        if ($report) {
            Log::info('Custom report generated successfully');
        }
        
        return $report;
    }

    /**
     * Tạo báo cáo hiệu suất
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generatePerformanceReport(ReportService $reportService): ?array
    {
        Log::info('Generating performance task report');
        
        $report = $reportService->generatePerformanceReport($this->reportParams);
        
        if ($report) {
            Log::info('Performance report generated successfully');
        }
        
        return $report;
    }

    /**
     * Tạo báo cáo phân tích
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generateAnalyticsReport(ReportService $reportService): ?array
    {
        Log::info('Generating analytics task report');
        
        $report = $reportService->generateAnalyticsReport($this->reportParams);
        
        if ($report) {
            Log::info('Analytics report generated successfully');
        }
        
        return $report;
    }

    /**
     * Tạo báo cáo mặc định
     * 
     * @param ReportService $reportService
     * @return array|null
     */
    protected function generateDefaultReport(ReportService $reportService): ?array
    {
        Log::info('Generating default task report');
        
        // Tạo báo cáo tổng hợp
        $report = $reportService->generateDailyReport($this->reportParams);
        
        if ($report) {
            Log::info('Default report generated successfully');
        }
        
        return $report;
    }

    /**
     * Gửi báo cáo qua email
     * 
     * @param ReportService $reportService
     * @param array $report
     * @return void
     */
    protected function sendReportByEmail(ReportService $reportService, array $report): void
    {
        Log::info('Sending report by email', [
            'recipients_count' => count($this->recipients),
            'report_type' => $this->reportType
        ]);

        try {
            $emailSent = $reportService->sendReportByEmail($report, $this->recipients);
            
            if ($emailSent) {
                Log::info('Report sent by email successfully', [
                    'recipients_count' => count($this->recipients)
                ]);
            } else {
                Log::warning('Failed to send report by email');
            }
        } catch (\Exception $e) {
            Log::error('Error sending report by email', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xử lý khi job fail
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateTaskReportJob failed permanently', [
            'report_type' => $this->reportType,
            'recipients_count' => count($this->recipients),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Gửi notification cho admin về job fail
        // Có thể schedule lại job nếu cần
    }
}
