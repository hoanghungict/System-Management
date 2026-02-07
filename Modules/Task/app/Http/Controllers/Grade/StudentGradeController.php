<?php

namespace Modules\Task\app\Http\Controllers\Grade;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Models\Attendance\Course;
use Modules\Task\app\Models\AssignmentSubmission;
use Modules\Task\app\Models\ExamSubmission;
use Modules\Auth\app\Models\Student;

class StudentGradeController extends Controller
{
    /**
     * Get Student Information and Summary Stats
     */
    public function getStudentInfo(Request $request)
    {
        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $student = $this->resolveStudent($userId, $request->user());
        if (!$student) {
             return response()->json(['message' => 'Student profile not found'], 404);
        }

        // Fetch relationships
        $student->load(['classroom.department']);

        $className = $student->classroom ? ($student->classroom->class_name ?? $student->classroom->code) : 'N/A';
        $majorName = $student->classroom && $student->classroom->department ? $student->classroom->department->name : 'N/A';
        $academicYear = $student->classroom ? $student->classroom->school_year : '2024-2025';

        // Calculate credits and GPA
        $passedCredits = 0;
        $totalWeightedScore10 = 0;
        $totalWeightedScore4 = 0;
        $totalCreditsCalculated = 0;

        // Fetch courses enrolled by student
        $courses = Course::join('course_enrollments', 'courses.id', '=', 'course_enrollments.course_id')
            ->where('course_enrollments.student_id', $student->id)
            ->select('courses.*')
            ->get();
        
        foreach($courses as $course) {
             $scoreData = $this->calculateSubjectScore($student->id, $course);
             $finalScore10 = $scoreData['average_score_10'];

             // Only count if score exists (course completed/graded)
             if ($finalScore10 !== null) {
                 $score4 = $this->convertTo4Scale($finalScore10);
                 
                 // Accumulate for GPA
                 $totalWeightedScore10 += $finalScore10 * $course->credits;
                 $totalWeightedScore4 += $score4 * $course->credits;
                 $totalCreditsCalculated += $course->credits;

                 // Count earned credits (Pass rule: Score4 >= 1.0 or Score10 >= 4.0)
                 if ($score4 >= 1.0) {
                     $passedCredits += $course->credits;
                 }
             }
        }

        $gpa10 = $totalCreditsCalculated > 0 ? round($totalWeightedScore10 / $totalCreditsCalculated, 2) : 0;
        $gpa4 = $totalCreditsCalculated > 0 ? round($totalWeightedScore4 / $totalCreditsCalculated, 2) : 0;
        
        // Classification
        $classification = 'Khá';
        if ($gpa4 >= 3.6) $classification = 'Xuất sắc';
        else if ($gpa4 >= 3.2) $classification = 'Giỏi';
        else if ($gpa4 >= 2.5) $classification = 'Khá';
        else if ($gpa4 >= 2.0) $classification = 'Trung bình';
        else $classification = 'Yếu';

        return response()->json([
            'student_code' => $student->student_code,
            'full_name' => $student->full_name,
            'class_name' => $className,
            'major' => $majorName,
            'academic_year' => $academicYear,
            'classification' => $classification,
            'gpa_10_cumulative' => $gpa10,
            'gpa_4_cumulative' => $gpa4,
            'credits_earned' => $passedCredits,
            'credits_total' => 120, // Total program credits
        ]);
    }

