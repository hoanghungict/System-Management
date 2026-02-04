<?php
/* Modules/Auth/app/Exports/SemesterTimelineExport.php */

namespace Modules\Auth\app\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SemesterTimelineExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('auth::exports.semester_timeline', [
            'students' => $this->data['students'],
            'columns' => $this->data['columns'],
            'attendance' => $this->data['attendance'],
            'semester_name' => $this->data['semester_name'],
        ]);
    }

    public function title(): string
    {
        return 'Tổng quan ' . $this->data['semester_name'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
