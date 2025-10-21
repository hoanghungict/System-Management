<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo đơn vị mẫu (unit)
        $unitId = DB::table('department')->insertGetId([
            'name' => 'Ngôn ngữ',
            'type' => 'faculty',
            'parent_id' => null,
        ]);


        // Tạo lớp mẫu
        $classId = DB::table('class')->insertGetId([
            'class_name' => 'Lớp CNTT01 ',
            'class_code' => 'CNTT01',
            'faculty_id' => $unitId,
            'lecturer_id' => 2,
            'school_year' => '2024-2025',
        ]);


        // Tạo sinh viên mẫu 2
        $studentId2 = DB::table('student')->insertGetId([
            'full_name' => 'Sinh Viên Thử Nghiệm',
            'birth_date' => '2001-02-02',
            'gender' => 'female',
            'address' => 'Hà Nội',
            'email' => 'sv2@test.com',
            'phone' => '0912345678',
            'student_code' => 'SV002',
            'class_id' => $classId,
        ]);

        // Tạo tài khoản sinh viên mẫu 2
        DB::table('student_account')->insert([
            'student_id' => $studentId2,
            'username' => 'sv_sv002',
            'password' => Hash::make('123456'),
        ]);

        $this->command->info('✅ Đã tạo dữ liệu mẫu thành công!');
        $this->command->info('👤 Sinh viên 2: username=sv_sv002, password=123456');
    }
}
