<?php

namespace Modules\Task\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\app\Models\ExamSubmission;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\ExamCode;
use Modules\Auth\app\Models\Student;

class ExamSubmissionFactory extends Factory
{
    protected $model = ExamSubmission::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'exam_code_id' => ExamCode::factory(),
            'student_id' => Student::factory(),
            'attempt' => 1,
            'started_at' => now(),
            'submitted_at' => null,
            'correct_count' => 0,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'total_score' => null,
            'manual_score' => null,
            'graded_by' => null,
            'graded_at' => null,
            'grader_note' => null,
            'status' => 'in_progress',
            'anti_cheat_violations' => [],
            'answers' => [],
        ];
    }

    /**
     * Bài làm đang trong quá trình
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'submitted_at' => null,
        ]);
    }

    /**
     * Bài làm đã nộp
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
            'correct_count' => $this->faker->numberBetween(10, 25),
            'wrong_count' => $this->faker->numberBetween(0, 10),
            'unanswered_count' => $this->faker->numberBetween(0, 5),
            'total_score' => $this->faker->randomFloat(2, 4, 10),
        ]);
    }

    /**
     * Bài làm đã chấm điểm
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'submitted_at' => now()->subHour(),
            'graded_at' => now(),
            'total_score' => $this->faker->randomFloat(2, 4, 10),
            'manual_score' => $this->faker->randomFloat(2, 4, 10),
        ]);
    }

    /**
     * Bài làm với câu trả lời
     */
    public function withAnswers(array $answers): static
    {
        return $this->state(fn (array $attributes) => [
            'answers' => $answers,
        ]);
    }
}
