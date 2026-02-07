<?php

namespace Modules\Auth\app\Imports;

use Modules\Auth\app\Models\Attendance\AttendanceSession;
use Modules\Auth\app\Models\Attendance\Course;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        try {
            $courseCode = $row['ma_mon'] ?? $row['code'] ?? null;
            if (!$courseCode) return null;

            $course = Course::where('code', $courseCode)->first();
            if (!$course) {
                Log::warning("ScheduleImport: Course not found for code {$courseCode}");
                return null;
            }

            // Parse Date
            $date = $this->parseDate($row['ngay'] ?? $row['date']);
            if (!$date) return null;

            // Parse Time
            $startTime = $this->parseTime($row['gio_bat_dau'] ?? $row['start_time']);
            $endTime = $this->parseTime($row['gio_ket_thuc'] ?? $row['end_time']);

            if (!$startTime || !$endTime) return null;

            // Day of Week
            $dayOfWeek = Carbon::parse($date)->dayOfWeekIso + 1; // 2=Mon

            // Session Number logic: count existing sessions + 1
            $sessionCount = AttendanceSession::where('course_id', $course->id)->count();

            return new AttendanceSession([
                'course_id' => $course->id,
                'session_number' => $sessionCount + 1,
                'session_date' => $date,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $row['phong'] ?? $row['room'] ?? 'TBA',
                'status' => 'scheduled',
                'topic' => $row['chu_de'] ?? $row['topic'] ?? null,
                'notes' => $row['ghi_chu'] ?? $row['notes'] ?? null,
                'lecturer_id' => $course->lecturer_id, // Default to course lecturer
            ]);
        } catch (\Exception $e) {
            Log::error("ScheduleImport Error: " . $e->getMessage(), ['row' => $row]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'ma_mon' => 'required',
            'ngay' => 'required',
            'gio_bat_dau' => 'required',
            'gio_ket_thuc' => 'required',
        ];
    }

    private function parseDate($value)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $ex) {
                return null;
            }
        }
    }

    private function parseTime($value)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i:s');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('H:i:s');
            } catch (\Exception $ex) {
                return null;
            }
        }
    }
}
