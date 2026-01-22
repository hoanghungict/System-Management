<?php

namespace Modules\Task\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\app\Models\Chapter;
use Modules\Task\app\Models\QuestionBank;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    private static int $orderCounter = 0;

    public function definition(): array
    {
        return [
            'question_bank_id' => QuestionBank::factory(),
            'name' => 'Chương ' . $this->faker->numberBetween(1, 20) . ': ' . $this->faker->sentence(3),
            'code' => strtoupper($this->faker->lexify('CH??')) . $this->faker->numerify('##'),
            'order_index' => ++self::$orderCounter,
        ];
    }

    /**
     * Đặt thứ tự cụ thể
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_index' => $order,
        ]);
    }
}
