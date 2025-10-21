<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bỏ qua seed user nếu bảng chưa tồn tại
        if (Schema::hasTable('users')) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'), // thêm password nếu cần
            ]);
        }

        // Chạy AdminSeeder để tạo dữ liệu mẫu
        $this->call([
            AdminSeeder::class,
        ]);
    }
}
