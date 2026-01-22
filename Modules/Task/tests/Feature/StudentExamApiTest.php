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
use Modules\Auth\app\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;

/**
 * Test API Exam cho Sinh viên
 * Bao gồm: Xem đề thi, bắt đầu làm bài, lưu câu trả lời, nộp bài, xem kết quả
 */
class StudentExamApiTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Lecturer $lecturer;
    protected QuestionBank $questionBank;
    protected Exam $exam;
    protected ExamCode $examCode;
    protected string $authHeader;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo student
        $this->student = Student::factory()->create();

        // Tạo lecturer và question bank
        $this->lecturer = Lecturer::factory()->create();
        $this->questionBank = QuestionBank::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);

        $chapter = Chapter::factory()->create([
            'question_bank_id' => $this->questionBank->id,
        ]);

        // Tạo câu hỏi
        Question::factory()->count(30)->create([
            'question_bank_id' => $this->questionBank->id,
            'chapter_id' => $chapter->id,
            'difficulty' => 'easy',
        ]);

        // Tạo đề thi đã publish
        $this->exam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'published',
            'max_attempts' => 2,
            'time_limit' => 60,
            'total_questions' => 30,
            'show_answers_after_submit' => true,
            'anti_cheat_enabled' => true,
        ]);

        // Tạo exam code
        $this->examCode = ExamCode::factory()->create([
            'exam_id' => $this->exam->id,
            'question_order' => Question::take(30)->pluck('id')->toArray(),
        ]);

        // Mock auth header
        $this->authHeader = 'Bearer ' . $this->generateTestToken($this->student);
    }

    /**
     * Helper: Generate test token
     */
    protected function generateTestToken(Student $student): string
    {
        // TODO: Implement theo cách authenticate của hệ thống
        return 'test_token_student_' . $student->id;
    }

    // ==================== List & Show Tests ====================

    #[Test]
    public function it_can_list_active_exams()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson('/api/student/exams');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'time_limit',
                            'total_questions',
                            'max_attempts',
                        ]
                    ],
                ]
            ]);
    }

    #[Test]
    public function it_can_show_exam_detail_before_start()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/{$this->exam->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'exam' => [
                        'id',
                        'title',
                        'time_limit',
                        'total_questions',
                    ],
                    'my_submissions',
                    'submitted_count',
                    'can_attempt',
                    'in_progress_submission',
                ]
            ])
            ->assertJsonPath('data.can_attempt', true);
    }

    #[Test]
    public function it_returns_404_for_unpublished_exam()
    {
        $draftExam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/{$draftExam->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Đề thi không tồn tại hoặc chưa mở',
            ]);
    }

    // ==================== Start Exam Tests ====================

    #[Test]
    public function it_can_start_exam()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/{$this->exam->id}/start");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'submission' => [
                        'id',
                        'exam_id',
                        'exam_code',
                        'attempt',
                        'started_at',
                        'remaining_time',
                        'answers',
                        'status',
                    ],
                    'exam' => [
                        'id',
                        'title',
                        'time_limit',
                        'total_questions',
                        'anti_cheat_enabled',
                    ],
                    'questions' => [
                        '*' => [
                            'id',
                            'content',
                            'type',
                            'options',
                        ]
                    ],
                ]
            ]);

        $this->assertDatabaseHas('exam_submissions', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'attempt' => 1,
        ]);
    }

    #[Test]
    public function it_resumes_in_progress_submission()
    {
        // Tạo submission đang làm dở
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10),
            'answers' => ['1' => 'A', '2' => 'B'],
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/{$this->exam->id}/start");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tiếp tục bài làm',
            ])
            ->assertJsonPath('data.submission.id', $submission->id)
            ->assertJsonPath('data.submission.answers', ['1' => 'A', '2' => 'B']);
    }

    #[Test]
    public function it_cannot_start_when_max_attempts_reached()
    {
        // Tạo 2 submission đã nộp (max_attempts = 2)
        ExamSubmission::factory()->count(2)->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/{$this->exam->id}/start");

        $response->assertStatus(422)
            ->assertJsonPath('message', "Bạn đã hết {$this->exam->max_attempts} lượt làm bài");
    }

    // ==================== Save Answer Tests ====================

    #[Test]
    public function it_can_save_answer()
    {
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'answers' => [],
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/submissions/{$submission->id}/answer", [
            'question_id' => 1,
            'answer' => 'A',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Đã lưu câu trả lời',
            ]);

        $this->assertEquals('A', $submission->fresh()->answers[1]);
    }

    #[Test]
    public function it_auto_submits_when_time_exceeded()
    {
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(120), // Quá hạn
            'answers' => [],
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/submissions/{$submission->id}/answer", [
            'question_id' => 1,
            'answer' => 'A',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Hết thời gian, bài đã được tự động nộp',
                'auto_submitted' => true,
            ]);
    }

    #[Test]
    public function it_returns_404_for_invalid_submission()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson('/api/student/exams/submissions/99999/answer', [
            'question_id' => 1,
            'answer' => 'A',
        ]);

        $response->assertStatus(404);
    }

    // ==================== Submit Tests ====================

    #[Test]
    public function it_can_submit_exam()
    {
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
            'answers' => ['1' => 'A', '2' => 'B'],
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/submissions/{$submission->id}/submit", [
            'answers' => ['3' => 'C'], // Thêm câu trả lời cuối
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'submission',
                    'show_answers',
                    'question_details',
                ]
            ]);

        $this->assertDatabaseHas('exam_submissions', [
            'id' => $submission->id,
            'status' => 'submitted',
        ]);
    }

    #[Test]
    public function it_cannot_submit_other_student_submission()
    {
        $otherStudent = Student::factory()->create();
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $otherStudent->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/submissions/{$submission->id}/submit");

        $response->assertStatus(404);
    }

    // ==================== Get Result Tests ====================

    #[Test]
    public function it_can_get_result_when_show_answers_enabled()
    {
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'submitted_at' => now(),
            'total_score' => 8.5,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/submissions/{$submission->id}/result");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'submission',
                    'show_answers',
                    'question_details' => [
                        '*' => [
                            'id',
                            'content',
                            'options',
                            'student_answer',
                            'correct_answer',
                            'is_correct',
                            'explanation',
                        ]
                    ],
                ]
            ])
            ->assertJsonPath('data.show_answers', true);
    }

    #[Test]
    public function it_hides_answers_when_show_answers_disabled()
    {
        // Cập nhật exam để không hiện đáp án
        $this->exam->update(['show_answers_after_submit' => false]);

        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/submissions/{$submission->id}/result");

        $response->assertStatus(200)
            ->assertJsonPath('data.show_answers', false)
            ->assertJsonMissing(['question_details']);
    }

    #[Test]
    public function it_cannot_get_result_of_in_progress_submission()
    {
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/submissions/{$submission->id}/result");

        $response->assertStatus(404);
    }

    // ==================== Anti-Cheat Tests ====================

    #[Test]
    public function it_can_log_violation()
    {
        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/submissions/{$submission->id}/violation", [
            'type' => 'tab_switch',
            'details' => ['count' => 1],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function it_ignores_violation_when_anti_cheat_disabled()
    {
        $this->exam->update(['anti_cheat_enabled' => false]);

        $submission = ExamSubmission::factory()->create([
            'exam_id' => $this->exam->id,
            'exam_code_id' => $this->examCode->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->postJson("/api/student/exams/submissions/{$submission->id}/violation", [
            'type' => 'tab_switch',
        ]);

        $response->assertStatus(200);
    }

    // ==================== Authorization Tests ====================

    #[Test]
    public function it_returns_401_without_auth()
    {
        $response = $this->getJson('/api/student/exams');

        $response->assertStatus(401);
    }

    // ==================== Time-based Tests ====================

    #[Test]
    public function it_cannot_access_exam_before_start_time()
    {
        $futureExam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'published',
            'start_time' => now()->addDays(1),
        ]);

        ExamCode::factory()->create(['exam_id' => $futureExam->id]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/{$futureExam->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_cannot_access_exam_after_end_time()
    {
        $expiredExam = Exam::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'question_bank_id' => $this->questionBank->id,
            'status' => 'published',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(1),
        ]);

        ExamCode::factory()->create(['exam_id' => $expiredExam->id]);

        $response = $this->withHeaders([
            'Authorization' => $this->authHeader,
        ])->getJson("/api/student/exams/{$expiredExam->id}");

        $response->assertStatus(404);
    }
}
