<?php

namespace Modules\Task\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\QuestionBank;
use Modules\Auth\app\Models\Lecturer;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'question_bank_id' => QuestionBank::factory(),
            'lecturer_id' => Lecturer::factory(),
            'course_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'time_limit' => $this->faker->randomElement([45, 60, 90, 120]),
            'total_questions' => $this->faker->randomElement([20, 30, 40, 50, 60]),
            'max_attempts' => $this->faker->randomElement([1, 2, 3]),
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 10,
                'hard' => 5,
            ],
            'exam_codes_count' => 4,
            'show_answers_after_submit' => $this->faker->boolean(70),
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'anti_cheat_enabled' => $this->faker->boolean(50),
            'status' => 'draft',
            'start_time' => null,
            'end_time' => null,
        ];
    }

    /**
     * Đề thi đã publish
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Đề thi đã đóng
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Đề thi có thời gian bắt đầu/kết thúc
     */
    public function withSchedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now(),
            'end_time' => now()->addDays(7),
        ]);
    }
}
