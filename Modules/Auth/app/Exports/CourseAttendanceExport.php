<?php
/* Modules/Auth/app/Exports/CourseAttendanceExport.php */

namespace Modules\Auth\app\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseAttendanceExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('auth::exports.course_attendance', [
            'course' => $this->data['course'],
            'sessions' => $this->data['sessions'],
            'students' => $this->data['students'],
            'statistics' => $this->data['statistics'],
        ]);
    }

    public function title(): string
    {
        return 'Điểm danh ' . $this->data['course']['code'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style cho header sẽ được định nghĩa trong blade hoặc ở đây
            1 => ['font' => ['bold' => true]],
        ];
    }
}
