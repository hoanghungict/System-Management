<?php

namespace Modules\Auth\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\app\Models\Lecturer;

class LecturerFactory extends Factory
{
    protected $model = Lecturer::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'lecturer_code' => 'GV' . $this->faker->unique()->numerify('######'),
            'department_id' => null,
            'experience_number' => $this->faker->numberBetween(1, 20),
            'birth_date' => $this->faker->date('Y-m-d', '-25 years'),
        ];
    }
}
