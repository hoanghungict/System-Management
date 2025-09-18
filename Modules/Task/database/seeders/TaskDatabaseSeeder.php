<?php

namespace Modules\Task\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder cho Task
 * 
 * Seeder này chạy các seeders khác để tạo dữ liệu mẫu cho Task
 * Tuân thủ Clean Architecture: chỉ chứa seeding logic, không chứa business logic phức tạp
 */
class TaskDatabaseSeeder extends Seeder
{
    /**
     * Chạy database seeds
     */
    public function run(): void
    {
        // $this->call([]);
    }
}
