<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\Department;
use Modules\Auth\app\Models\Attendance\Semester;
use Modules\Auth\app\Models\Attendance\Course;
use Modules\Auth\app\Models\Attendance\CourseEnrollment;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\LecturerAccount;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\StudentAccount;
use Modules\Auth\app\Models\Classroom;

class EducationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Education Data...');

        // 1. Get Department
        $cntt = Department::where('name', 'Khoa Công nghệ Thông tin')->first();
        if (!$cntt) {
            $this->command->error('Khoa CNTT not found. Run DepartmentDatabaseSeeder first.');
            return;
        }

        // 2. Create Semester
        $semester = Semester::firstOrCreate(
            ['code' => 'HK1-2425'],
            [
                'name' => 'Học kỳ 1 năm học 2024-2025',
                'academic_year' => '2024-2025',
                'semester_type' => '1',
                'start_date' => '2024-09-01',
                'end_date' => '2025-01-31',
                'is_active' => true,
            ]
        );

        // 3. Create Lecturers
        $lecturers = [
            [
                'code' => 'GV001',
                'name' => 'Nguyễn Văn A',
                'email' => 'gv1@school.edu.vn',
                'username' => 'gv1'
            ],
            [
                'code' => 'GV002',
                'name' => 'Trần Thị B',
                'email' => 'gv2@school.edu.vn',
                'username' => 'gv2'
            ]
        ];

        foreach ($lecturers as $l) {
            $lecturer = Lecturer::firstOrCreate(
                ['lecturer_code' => $l['code']],
                [
                    'full_name' => $l['name'],
                    'email' => $l['email'],
                    'department_id' => $cntt->id,
                    'phone' => '0901234' . substr($l['code'], -3),
                    'birth_date' => '1980-01-01',
                    'address' => 'Hà Nội'
                ]
            );

            LecturerAccount::firstOrCreate(
                ['lecturer_id' => $lecturer->id],
                [
                    'username' => $l['username'],
                    'password' => Hash::make('123456'),
                    'is_admin' => false
                ]
            );
        }

        $gv1 = Lecturer::where('lecturer_code', 'GV001')->first();
        $gv2 = Lecturer::where('lecturer_code', 'GV002')->first();

        // 4. Create Classrooms (Lớp sinh hoạt)
        $classrooms = [
            ['code' => 'K65PM1', 'name' => 'K65 Phần mềm 1', 'lecturer' => $gv1],
            ['code' => 'K65PM2', 'name' => 'K65 Phần mềm 2', 'lecturer' => $gv2],
        ];

        foreach ($classrooms as $c) {
            Classroom::firstOrCreate(
                ['class_code' => $c['code']],
                [
                    'class_name' => $c['name'],
                    'department_id' => $cntt->id,
                    'lecturer_id' => $c['lecturer']->id,
                    'school_year' => '2024-2028'
                ]
            );
        }

        $pm1 = Classroom::where('class_code', 'K65PM1')->first();
        $pm2 = Classroom::where('class_code', 'K65PM2')->first();

        // 5. Create Students
        // Create 5 students in PM1 and 5 in PM2
        for ($i = 1; $i <= 10; $i++) {
            $class = $i <= 5 ? $pm1 : $pm2;
            $code = 'SV' . str_pad($i, 3, '0', STR_PAD_LEFT); // SV001, SV002...
            $username = 'sv' . $i;

            $student = Student::withTrashed()->firstOrCreate(
                ['student_code' => $code],
                [
                    'full_name' => "Sinh viên $i",
                    'class_id' => $class->id,
                    'email' => "$username@student.school.edu.vn",
                    'birth_date' => '2006-01-01',
                    'gender' => ($i % 2 == 0) ? 'female' : 'male',
                    'address' => 'Hà Nội',
                    'phone' => '09876543' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'account_status' => 'active'
                ]
            );
            
            if ($student->trashed()) {
                $student->restore();
            }

            StudentAccount::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'username' => $username,
                    'password' => Hash::make('123456')
                ]
            );
        }

        // 6. Create Courses (Môn học/Lớp tín chỉ)
        // Course 1: Lập trình Web (GV1)
        $courseWeb = Course::withTrashed()->firstOrCreate(
            ['code' => 'INT3306-1'],
            [
                'name' => 'Lập trình Web',
                'semester_id' => $semester->id,
                'lecturer_id' => $gv1->id,
                'department_id' => $cntt->id,
                'credits' => 3,
                'status' => 'active',
                'start_date' => '2024-09-05',
                'end_date' => '2024-12-30',
                'schedule_days' => [2, 4], // Thứ 2, Thứ 4
                'start_time' => '07:00',
                'end_time' => '09:00',
                'room' => '301-G2'
            ]
        );
        if ($courseWeb->trashed()) $courseWeb->restore();

        // Course 2: Cơ sở dữ liệu (GV2)
        $courseDB = Course::withTrashed()->firstOrCreate(
            ['code' => 'INT3307-1'],
            [
                'name' => 'Cơ sở dữ liệu',
                'semester_id' => $semester->id,
                'lecturer_id' => $gv2->id,
                'department_id' => $cntt->id,
                'credits' => 3,
                'status' => 'active',
                'start_date' => '2024-09-06',
                'end_date' => '2024-12-31',
                'schedule_days' => [3, 5], // Thứ 3, Thứ 5
                'start_time' => '09:00',
                'end_time' => '11:00',
                'room' => '302-G2'
            ]
        );
        if ($courseDB->trashed()) $courseDB->restore();

        // 7. Enroll Students to Courses
        $allStudents = Student::all();
        foreach ($allStudents as $student) {
            // Enroll in Web
            CourseEnrollment::firstOrCreate(
                [
                    'course_id' => $courseWeb->id,
                    'student_id' => $student->id
                ],
                [
                    'enrolled_at' => now(),
                    'status' => 'active',
                    'note' => 'Auto seeded'
                ]
            );

            // Enroll in DB
            CourseEnrollment::firstOrCreate(
                [
                    'course_id' => $courseDB->id,
                    'student_id' => $student->id
                ],
                [
                    'enrolled_at' => now(),
                    'status' => 'active',
                    'note' => 'Auto seeded'
                ]
            );
        }

        $this->command->info('Education Data Seeded Successfully!');
    }
}
