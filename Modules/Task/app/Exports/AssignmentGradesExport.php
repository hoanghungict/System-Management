<?php

namespace Modules\Task\app\Exports;

use Modules\Task\app\Models\Assignment;
use Modules\Task\app\Models\AssignmentSubmission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssignmentGradesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $assignmentId;

    public function __construct(int $assignmentId)
    {
        $this->assignmentId = $assignmentId;
    }

    public function collection()
    {
        return AssignmentSubmission::with('student')
            ->where('assignment_id', $this->assignmentId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Student Code',
            'Attempt',
            'Status',
            'Auto Score',
            'Manual Score',
            'Total Score',
            'Submitted At',
            'Graded At',
        ];
    }

    public function map($submission): array
    {
        $studentName = $submission->student ? $submission->student->full_name : 'N/A (Deleted)';
        $studentCode = $submission->student ? ($submission->student->student_code ?? 'N/A') : 'N/A';

        return [
            $studentName,
            $studentCode,
            $submission->attempt,
            $submission->status,
            $submission->auto_score,
            $submission->manual_score,
            $submission->total_score,
            $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : 'N/A',
            $submission->graded_at ? $submission->graded_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Student Name
            'B' => 15, // Student Code
            'C' => 10, // Attempt
            'D' => 15, // Status
            'E' => 12, // Auto Score
            'F' => 12, // Manual Score
            'G' => 12, // Total Score
            'H' => 20, // Submitted At
            'I' => 20, // Graded At
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->collection()->count() + 1; // +1 for header

                // Header styling with background color
                $sheet->getStyle('A1:I1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('4F81BD'); // Nice Blue Color
                
                // Add borders to all cells
                $sheet->getStyle('A1:I'.$rowCount)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // Center align specific columns (Attempt, Status, Scores, Dates)
                $sheet->getStyle('C2:I'.$rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Center align header
                $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
