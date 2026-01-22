<?php

namespace Modules\Auth\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\app\Models\Student;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'birth_date' => $this->faker->date('Y-m-d', '-20 years'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'student_code' => 'SV' . $this->faker->unique()->numerify('########'),
            'class_id' => null,
            'imported_at' => now(),
            'import_job_id' => null,
            'account_status' => 'active',
        ];
    }

    /**
     * Sinh viên active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'active',
        ]);
    }

    /**
     * Sinh viên inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'inactive',
        ]);
    }
}
