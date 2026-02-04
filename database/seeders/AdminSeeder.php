<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chạy seeder cho mẫu thông báo
        $this->call(NotificationTemplateSeeder::class);

        // Kiểm tra và tạo đơn vị mẫu (unit)
        $unit = DB::table('department')->where('name', 'Khoa Công nghệ Thông tin')->first();
        if (!$unit) {
            $unitId = DB::table('department')->insertGetId([
                'name' => 'Khoa Công nghệ Thông tin',
                'type' => 'faculty',
                'parent_id' => null,
                'staff_count' => 0,
            ]);
        } else {
            $unitId = $unit->id;
        }

        // Kiểm tra và tạo giảng viên admin
        // Check by code OR email
        $lecturer = DB::table('lecturer')
            ->where('lecturer_code', 'GV001')
            ->orWhere('email', 'admin@system.com')
            ->first();

        if (!$lecturer) {
            $lecturerId = DB::table('lecturer')->insertGetId([
                'full_name' => 'Admin System',
                'gender' => 'male',
                'address' => 'Hà Nội',
                'email' => 'admin@system.com',
                'phone' => '0123456789',
                'lecturer_code' => 'GV001',
                'department_id' => $unitId,
            ]);
        } else {
            $lecturerId = $lecturer->id;
        }

        // Kiểm tra và tạo tài khoản admin
        $adminAccount = DB::table('lecturer_account')->where('username', 'admin')->first();
        if (!$adminAccount) {
            DB::table('lecturer_account')->insert([
                'lecturer_id' => $lecturerId,
                'username' => 'admin',
                'password' => Hash::make('123456'),
                'is_admin' => 1, // Là admin
            ]);
        }

        // Kiểm tra và tạo lớp mẫu
        $class = DB::table('class')->where('class_code', 'CNTT65')->first();
        if (!$class) {
            $classId = DB::table('class')->insertGetId([
                'class_name' => 'Lớp CNTT K65',
                'class_code' => 'CNTT65',
                'department_id' => $unitId,
                'lecturer_id' => $lecturerId,
                'school_year' => '2024-2025',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $classId = $class->id;
        }

        // Kiểm tra và tạo sinh viên mẫu
        $student = DB::table('student')
            ->where('student_code', 'SV001')
            ->orWhere('email', 'sinhvien@test.com')
            ->first();

        if (!$student) {
            $studentId = DB::table('student')->insertGetId([
                'full_name' => 'Sinh Viên Mẫu',
                'birth_date' => '2000-01-01',
                'gender' => 'male',
                'address' => 'Hà Nội',
                'email' => 'sinhvien@test.com',
                'phone' => '0987654321',
                'student_code' => 'SV001',
                'class_id' => $classId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $studentId = $student->id;
        }

        // Kiểm tra và tạo tài khoản sinh viên mẫu
        $studentAccount = DB::table('student_account')->where('username', 'sv_sv001')->first();
        if (!$studentAccount) {
            DB::table('student_account')->insert([
                'student_id' => $studentId,
                'username' => 'sv_sv001',
                'password' => Hash::make('123456'),
            ]);
        }

        // --- NEW: Create Semester and Database Course for Lecturer ---

        // 1. Create Semester
        $semester = DB::table('semesters')->where('code', 'HK1-2425')->first();
        if (!$semester) {
            $semesterId = DB::table('semesters')->insertGetId([
                'name' => 'Học kỳ 1 năm học 2024-2025',
                'code' => 'HK1-2425',
                'academic_year' => '2024-2025',
                'semester_type' => '1',
                'start_date' => '2024-09-01',
                'end_date' => '2025-01-31',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $semesterId = $semester->id;
        }

        // 2. Create Course "Cơ sở dữ liệu" assigned to Lecturer
        $course = DB::table('courses')->where('code', 'CSDL_01')->first();
        if (!$course) {
            $courseId = DB::table('courses')->insertGetId([
                'code' => 'CSDL_01',
                'name' => 'Cơ sở dữ liệu - Lớp 1',
                'credits' => 3,
                'semester_id' => $semesterId,
                'lecturer_id' => $lecturerId,
                'department_id' => $unitId,
                'start_date' => '2024-09-05',
                'end_date' => '2025-01-15',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->command->info('✅ Đã tạo Môn học: Cơ sở dữ liệu - Lớp 1 (CSDL_01) cho GV Admin.');
        } else {
             $this->command->info('ℹ️ Môn học CSDL_01 đã tồn tại.');
        }

        $this->command->info('✅ Đã tạo dữ liệu mẫu thành công!');
        $this->command->info('👤 Admin: username=admin, password=123456');
        $this->command->info('👤 Sinh viên: username=sv_sv001, password=123456');
        $this->command->info('🏫 Đơn vị: Khoa Công nghệ Thông tin');
        $this->command->info('📚 Lớp: CNTT K65');


    // Them user nha vanh
        $teachers = [
            ['name' => 'Nguyễn Hoài Linh', 'code' => 'thay_linh', 'email' => 'thayling@system.com'],
            ['name' => 'Hoàng Ngọc Hưng',   'code' => 'thay_hung', 'email' => 'thayhung@system.com'],
            ['name' => 'Nguyễn Tuấn Anh',     'code' => 'thay_tuan_anh', 'email' => 'thaytuananh@system.com'],
        ];

        foreach ($teachers as $t) {
            $lec = DB::table('lecturer')->where('lecturer_code', $t['code'])->first();
            if (!$lec) {
                $lecId = DB::table('lecturer')->insertGetId([
                    'full_name'     => $t['name'],
                    'gender'        => 'male',
                    'address'       => 'Hà Nội',
                    'email'         => $t['email'],
                    'phone'         => '0900000000',
                    'lecturer_code' => $t['code'],
                    'department_id' => $unitId, // Khoa CNTT
                ]);
            } else {
                $lecId = $lec->id;
            }

            $username = strtolower($t['code']);
            $acc = DB::table('lecturer_account')->where('username', $username)->first();
            if (!$acc) {
                DB::table('lecturer_account')->insert([
                    'lecturer_id' => $lecId,
                    'username'    => $username,
                    'password'    => Hash::make('123456'),
                    'is_admin'    => 0,
                ]);
            }
        }
        $this->command->info('✅ Đã thêm 3 Giảng viên (thay_ling, thay_hung, thay_tuan_anh) / Pass: 123456');

        $students = [
            ['name' => 'Đỗ Văn Anh',    'code' => 'van_anh', 'email' => 'vananh@test.com'],
            ['name' => 'Dương Đức Anh',     'code' => 'duc_anh', 'email' => 'ducanh@test.com'],
            ['name' => 'Nguyễn Ngọc Lâm',      'code' => 'ngoc_lam', 'email' => 'ngoclam@test.com'],
            ['name' => 'Vũ Xuân Nam',     'code' => 'xuan_nam', 'email' => 'xuannam@test.com'],
            ['name' => 'Dư Văn Độ',   'code' => 'van_do', 'email' => 'vando@test.com'],
            ['name' => 'Nguyễn Ngọc Hiếu',      'code' => 'ngoc_hieu', 'email' => 'ngochieu@test.com'],
        ];

        foreach ($students as $s) {
            $stu = DB::table('student')->where('student_code', $s['code'])->first();
            if (!$stu) {
                $stuId = DB::table('student')->insertGetId([
                    'full_name'    => $s['name'],
                    'birth_date'   => '2005-01-01',
                    'gender'       => 'male',
                    'address'      => 'Hà Nội',
                    'email'        => $s['email'],
                    'phone'        => '0912345678',
                    'student_code' => $s['code'],
                    'class_id'     => $classId, // Assign to same class (CNTT K65)
                    'created_at'   => now(),
                    'updated_at'   => now()
                ]);
            } else {
                $stuId = $stu->id;
            }

            $username = strtolower($s['code']);
            $acc = DB::table('student_account')->where('username', $username)->first();
            if (!$acc) {
                DB::table('student_account')->insert([
                    'student_id' => $stuId,
                    'username'   => $username,
                    'password'   => Hash::make('123456'),
                ]);
            }
        }
        $this->command->info('✅ Đã thêm 6 Sinh viên (van_anh, duc_anh, ngoc_lam, xuan_name, van_do, ngoc_hieu) / Pass: 123456');
    }
}
