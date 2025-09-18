<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LecturerSeeder3 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Giả sử khoa CNTT đã tồn tại (tạo từ AdminSeeder), lấy ra id
        $unitId = DB::table('department')
            ->where('name', 'Khoa Công nghệ Thông tin')
            ->value('id');

        if (!$unitId) {
            $this->command->error('❌ Chưa có đơn vị Khoa Công nghệ Thông tin. Hãy chạy AdminSeeder trước!');
            return;
        }



        // ==========================
        // Giảng viên 2
        // ==========================
        $lecturerId2 = DB::table('lecturer')->insertGetId([
            'full_name' => 'Nguyễn Thị B',
            'gender' => 'female',
            'address' => 'Hồ Chí Minh',
            'email' => 'nguyenthivyb@test.com',
            'phone' => '0987654321',
            'lecturer_code' => 'GV004',
            'department_id' => $unitId,
        ]);

        DB::table('lecturer_account')->insert([
            'lecturer_id' => $lecturerId2,
            'username' => 'gv_gv003',
            'password' => Hash::make('123456'),
            'is_admin' => 0,
        ]);

        $this->command->info('✅ Đã tạo giảng viên 2 thành công!');
        $this->command->info('👩‍🏫 Giảng viên 2: username=gv_gv003, password=123456');
    }
}
