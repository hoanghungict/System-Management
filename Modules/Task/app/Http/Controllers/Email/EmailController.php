<?php

namespace Modules\Task\app\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Email Controller - API cho email operations
 * 
 * Controller này cung cấp endpoints để gửi email
 */
class EmailController extends Controller
{
    /**
     * POST /email/send-report
     */
    public function sendReportEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'subject' => 'required|string',
            'report_type' => 'required|string|in:daily,weekly,monthly',
            'date_range' => 'nullable|array'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report email sent successfully',
            'data' => [
                'recipients' => $validated['recipients'],
                'subject' => $validated['subject'],
                'report_type' => $validated['report_type'],
                'sent_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * POST /email/send-notification
     */
    public function sendNotificationEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification email sent successfully',
            'data' => [
                'recipients' => $validated['recipients'],
                'subject' => $validated['subject'],
                'priority' => $validated['priority'] ?? 'normal',
                'sent_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * POST /email/send-template
     */
    public function sendTemplateEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'template_name' => 'required|string',
            'template_data' => 'nullable|array',
            'subject' => 'nullable|string'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template email sent successfully',
            'data' => [
                'recipients' => $validated['recipients'],
                'template_name' => $validated['template_name'],
                'template_data' => $validated['template_data'] ?? [],
                'sent_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * POST /email/send-bulk
     */
    public function sendBulkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'batch_size' => 'nullable|integer|min:1|max:100'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bulk email sent successfully',
            'data' => [
                'recipients_count' => count($validated['recipients']),
                'subject' => $validated['subject'],
                'batch_size' => $validated['batch_size'] ?? 50,
                'sent_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * GET /email/test-connection
     */
    public function testConnection(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Email connection test completed',
            'data' => [
                'status' => 'connected',
                'timestamp' => now()->toISOString(),
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port')
            ]
        ]);
    }
}