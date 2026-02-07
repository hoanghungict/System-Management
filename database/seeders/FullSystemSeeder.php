<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Modules\Auth\app\Models\Department;
use Modules\Auth\app\Models\Classroom;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\LecturerAccount;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\StudentAccount;
use Modules\Auth\app\Models\Attendance\Course;
use Modules\Auth\app\Models\Attendance\Semester;
use Modules\Auth\app\Models\Attendance\CourseEnrollment;
use Modules\Task\app\Models\Assignment;
use Modules\Task\app\Models\AssignmentSubmission;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\ExamSubmission;
use Modules\Task\app\Models\QuestionBank; // Assuming needed for Exams
use Modules\Task\app\Models\ExamCode; // Needed for Exam publishing

class FullSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate Tables
        $tables = [
            'student_account', 'lecturer_account', 'student', 'lecturer', 
            'course_enrollments', 'courses', 'semesters', 'department', 'class', 
            'assignments', 'assignment_submissions', 'exam_submissions', 'exams', 
            'attendance_sessions', 'exam_codes', 'question_banks', 'questions'
        ];
        
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Tables truncated. Starting seeding...');

        // 1. Departments (2 Khoa) - Table: department
        // Schema: name, type ('school','faculty','department'), parent_id
        $deptCNTT = Department::create(['name' => 'Khoa Công Nghệ Thông Tin', 'type' => 'faculty']);
        $deptKT = Department::create(['name' => 'Khoa Kinh Tế', 'type' => 'faculty']);

        // 2. Lecturers (1 Admin + 3 Giảng viên) - Table: lecturer
        // Schema: full_name, email, phone, lecturer_code, department_id, ...
        
        // Admin Profile
        $adminProfile = Lecturer::create([
            'full_name' => 'Quản Trị Viên',
            'email' => 'admin@school.edu.vn',
            'phone' => '0900000000',
            'department_id' => $deptCNTT->id,
            'lecturer_code' => 'ADMIN01',
        ]);
        LecturerAccount::create([
            'lecturer_id' => $adminProfile->id,
            'username' => 'admin',
            'password' => Hash::make('password'),
            'is_admin' => true
        ]);

        $lecturerNames = ['Nguyễn Văn A', 'Trần Thị B', 'Lê Văn C'];
        $lecturers = [];
        foreach ($lecturerNames as $index => $name) {
            $l = Lecturer::create([
                'full_name' => $name,
                'email' => "gv" . ($index + 1) . "@school.edu.vn",
                'phone' => '091234567' . $index,
                'department_id' => $deptCNTT->id,
                'lecturer_code' => 'GV' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
            ]);
            LecturerAccount::create([
                'lecturer_id' => $l->id,
                'username' => "gv" . ($index + 1),
                'password' => Hash::make('password'),
                'is_admin' => false
            ]);
            $lecturers[] = $l;
        }

        // 3. Classrooms (5 Lớp) - Table: class
        // Schema: class_name, class_code, department_id, lecturer_id, school_year
        $class1 = Classroom::create([
            'class_code' => 'CNTT01', 
            'class_name' => 'Lớp CNTT K1', 
            'department_id' => $deptCNTT->id, 
            'school_year' => '2024-2025',
            'lecturer_id' => $lecturers[0]->id // Assign GV1 to Class 1
        ]);
        $class2 = Classroom::create(['class_code' => 'CNTT02', 'class_name' => 'Lớp CNTT K2', 'department_id' => $deptCNTT->id, 'school_year' => '2024-2025']);
        $class3 = Classroom::create(['class_code' => 'KT01', 'class_name' => 'Lớp KT K1', 'department_id' => $deptKT->id, 'school_year' => '2024-2025']);
        $class4 = Classroom::create(['class_code' => 'KT02', 'class_name' => 'Lớp KT K2', 'department_id' => $deptKT->id, 'school_year' => '2024-2025']);
        $class5 = Classroom::create(['class_code' => 'CNTT03', 'class_name' => 'Lớp CNTT K3', 'department_id' => $deptCNTT->id, 'school_year' => '2024-2025']);

        // 4. Students (30 Sinh viên) - Table: student
        // Schema: full_name, student_code, class_id, email, phone, birth_date, gender, account_status ('active')
        $students = [];
        $firstNames = ['An', 'Bình', 'Cường', 'Dũng', 'Giang', 'Hương', 'Hùng', 'Khánh', 'Lan', 'Minh', 'Nam', 'Nga', 'Oanh', 'Phúc', 'Quân', 'Sơn', 'Thảo', 'Trang', 'Tú', 'Uyên', 'Vân', 'Vinh', 'Xuân', 'Yến', 'Kim', 'Lộc', 'Phát', 'Tài', 'Đức', 'Trí'];
        $lastNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];

        for ($i = 1; $i <= 30; $i++) {
            $randLast = $lastNames[array_rand($lastNames)];
            $randFirst = $firstNames[array_rand($firstNames)];
            $fullName = "$randLast $randFirst";
            
            $s = Student::create([
                'full_name' => $fullName,
                'student_code' => 'SV' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'class_id' => $class1->id,
                'email' => "sv$i@student.school.edu.vn",
                'phone' => '09876543' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'birth_date' => '2000-01-01',
                'gender' => ($i % 2 == 0) ? 'female' : 'male', // 'male','female','other'
                'account_status' => 'active',
            ]);
            StudentAccount::create([
                'student_id' => $s->id,
                'username' => "sv$i",
                'password' => Hash::make('password')
            ]);
            $students[] = $s;
        }

        // 5. Semester - Table: semesters
        // Schema: name, code, academic_year, semester_type ('1','2','3'), start_date, end_date, is_active
        $semester = Semester::create([
            'code' => 'HK1-2024',
            'name' => 'Học kỳ 1 Năm học 2024-2025',
            'semester_type' => '1',
            'academic_year' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-01-15',
            'is_active' => true,
        ]);

        // 6. Courses (4 Môn) - Linked to Lecturers - Table: courses
        $courseData = [
            ['code' => 'IT001', 'name' => 'Lập trình Web', 'credits' => 3, 'lecturer_idx' => 0],
            ['code' => 'IT002', 'name' => 'Cấu trúc dữ liệu', 'credits' => 3, 'lecturer_idx' => 1],
            ['code' => 'IT003', 'name' => 'Cơ sở dữ liệu', 'credits' => 4, 'lecturer_idx' => 2],
            ['code' => 'IT004', 'name' => 'Mạng máy tính', 'credits' => 3, 'lecturer_idx' => 0],
        ];

        $courses = [];
        foreach ($courseData as $c) {
            $course = Course::create([
                'code' => $c['code'],
                'name' => $c['name'],
                'credits' => $c['credits'],
                'semester_id' => $semester->id,
                'lecturer_id' => $lecturers[$c['lecturer_idx']]->id,
                'department_id' => $deptCNTT->id,
                'schedule_days' => ['Monday', 'Wednesday'], 
                'start_date' => '2024-09-05',
                'end_date' => '2025-01-10',
                'status' => 'active',
                'sessions_generated' => false
            ]);
            $courses[] = $course;
        }

        // 7. Enrollment (All 30 students to all 4 courses)
        foreach ($courses as $course) {
            foreach ($students as $student) {
                CourseEnrollment::create([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]);
            }
        }

        // 8. Assignments & Grades (Only for IT001 - Lập trình Web for demo)
        $courseWeb = $courses[0]; 
        $lecturerWeb = $lecturers[0];

        $assignmentTypes = [
            ['title' => 'Bài thường xuyên 1', 'col' => 'TX1'],
            ['title' => 'Bài thường xuyên 2', 'col' => 'TX2'],
            ['title' => 'Kiểm tra định kỳ 1', 'col' => 'ĐK1'],
            ['title' => 'Kiểm tra định kỳ 2', 'col' => 'ĐK2'],
        ];

        foreach ($assignmentTypes as $type) {
            $assign = Assignment::create([
                'course_id' => $courseWeb->id,
                'title' => $type['title'],
                'description' => 'Bài tập kiểm tra kiến thức.',
                'lecturer_id' => $lecturerWeb->id,
                'deadline' => Carbon::now()->addDays(7),
                'grade_column' => $type['col'],
                'status' => 'published',
            ]);

            // Seed scores for each student
            foreach ($students as $student) {
                // Random score 5-10
                $score = rand(50, 100) / 10; 
                AssignmentSubmission::create([
                    'assignment_id' => $assign->id,
                    'student_id' => $student->id,
                    // 'content' => 'Em nộp bài ạ', // Removed as column doesn't exist
                    'submitted_at' => now(),
                    'status' => 'graded',
                    'total_score' => $score,
                    'manual_score' => $score,
                    'graded_by' => $lecturerWeb->id,
                    'graded_at' => now(),
                ]);
            }
        }

        // 9. Exams
        // Need a QuestionBank first
        $qBank = QuestionBank::create([
            'lecturer_id' => $lecturerWeb->id,
            'course_id' => $courseWeb->id,
            'name' => 'Ngân hàng câu hỏi Web',
            'status' => 'active'
        ]);

        $exam = Exam::create([
            'course_id' => $courseWeb->id,
            'title' => 'Thi cuối kỳ môn Lập trình Web',
            'lecturer_id' => $lecturerWeb->id,
            'question_bank_id' => $qBank->id,
            'start_time' => Carbon::now()->subDays(1),
            'end_time' => Carbon::now()->addDays(1),
            'time_limit' => 60,
            'total_questions' => 50,
            'status' => 'published', 
            'exam_codes_count' => 1,
            'anti_cheat_enabled' => false
        ]);

        // Create an Exam Code
        $examCode = ExamCode::create([
            'exam_id' => $exam->id,
            'code' => '101',
            'question_order' => [], // Empty for now
        ]);

        foreach ($students as $student) {
            $examScore = rand(40, 100) / 10;
            ExamSubmission::create([
                'exam_id' => $exam->id,
                'exam_code_id' => $examCode->id,
                'student_id' => $student->id,
                'attempt' => 1,
                'started_at' => now()->subHour(),
                'submitted_at' => now(),
                'status' => 'graded',
                'correct_count' => 40,
                'wrong_count' => 10,
                'total_score' => $examScore, 
            ]);
        }

        $this->command->info('Full System Seeding Completed!');
        $this->command->info('Admin: admin / password');
        $this->command->info('Lecturer: gv1 / password');
        $this->command->info('Student: sv1 / password');
    }
}
