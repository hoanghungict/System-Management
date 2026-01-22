<?php

namespace Modules\Task\Tests\Feature;

use Tests\TestCase;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\QuestionBank;
use Modules\Task\app\Models\Question;
use Modules\Task\app\Models\Chapter;
use Modules\Task\app\Models\ExamCode;
use Modules\Task\app\Models\ExamSubmission;
use Modules\Auth\app\Models\Lecturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test API Exam cho Giảng viên
 * Bao gồm: CRUD đề thi, tạo mã đề, publish, xem submissions
 */
class LecturerExamApiTest extends TestCase
{
    use RefreshDatabase;

    protected Lecturer $lecturer;
    protected QuestionBank $questionBank;
    protected string $authHeader;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo lecturer
        $this->lecturer = Lecturer::factory()->create();

        // Tạo question bank với chapters và questions
        $this->questionBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        $chapter = Chapter::factory()->create([
            'question_bank_id' => $this->questionBank->id,
        ]);

        // Tạo đủ câu hỏi theo độ khó
        Question::factory()->count(30)->create([
            'question_bank_id' => $this->questionBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'easy',
        ]);

        Question::factory()->count(20)->create([
            'question_bank_id' => $this->questionBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'medium',
        ]);

        Question::factory()->count(10)->create([
            'question_bank_id' => $this->questionBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'hard',
        ]);

        // Mock auth header (tùy theo cách authenticate của hệ thống)
        $this->authHeader = 'Bearer ' . $this->generateTestToken($this->lecturer);
    }

    /**
     * Helper: Generate test token
     */
    protected function generateTestToken(Lecturer $lecturer): string
    {
        // TODO: Implement theo cách authenticate của hệ thống
        return 'test_token_' . $lecturer->id;
    }

    // ==================== CRUD Tests ====================

    #[Test]
    public function it_can_list_exams_for_lecturer()
    {
        // Tạo vài đề thi
        Exam::factory()->count(3)->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson('/api/lecturer/exams');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'status',
                            'time_limit',
                            'total_questions',
                        ]
                    ],
                    'current_page',
                    'total',
                ]
            ]);
    }

    #[Test]
    public function it_can_create_exam()
    {
        $examData = [
            'question_bank_id' => $this->questionBank->id,
            'title' => 'Bài kiểm tra giữa kỳ',
            'description' => 'Mô tả bài kiểm tra',
            'time_limit' => 90,
            'total_questions' => 60,
            'max_attempts' => 1,
            'shuffle_questions' => true,
            'shuffle_options' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson('/api/lecturer/exams', $examData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'status',
                    'difficulty_config',
                ]
            ]);

        $this->assertDatabaseHas('exams', [
            'title' => 'Bài kiểm tra giữa kỳ',
            'lecturer_id' => $this->lecturer->id,
            'status' => 'draft',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_exam()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson('/api/lecturer/exams', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'question_bank_id',
                    'title',
                    'time_limit',
                    'total_questions',
                ]
            ]);
    }

    #[Test]
    public function it_can_show_exam_detail()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/lecturer/exams/{$exam->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'time_limit',
                    'total_questions',
                    'question_bank',
                ]
            ]);
    }

    #[Test]
    public function it_returns_404_for_not_found_exam()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson('/api/lecturer/exams/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Không tìm thấy đề thi',
            ]);
    }

    #[Test]
    public function it_can_update_exam()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'draft',
        ]);

        $updateData = [
            'title' => 'Bài kiểm tra cuối kỳ (đã sửa)',
            'time_limit' => 120,
        ];

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->putJson("/api/lecturer/exams/{$exam->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cập nhật đề thi thành công',
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'Bài kiểm tra cuối kỳ (đã sửa)',
            'time_limit' => 120,
        ]);
    }

    #[Test]
    public function it_cannot_update_published_exam_with_submissions()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'published',
        ]);

        // Tạo exam code và submission
        $examCode = ExamCode::factory()->create(['exam_id' => $exam->id]);
        ExamSubmission::factory()->create([
            'exam_id' => $exam->id,
            'exam_code_id' => $examCode->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->putJson("/api/lecturer/exams/{$exam->id}", [
            'title' => 'Cố gắng sửa',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Không thể sửa đề thi đã có sinh viên làm bài',
            ]);
    }

    #[Test]
    public function it_can_delete_exam()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->deleteJson("/api/lecturer/exams/{$exam->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Xóa đề thi thành công',
            ]);

        $this->assertSoftDeleted('exams', ['id' => $exam->id]);
    }

    // ==================== Exam Code Tests ====================

    #[Test]
    public function it_can_generate_exam_codes()
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

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/lecturer/exams/{$exam->id}/generate-codes", [
            'count' => 4,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $this->assertDatabaseCount('exam_codes', 4);
    }

    #[Test]
    public function it_fails_to_generate_codes_when_not_enough_questions()
    {
        // Tạo question bank mới không có đủ câu hỏi
        $emptyBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        Chapter::factory()->create([
            'question_bank_id' => $emptyBank->id,
        ]);

        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $emptyBank->id,
            'total_questions' => 60,
            'difficulty_config' => [
                'easy' => 30,
                'medium' => 20,
                'hard' => 10,
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/lecturer/exams/{$exam->id}/generate-codes", [
            'count' => 4,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_can_get_exam_codes_list()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
        ]);

        ExamCode::factory()->count(4)->create(['exam_id' => $exam->id]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/lecturer/exams/{$exam->id}/codes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'bank_stats',
            ]);
    }

    // ==================== Publish/Close Tests ====================

    #[Test]
    public function it_can_publish_exam()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'draft',
        ]);

        // Tạo exam codes trước
        ExamCode::factory()->count(2)->create(['exam_id' => $exam->id]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/lecturer/exams/{$exam->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Đề thi đã được publish',
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'status' => 'published',
        ]);
    }

    #[Test]
    public function it_cannot_publish_exam_without_codes()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/lecturer/exams/{$exam->id}/publish");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Vui lòng tạo mã đề trước khi publish',
            ]);
    }

    #[Test]
    public function it_can_close_exam()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'published',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/lecturer/exams/{$exam->id}/close");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Đề thi đã được đóng',
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'status' => 'closed',
        ]);
    }

    // ==================== Submission Tests ====================

    #[Test]
    public function it_can_get_exam_submissions()
    {
        $exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
        ]);

        $examCode = ExamCode::factory()->create(['exam_id' => $exam->id]);

        ExamSubmission::factory()->count(5)->create([
            'exam_id' => $exam->id,
            'exam_code_id' => $examCode->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/lecturer/exams/{$exam->id}/submissions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'student',
                            'exam_code',
                            'total_score',
                            'status',
                        ]
                    ],
                ]
            ]);
    }

    #[Test]
    public function it_can_get_suggested_config()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson('/api/lecturer/exams/suggested-config?time_limit=90');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'time_limit',
                    'total_questions',
                    'difficulty_config' => [
                        'easy',
                        'medium',
                        'hard',
                    ],
                    'minutes_per_question',
                ]
            ]);
    }

    // ==================== Authorization Tests ====================

    #[Test]
    public function it_cannot_access_other_lecturer_exam()
    {
        $otherLecturer = Lecturer::factory()->create();
        $exam = Exam::factory()->create([
            'lecturer_id' => $otherLecturer->id,
            'question_bank_id' => $this->questionBank->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/lecturer/exams/{$exam->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    #[Test]
    public function it_returns_401_without_auth()
    {
        $response = $this->getJson('/api/lecturer/exams');

        $response->assertStatus(401);
    }
}
