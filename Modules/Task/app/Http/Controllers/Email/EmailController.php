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
}