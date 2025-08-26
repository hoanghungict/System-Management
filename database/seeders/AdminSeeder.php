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
        // Tạo đơn vị mẫu (unit)
        $unitId = DB::table('unit')->insertGetId([
            'name' => 'Khoa Công nghệ Thông tin',
            'type' => 'faculty',
            'parent_id' => null,
        ]);

        // Tạo giảng viên admin
        $lecturerId = DB::table('lecturer')->insertGetId([
            'full_name' => 'Admin System',
            'gender' => 'male',
            'address' => 'Hà Nội',
            'email' => 'admin@system.com',
            'phone' => '0123456789',
            'lecturer_code' => 'GV001',
            'unit_id' => $unitId,
        ]);

        // Tạo tài khoản admin
        DB::table('lecturer_account')->insert([
            'lecturer_id' => $lecturerId,
            'username' => 'admin',
            'password' => Hash::make('123456'),
            'is_admin' => 1, // Là admin
        ]);

        // Tạo lớp mẫu
        $classId = DB::table('class')->insertGetId([
            'class_name' => 'Lớp CNTT K65',
            'class_code' => 'CNTT65',
            'faculty_id' => $unitId,
            'lecturer_id' => $lecturerId,
            'school_year' => '2024-2025',
        ]);

        // Tạo sinh viên mẫu
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

        // Tạo tài khoản sinh viên mẫu
        DB::table('student_account')->insert([
            'student_id' => $studentId,
            'username' => 'sv_sv001',
            'password' => Hash::make('123456'),
        ]);

        $this->command->info('✅ Đã tạo dữ liệu mẫu thành công!');
        $this->command->info('👤 Admin: username=admin, password=123456');
        $this->command->info('👤 Sinh viên: username=sv_sv001, password=123456');
        $this->command->info('🏫 Đơn vị: Khoa Công nghệ Thông tin');
        $this->command->info('📚 Lớp: CNTT K65');
    }
}
