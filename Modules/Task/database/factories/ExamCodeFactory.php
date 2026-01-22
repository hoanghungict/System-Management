<?php

namespace Modules\Task\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\app\Models\ExamCode;
use Modules\Task\app\Models\Exam;

class ExamCodeFactory extends Factory
{
    protected $model = ExamCode::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'code' => str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'question_order' => [],
            'option_shuffle_map' => null,
        ];
    }

    /**
     * Mã đề với câu hỏi đã xáo trộn
     */
    public function withQuestions(array $questionIds): static
    {
        return $this->state(fn (array $attributes) => [
            'question_order' => $questionIds,
        ]);
    }

    /**
     * Mã đề với đáp án đã xáo trộn
     */
    public function withShuffledOptions(array $shuffleMap): static
    {
        return $this->state(fn (array $attributes) => [
            'option_shuffle_map' => $shuffleMap,
        ]);
    }
}
