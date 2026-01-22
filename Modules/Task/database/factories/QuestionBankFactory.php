<?php

namespace Modules\Task\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\app\Models\QuestionBank;
use Modules\Auth\app\Models\Lecturer;

class QuestionBankFactory extends Factory
{
    protected $model = QuestionBank::class;

    public function definition(): array
    {
        return [
            'course_id' => null,
            'lecturer_id' => Lecturer::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'subject_code' => strtoupper($this->faker->lexify('???')) . $this->faker->numerify('###'),
            'status' => 'active',
            'material_id' => null,
        ];
    }

    /**
     * Ngân hàng câu hỏi active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Ngân hàng câu hỏi inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