    /**
     * Get Student detailed grades table
     */
    public function getStudentGrades(Request $request)
    {
        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        if (!$userId) {
             return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $student = $this->resolveStudent($userId, $request->user());
        if (!$student) {
             return response()->json(['message' => 'Student profile not found'], 404);
        }

        // Fetch courses enrolled by student
        $courses = Course::join('course_enrollments', 'courses.id', '=', 'course_enrollments.course_id')
            ->where('course_enrollments.student_id', $student->id)
            ->select('courses.*')
            ->with(['semester'])
            ->get();

        $gradeData = $courses->map(function ($course) use ($student) {
            $scoreData = $this->calculateSubjectScore($student->id, $course);

            $avg10 = $scoreData['average_score_10'];
            $score4 = $this->convertTo4Scale($avg10);
            $letter = $this->convertToLetter($avg10);

            return [
                'id' => $course->id,
                'course_code' => $course->code,
                'course_name' => $course->name,
                'credits' => $course->credits,
                'component_scores' => $scoreData['component_text'] ?: '-',
                'exam_score' => $scoreData['exam_score'],
                'average_score_10' => $avg10,
                'score_4' => $score4,
                'letter_grade' => $letter,
                'semester_name' => $course->semester?->name,
                'academic_year' => $course->semester?->academic_year,
            ];
        });

        return response()->json([
            'summary' => [],
            'courses' => $gradeData
        ]);
    }

    // Helper to resolve student
    private function resolveStudent($userId, $user) {
        $student = null;
        if ($user) {
            if ($user->student_id) {
                 $student = Student::find($user->student_id);
            } elseif (method_exists($user, 'student') && $user->student) {
                $student = $user->student;
            }
        }
        if (!$student && $userId) {
            $student = Student::find($userId);
        }
        return $student;
    }

    // Helper to calculate score for a single course
    private function calculateSubjectScore($studentId, $course) {
        // 1. Fetch Assignment Submissions
        $submissions = AssignmentSubmission::where('student_id', $studentId)
            ->whereHas('assignment', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->with('assignment')
            ->get();

        // 2. Group by Grade Column
        $components = [];
        foreach ($submissions as $sub) {
            $col = $sub->assignment->grade_column ?: 'Khác';
            if (!isset($components[$col])) {
                $components[$col] = [];
            }
            $components[$col][] = $sub->score;
        }

        // 3. Calc Component Score
        $componentStrings = [];
        $totalComponentScore = 0;
        $componentCount = 0;

        foreach ($components as $col => $scores) {
            if (empty($scores)) continue;
            $avg = array_sum($scores) / count($scores);
            $componentStrings[] = "$col: " . round($avg, 1);
            $totalComponentScore += $avg;
            $componentCount++;
        }
        $componentText = implode(' - ', $componentStrings);

        // 4. Fetch Exam Score
        $examSubmission = ExamSubmission::where('student_id', $studentId)
            ->whereHas('exam', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->orderBy('total_score', 'desc')
            ->first();

        $examScore = $examSubmission ? $examSubmission->score : null;

        // 5. Final Calculation
        $avg10 = null;
        if ($componentCount > 0 || $examScore !== null) {
            $avgComponent = $componentCount > 0 ? $totalComponentScore / $componentCount : 0;
            if ($examScore !== null) {
                $avg10 = ($avgComponent * 0.4) + ($examScore * 0.6);
            } else {
                $avg10 = null; 
            }
        }
        
        return [
            'average_score_10' => $avg10,
            'component_text' => $componentText,
            'exam_score' => $examScore
        ];
    }

    private function convertTo4Scale($score10) {
        if ($score10 === null) return null;
        if ($score10 >= 8.5) return 4.0;
        if ($score10 >= 8.0) return 3.5;
        if ($score10 >= 7.0) return 3.0;
        if ($score10 >= 6.5) return 2.5;
        if ($score10 >= 5.5) return 2.0;
        if ($score10 >= 5.0) return 1.5;
        if ($score10 >= 4.0) return 1.0;
        return 0.0;
    }

    private function convertToLetter($score10) {
        if ($score10 === null) return null;
        if ($score10 >= 9.0) return 'A+';
        if ($score10 >= 8.5) return 'A';
        if ($score10 >= 8.0) return 'B+';
        if ($score10 >= 7.0) return 'B';
        if ($score10 >= 6.5) return 'C+';
        if ($score10 >= 5.5) return 'C';
        if ($score10 >= 5.0) return 'D+';
        if ($score10 >= 4.0) return 'D';
        return 'F';
    }
}
