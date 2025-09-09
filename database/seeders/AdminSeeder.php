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
        // Kiểm tra và tạo đơn vị mẫu (unit)
        $unit = DB::table('department')->where('name', 'Khoa Công nghệ Thông tin')->first();
        if (!$unit) {
            $unitId = DB::table('department')->insertGetId([
                'name' => 'Khoa Công nghệ Thông tin',
                'type' => 'faculty',
                'parent_id' => null,
            ]);
        } else {
            $unitId = $unit->id;
        }

        // Kiểm tra và tạo giảng viên admin
        $lecturer = DB::table('lecturer')->where('email', 'admin@system.com')->first();
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
            ]);
        } else {
            $classId = $class->id;
        }

        // Kiểm tra và tạo sinh viên mẫu
        $student = DB::table('student')->where('email', 'sinhvien@test.com')->first();
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

        $this->command->info('✅ Đã tạo dữ liệu mẫu thành công!');
        $this->command->info('👤 Admin: username=admin, password=123456');
        $this->command->info('👤 Sinh viên: username=sv_sv001, password=123456');
        $this->command->info('🏫 Đơn vị: Khoa Công nghệ Thông tin');
        $this->command->info('📚 Lớp: CNTT K65');
    }
}
