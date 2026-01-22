<?php

namespace Modules\Task\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Task\app\Models\QuestionBank;
use Modules\Task\app\Models\Chapter;
use Modules\Task\app\Models\Question;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Task\app\Imports\QuestionBankImport;

/**
 * QuestionBankController
 * Quản lý ngân hàng câu hỏi
 */
class QuestionBankController extends Controller
{
    /**
     * Lấy danh sách ngân hàng câu hỏi
     */
    public function index(Request $request): JsonResponse
    {
        $lecturerId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $questionBanks = QuestionBank::byLecturer($lecturerId)
            ->active()
            ->with(['course:id,name,code', 'chapters' => function($q) {
                $q->orderBy('order_index');
            }])
            ->withCount(['questions', 'chapters', 'exams'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $questionBanks
        ]);
    }

    /**
     * Tạo ngân hàng câu hỏi mới
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'subject_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $lecturerId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $questionBank = QuestionBank::create([
            ...$validator->validated(),
            'lecturer_id' => $lecturerId,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo ngân hàng câu hỏi thành công',
            'data' => $questionBank
        ], 201);
    }

    /**
     * Chi tiết ngân hàng câu hỏi
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $questionBank = QuestionBank::with(['chapters' => function ($q) {
                $q->withCount('questions');
            }])
            ->withCount('questions')
            ->find($id);

        if (!$questionBank) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ngân hàng câu hỏi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($questionBank->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Thống kê theo độ khó
        $difficultyStats = $questionBank->questions_by_difficulty;

        return response()->json([
            'success' => true,
            'data' => [
                'question_bank' => $questionBank,
                'difficulty_stats' => $difficultyStats,
            ]
        ]);
    }

    /**
     * Cập nhật ngân hàng câu hỏi
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $questionBank = QuestionBank::find($id);

        if (!$questionBank) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ngân hàng câu hỏi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($questionBank->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'subject_code' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $questionBank->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $questionBank->fresh()
        ]);
    }

    /**
     * Xóa ngân hàng câu hỏi
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $questionBank = QuestionBank::find($id);

        if (!$questionBank) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ngân hàng câu hỏi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($questionBank->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Kiểm tra có đề thi nào đang dùng không
        if ($questionBank->exams()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa ngân hàng đang được sử dụng trong đề thi'
            ], 422);
        }

        $questionBank->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa thành công'
        ]);
    }

    /**
     * Lấy danh sách câu hỏi trong ngân hàng
     */
    public function getQuestions(Request $request, int $id): JsonResponse
    {
        $questionBank = QuestionBank::find($id);

        if (!$questionBank) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ngân hàng câu hỏi'], 404);
        }

        $query = $questionBank->questions()->with('chapter:id,name,code');

        // Filter by chapter
        if ($request->has('chapter_id')) {
            $query->where('chapter_id', $request->chapter_id);
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Search
        if ($request->has('search')) {
            $query->where('content', 'like', '%' . $request->search . '%');
        }

        $questions = $query->orderBy('chapter_id')
            ->orderBy('difficulty')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * Import câu hỏi từ Excel
     */
    public function importQuestions(Request $request, int $id): JsonResponse
    {
        $questionBank = QuestionBank::find($id);

        if (!$questionBank) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ngân hàng câu hỏi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($questionBank->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $import = new QuestionBankImport($questionBank);
            Excel::import($import, $request->file('file'));

            $result = $import->getResult();

            return response()->json([
                'success' => true,
                'message' => "Import thành công {$result['success']} câu hỏi",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import thất bại: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Thêm chương vào ngân hàng
     */
    public function addChapter(Request $request, int $id): JsonResponse
    {
        $questionBank = QuestionBank::find($id);

        if (!$questionBank) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ngân hàng câu hỏi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($questionBank->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $maxOrder = $questionBank->chapters()->max('order_index') ?? 0;

        $chapter = Chapter::create([
            'question_bank_id' => $id,
            'name' => $request->name,
            'code' => $request->code,
            'order_index' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm chương thành công',
            'data' => $chapter
        ], 201);
    }

    /**
     * Cập nhật chương
     */
    public function updateChapter(Request $request, int $id, int $chapterId): JsonResponse
    {
        $chapter = Chapter::where('question_bank_id', $id)->find($chapterId);

        if (!$chapter) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy chương'], 404);
        }

        $chapter->update($request->only(['name', 'code', 'order_index']));

        return response()->json([
            'success' => true,
            'data' => $chapter
        ]);
    }

    /**
     * Xóa chương
     */
    public function deleteChapter(Request $request, int $id, int $chapterId): JsonResponse
    {
        $chapter = Chapter::where('question_bank_id', $id)->find($chapterId);

        if (!$chapter) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy chương'], 404);
        }

        // Xóa chapter_id của các câu hỏi thuộc chương này
        Question::where('chapter_id', $chapterId)->update(['chapter_id' => null]);

        $chapter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa chương thành công'
        ]);
    }

}
