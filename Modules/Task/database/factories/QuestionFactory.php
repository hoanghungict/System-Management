<?php

namespace Modules\Task\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\app\Models\Question;
use Modules\Task\app\Models\QuestionBank;
use Modules\Task\app\Models\Chapter;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    private static int $orderCounter = 0;

    public function definition(): array
    {
        $options = [
            ['key' => 'A', 'text' => $this->faker->sentence()],
            ['key' => 'B', 'text' => $this->faker->sentence()],
            ['key' => 'C', 'text' => $this->faker->sentence()],
            ['key' => 'D', 'text' => $this->faker->sentence()],
        ];

        return [
            'question_bank_id' => QuestionBank::factory(),
            'chapter_id' => Chapter::factory(),
            'subject_code' => strtoupper($this->faker->lexify('???')) . $this->faker->numerify('###'),
            'assignment_id' => null,
            'type' => 'multiple_choice',
            'content' => $this->faker->paragraph(),
            'options' => $options,
            'correct_answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'points' => 1.0,
            'order_index' => ++self::$orderCounter,
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'rubric' => null,
            'explanation' => $this->faker->paragraph(),
        ];
    }

    /**
     * Câu hỏi trắc nghiệm
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiple_choice',
        ]);
    }

    /**
     * Câu hỏi tự luận
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'essay',
            'options' => null,
            'correct_answer' => null,
        ]);
    }

    /**
     * Câu hỏi trả lời ngắn
     */
    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'short_answer',
            'options' => null,
            'correct_answer' => $this->faker->word() . '|' . $this->faker->word(),
        ]);
    }

    /**
     * Câu hỏi dễ
     */
    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'easy',
        ]);
    }

    /**
     * Câu hỏi trung bình
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'medium',
        ]);
    }

    /**
     * Câu hỏi khó
     */
    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'hard',
        ]);
    }
}
