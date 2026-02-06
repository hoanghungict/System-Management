<?php

namespace Modules\Task\Tests\Unit\Services;

use Tests\TestCase;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\QuestionBank;
use Modules\Task\app\Models\Question;
use Modules\Task\app\Models\Chapter;
use Modules\Task\app\Models\ExamCode;
use Modules\Task\app\Services\ExamCodeGeneratorService;
use Modules\Auth\app\Models\Lecturer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit Test cho ExamCodeGeneratorService
 * Test logic tạo mã đề, phân bố câu hỏi theo độ khó, xáo trộn câu hỏi/đáp án
 */
class ExamCodeGeneratorServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected ExamCodeGeneratorService $service;
    protected Lecturer $lecturer;
    protected QuestionBank $questionBank;
    protected array $chapters = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExamCodeGeneratorService();

        // Tạo lecturer và question bank
        $this->lecturer = Lecturer::factory()->create();
        $this->questionBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        // Tạo 3 chương
        for ($i = 1; $i <= 3; $i++) {
            $this->chapters[] = Chapter::factory()->create([
                'question_bank_id' => $this->questionBank->id,
                'name' => "Chương $i",
                'order_index' => $i,
            ]);
        }

        // Tạo câu hỏi cho mỗi chương
        foreach ($this->chapters as $chapter) {
            Question::factory()->count(20)->create([
                'question_bank_id' => $this->questionBank->id,
                'chapter_id' => $chapter->id,
                'difficulty' => 'easy',
            ]);

            Question::factory()->count(15)->create([
                'question_bank_id' => $this->questionBank->id,
                'chapter_id' => $chapter->id,
                'difficulty' => 'medium',
            ]);

            Question::factory()->count(10)->create([
                'question_bank_id' => $this->questionBank->id,
                'chapter_id' => $chapter->id,
                'difficulty' => 'hard',
            ]);
        }
    }

    // ==================== Basic Generation Tests ====================

    #[Test]
    public function it_generates_correct_number_of_exam_codes()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 30,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 10,
                'hard' => 5,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 4);

        $this->assertCount(4, $codes);

        // Kiểm tra format mã đề
        $this->assertEquals('001', $codes[0]->code);
        $this->assertEquals('002', $codes[1]->code);
        $this->assertEquals('003', $codes[2]->code);
        $this->assertEquals('004', $codes[3]->code);
    }

    #[Test]
    public function each_exam_code_has_correct_number_of_questions()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 30,
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 10,
                'hard' => 5,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 2);

        foreach ($codes as $code) {
            $this->assertCount(30, $code->question_order);
        }
    }

    // ==================== Difficulty Distribution Tests ====================

    #[Test]
    public function it_respects_difficulty_config()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 30,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 10,
                'hard' => 5,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);
        $code = $codes[0];

        // Đếm số câu theo độ khó
        $questionIds = $code->question_order;
        $questions = Question::whereIn('id', $questionIds)->get();

        $easyCount = $questions->where('difficulty', 'easy')->count();
        $mediumCount = $questions->where('difficulty', 'medium')->count();
        $hardCount = $questions->where('difficulty', 'hard')->count();

        $this->assertEquals(15, $easyCount, 'Số câu dễ không đúng');
        $this->assertEquals(10, $mediumCount, 'Số câu trung bình không đúng');
        $this->assertEquals(5, $hardCount, 'Số câu khó không đúng');
    }

    #[Test]
    public function it_distributes_questions_across_chapters()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 30,
            'shuffle_questions' => false,
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 9,
                'hard' => 6,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);
        $questionIds = $codes[0]->question_order;
        $questions = Question::whereIn('id', $questionIds)->get();

        // Kiểm tra mỗi chương đều có câu hỏi
        foreach ($this->chapters as $chapter) {
            $chapterQuestions = $questions->where('chapter_id', $chapter->id)->count();
            $this->assertGreaterThan(0, $chapterQuestions, "Chương {$chapter->name} không có câu hỏi");
        }
    }

    // ==================== Shuffle Tests ====================

    #[Test]
    public function it_shuffles_questions_when_enabled()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 20,
            'shuffle_questions' => true,
            'shuffle_options' => false,
            'difficulty_config' => [
                'easy' => 10,
                'medium' => 7,
                'hard' => 3,
            ],
        ]);

        // Tạo 2 mã đề
        $codes = $this->service->generateExamCodes($exam, 2);

        // So sánh thứ tự câu hỏi giữa 2 mã đề
        $order1 = $codes[0]->question_order;
        $order2 = $codes[1]->question_order;

        // Thứ tự nên khác nhau (xác suất trùng rất thấp)
        $this->assertNotEquals($order1, $order2, 'Thứ tự câu hỏi 2 mã đề nên khác nhau');

        // Nhưng chứa cùng các câu hỏi
        sort($order1);
        sort($order2);
        $this->assertEquals($order1, $order2, 'Hai mã đề phải chứa cùng các câu hỏi');
    }

    #[Test]
    public function it_generates_option_shuffle_map_when_enabled()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 20,
            'shuffle_questions' => false,
            'shuffle_options' => true,
            'difficulty_config' => [
                'easy' => 10,
                'medium' => 7,
                'hard' => 3,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);
        $code = $codes[0];

        // Kiểm tra có option_shuffle_map
        $this->assertNotNull($code->option_shuffle_map);
        $this->assertIsArray($code->option_shuffle_map);

        // Kiểm tra format của shuffle map
        if (!empty($code->option_shuffle_map)) {
            $firstQuestionId = array_key_first($code->option_shuffle_map);
            $mapping = $code->option_shuffle_map[$firstQuestionId];

            // Phải có đủ 4 key A, B, C, D
            $this->assertArrayHasKey('A', $mapping);
            $this->assertArrayHasKey('B', $mapping);
            $this->assertArrayHasKey('C', $mapping);
            $this->assertArrayHasKey('D', $mapping);

            // Giá trị phải là A, B, C, D (bị xáo trộn)
            $values = array_values($mapping);
            sort($values);
            $this->assertEquals(['A', 'B', 'C', 'D'], $values);
        }
    }

    #[Test]
    public function it_does_not_shuffle_when_disabled()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 10,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'difficulty_config' => [
                'easy' => 5,
                'medium' => 3,
                'hard' => 2,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);
        $code = $codes[0];

        // Không có option_shuffle_map
        $this->assertNull($code->option_shuffle_map);
    }

    // ==================== Validation Tests ====================

    #[Test]
    public function it_throws_exception_when_question_bank_has_no_chapters()
    {
        $emptyBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $emptyBank->id,
            'total_questions' => 30,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ngân hàng câu hỏi chưa có chương nào');

        $this->service->generateExamCodes($exam, 1);
    }

    #[Test]
    public function it_throws_exception_when_not_enough_questions()
    {
        // Tạo bank với ít câu hỏi
        $smallBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        $chapter = Chapter::factory()->create([
            'question_bank_id' => $smallBank->id,
        ]);

        // Chỉ tạo 5 câu dễ
        Question::factory()->count(5)->create([
            'question_bank_id' => $smallBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'easy',
        ]);

        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $smallBank->id,
            'total_questions' => 30,
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 10,
                'hard' => 5,
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Thiếu câu hỏi mức/');

        $this->service->generateExamCodes($exam, 1);
    }

    #[Test]
    public function it_throws_exception_when_question_bank_null()
    {
        $exam = new Exam([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => null,
            'total_questions' => 30,
        ]);
        $exam->id = 999;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Đề thi chưa được liên kết với ngân hàng câu hỏi');

        $this->service->generateExamCodes($exam, 1);
    }

    // ==================== Suggested Config Tests ====================

    #[Test]
    public function it_calculates_correct_suggested_config()
    {
        $config = $this->service->getSuggestedConfig(90);

        $this->assertEquals(90, $config['time_limit']);
        $this->assertEquals(60, $config['total_questions']); // 90 / 1.5 = 60
        $this->assertEquals(1.5, $config['minutes_per_question']);

        // Kiểm tra difficulty config
        $this->assertEquals(30, $config['difficulty_config']['easy']);  // 60 * 0.5
        $this->assertEquals(20, $config['difficulty_config']['medium']); // 60 * 0.33
        $this->assertEquals(10, $config['difficulty_config']['hard']);   // 60 * 0.17
    }

    #[Test]
    #[DataProvider('timeLimitProvider')]
    public function it_calculates_suggested_questions_for_various_time_limits(
        int $timeLimit,
        int $expectedQuestions
    ) {
        $config = $this->service->getSuggestedConfig($timeLimit);

        $this->assertEquals($expectedQuestions, $config['total_questions']);
    }

    public static function timeLimitProvider(): array
    {
        return [
            '45 phút' => [45, 30],
            '60 phút' => [60, 40],
            '90 phút' => [90, 60],
            '120 phút' => [120, 80],
            '150 phút' => [150, 100],
        ];
    }

    // ==================== Uniqueness Tests ====================

    #[Test]
    public function each_exam_code_has_unique_questions()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 20,
            'difficulty_config' => [
                'easy' => 10,
                'medium' => 7,
                'hard' => 3,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);
        $questionIds = $codes[0]->question_order;

        // Không có câu hỏi trùng lặp trong 1 mã đề
        $uniqueIds = array_unique($questionIds);
        $this->assertCount(count($questionIds), $uniqueIds, 'Có câu hỏi bị trùng lặp');
    }

    // ==================== Database Persistence Tests ====================

    #[Test]
    public function it_persists_exam_codes_to_database()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 20,
            'difficulty_config' => [
                'easy' => 10,
                'medium' => 7,
                'hard' => 3,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 3);

        $this->assertDatabaseCount('exam_codes', 3);

        foreach ($codes as $code) {
            $this->assertDatabaseHas('exam_codes', [
                'id' => $code->id,
                'exam_id' => $exam->id,
                'code' => $code->code,
            ]);
        }
    }

    // ==================== Edge Cases ====================

    #[Test]
    public function it_handles_exam_with_zero_questions_for_some_difficulty()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'total_questions' => 25,
            'difficulty_config' => [
                'easy' => 25,
                'medium' => 0,
                'hard' => 0,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);

        $questionIds = $codes[0]->question_order;
        $questions = Question::whereIn('id', $questionIds)->get();

        $this->assertCount(25, $questions);
        $this->assertEquals(25, $questions->where('difficulty', 'easy')->count());
    }

    #[Test]
    public function it_handles_single_chapter()
    {
        // Tạo bank với 1 chương duy nhất
        $singleChapterBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        $chapter = Chapter::factory()->create([
            'question_bank_id' => $singleChapterBank->id,
        ]);

        Question::factory()->count(30)->create([
            'question_bank_id' => $singleChapterBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'easy',
        ]);

        Question::factory()->count(20)->create([
            'question_bank_id' => $singleChapterBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'medium',
        ]);

        Question::factory()->count(10)->create([
            'question_bank_id' => $singleChapterBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'hard',
        ]);

        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $singleChapterBank->id,
            'total_questions' => 30,
            'difficulty_config' => [
                'easy' => 15,
                'medium' => 10,
                'hard' => 5,
            ],
        ]);

        $codes = $this->service->generateExamCodes($exam, 1);

        $this->assertCount(30, $codes[0]->question_order);
    }
}
