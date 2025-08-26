<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\app\Models\Unit;

class DepartmentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo University (Trường đại học)
        $university = Unit::create([
            'name' => 'Đại học Công nghệ Thông tin',
            'type' => 'university'
        ]);

        // Tạo các Faculty (Khoa)
        $faculties = [
            [
                'name' => 'Khoa Công nghệ Thông tin',
                'type' => 'faculty',
                'parent_id' => $university->id
            ],
            [
                'name' => 'Khoa Kỹ thuật Điện tử - Viễn thông',
                'type' => 'faculty',
                'parent_id' => $university->id
            ]
        ];

        foreach ($faculties as $facultyData) {
            $faculty = Unit::create($facultyData);

            // Tạo các Department (Tổ) cho mỗi khoa
            if ($faculty->name === 'Khoa Công nghệ Thông tin') {
                Unit::create([
                    'name' => 'Tổ Công nghệ Phần mềm',
                    'type' => 'department',
                    'parent_id' => $faculty->id
                ]);
            }

            if ($faculty->name === 'Khoa Kỹ thuật Điện tử - Viễn thông') {
                Unit::create([
                    'name' => 'Tổ Điện tử',
                    'type' => 'department',
                    'parent_id' => $faculty->id
                ]);
            }
        }
    }
}
