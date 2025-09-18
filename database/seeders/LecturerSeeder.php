<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LecturerSeeder extends Seeder
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

        // Tạo giảng viên thường
        $lecturerId = DB::table('lecturer')->insertGetId([
            'full_name' => 'Nguyễn Văn A',
            'gender' => 'male',
            'address' => 'Hà Nội',
            'email' => 'nguyenvana@test.com',
            'phone' => '0912345678',
            'lecturer_code' => 'GV002',
            'department_id' => $unitId,
        ]);

        // Tạo tài khoản giảng viên
        DB::table('lecturer_account')->insert([
            'lecturer_id' => $lecturerId,
            'username' => 'gv_gv002',
            'password' => Hash::make('123456'),
            'is_admin' => 0, // Giảng viên thường
        ]);

        $this->command->info('✅ Đã tạo giảng viên thường thành công!');
        $this->command->info('👨‍🏫 Giảng viên: username=gv_gv002, password=123456');
    }
}
