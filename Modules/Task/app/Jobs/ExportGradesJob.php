<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Task\app\Exports\AssignmentGradesExport;
use Modules\Task\app\Models\Assignment;
use Modules\Auth\app\Models\AuditLog;
use Modules\Auth\app\Models\Lecturer;

class ExportGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assignmentId;
    protected $lecturerId;

    public function __construct(int $assignmentId, int $lecturerId)
    {
        $this->assignmentId = $assignmentId;
        $this->lecturerId = $lecturerId;
    }

    public function handle(): void
    {
        $assignment = Assignment::find($this->assignmentId);

        if (!$assignment) {
            return;
        }

        $fileName = 'grades_assignment_' . $this->assignmentId . '_' . date('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        // Store file in public disk or protected storage? User said backend only.
        // Let's assume standard storage (private) and return link via notification or email?
        // For simplicity and immediate download context, typical logic might store and notify.
        // We will store it.
        
        Excel::store(new AssignmentGradesExport($this->assignmentId), $filePath);

        // Notify Lecturer (Since we don't have a full notification system set up for this specific flow in the plan,
        // we'll just log it for now, or assume the controller returns a "Processing" status and polling/email would handle it)
        // A real app would send a notification with a download link.
        
        // Log to AuditLog
        AuditLog::log('grades_exported', $this->lecturerId, 'Assignment', $this->assignmentId, [
            'file_name' => $fileName,
            'path' => $filePath
        ]);
        
        // FUTURE: Notification::send($lecturer, new GradesExportReady($filePath));
    }
}
