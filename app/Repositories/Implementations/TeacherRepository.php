<?php

namespace App\Repositories\Implementations;

use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Exam;
use App\Models\StudentExamProgress;
use App\Models\StudentLessonProgress;
use App\Models\StudentChapterExerciseProgress;
use App\Repositories\Interfaces\TeacherRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TeacherRepository implements TeacherRepositoryInterface
{
    public function findByUserId($userId)
    {
        return Teacher::where('user_id', $userId)->first();
    }

    public function getClasses($teacherId)
    {
        return ClassModel::where('teacher_id', $teacherId)
            ->withCount('students')
            ->with(['students' => function($query) {
                $query->select('students.id', 'students.student_code', 'students.avg_score', 'students.class_id')
                    ->with('user:id,name,email');
            }])
            ->get()
            ->map(function($class) {
                return [
                    'id' => $class->id,
                    'class_name' => $class->class_name,
                    'class_code' => $class->class_code,
                    'students_count' => $class->students_count,
                    'students' => $class->students,
                    'created_at' => $class->created_at,
                ];
            });
    }

    public function getClassById($teacherId, $classId)
    {
        return ClassModel::where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->withCount('students')
            ->with(['students.user', 'teacher.user'])
            ->first();
    }

    public function getStudentsByClass($classId)
    {
        return Student::where('class_id', $classId)
            ->with('user:id,name,email,image_url')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'name' => $student->user->name,
                    'email' => $student->user->email,
                    'image_url' => $student->user->image_url,
                    'avg_score' => $student->avg_score,
                ];
            });
    }

    public function getStudentProgress($studentId)
    {
        $lessonProgress = StudentLessonProgress::where('student_id', $studentId)
            ->with('lesson:id,lesson_title,topic_id')
            ->orderBy('submitted_at', 'desc')
            ->limit(10)
            ->get();

        $chapterProgress = StudentChapterExerciseProgress::where('student_id', $studentId)
            ->with('chapterExercise:id,description,topic_id')
            ->orderBy('submitted_at', 'desc')
            ->limit(10)
            ->get();

        $examProgress = StudentExamProgress::where('student_id', $studentId)
            ->with('exam:id,title')
            ->where('is_completed', true)
            ->orderBy('submitted_at', 'desc')
            ->limit(10)
            ->get();

        $student = Student::with('user:id,name,email')->find($studentId);

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'student_code' => $student->student_code,
                'avg_score' => $student->avg_score,
            ],
            'lesson_progress' => $lessonProgress->map(function($progress) {
                return [
                    'lesson_id' => $progress->lesson_id,
                    'lesson_title' => $progress->lesson->lesson_title ?? 'N/A',
                    'score' => $progress->score,
                    'submitted_at' => $progress->submitted_at,
                ];
            }),
            'chapter_progress' => $chapterProgress->map(function($progress) {
                return [
                    'chapter_exercise_id' => $progress->chapter_exercise_id,
                    'description' => $progress->chapterExercise->description ?? 'N/A',
                    'score' => $progress->score,
                    'submitted_at' => $progress->submitted_at,
                ];
            }),
            'exam_progress' => $examProgress->map(function($progress) {
                return [
                    'exam_id' => $progress->exam_id,
                    'exam_title' => $progress->exam->title ?? 'N/A',
                    'score' => $progress->score,
                    'submitted_at' => $progress->submitted_at,
                ];
            }),
        ];
    }

    public function getClassAnalytics($classId)
    {
        $students = Student::where('class_id', $classId)->get();
        $studentIds = $students->pluck('id');

        if ($studentIds->isEmpty()) {
            return [
                'class_id' => $classId,
                'total_students' => 0,
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'students_above_80' => 0,
                'students_below_50' => 0,
                'total_exams_completed' => 0,
                'total_lessons_completed' => 0,
                'score_distribution' => [],
            ];
        }

        $avgScore = $students->avg('avg_score') ?? 0;
        $highestScore = $students->max('avg_score') ?? 0;
        $lowestScore = $students->min('avg_score') ?? 0;

        $studentsAbove80 = $students->where('avg_score', '>=', 80)->count();
        $studentsBelow50 = $students->where('avg_score', '<', 50)->count();

        $totalExamsCompleted = StudentExamProgress::whereIn('student_id', $studentIds)
            ->where('is_completed', true)
            ->count();

        $totalLessonsCompleted = StudentLessonProgress::whereIn('student_id', $studentIds)
            ->distinct('lesson_id')
            ->count();

        // Score distribution
        $scoreRanges = [
            '0-20' => $students->whereBetween('avg_score', [0, 20])->count(),
            '21-40' => $students->whereBetween('avg_score', [21, 40])->count(),
            '41-60' => $students->whereBetween('avg_score', [41, 60])->count(),
            '61-80' => $students->whereBetween('avg_score', [61, 80])->count(),
            '81-100' => $students->whereBetween('avg_score', [81, 100])->count(),
        ];

        return [
            'class_id' => $classId,
            'total_students' => $students->count(),
            'average_score' => round($avgScore, 2),
            'highest_score' => round($highestScore, 2),
            'lowest_score' => round($lowestScore, 2),
            'students_above_80' => $studentsAbove80,
            'students_below_50' => $studentsBelow50,
            'total_exams_completed' => $totalExamsCompleted,
            'total_lessons_completed' => $totalLessonsCompleted,
            'score_distribution' => $scoreRanges,
        ];
    }

    public function getExamsByClass($classId)
    {
        return Exam::where('class_id', $classId)
            ->with(['class:id,class_name', 'teacher.user:id,name'])
            ->withCount('studentProgress')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($exam) {
                $completedCount = $exam->studentProgress()
                    ->where('is_completed', true)
                    ->count();

                $avgScore = $exam->studentProgress()
                    ->where('is_completed', true)
                    ->avg('score') ?? 0;

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'duration_minutes' => $exam->duration_minutes,
                    'total_questions' => $exam->total_questions,
                    'is_active' => $exam->is_active,
                    'class_name' => $exam->class->class_name ?? 'N/A',
                    'teacher_name' => $exam->teacher->user->name ?? 'N/A',
                    'total_attempts' => $exam->student_progress_count,
                    'completed_count' => $completedCount,
                    'average_score' => round($avgScore, 2),
                    'created_at' => $exam->created_at,
                ];
            });
    }

    public function createExam($teacherId, array $data)
    {
        $data['teacher_id'] = $teacherId;
        $data['is_active'] = $data['is_active'] ?? false;
        
        return Exam::create($data);
    }

    public function updateExam($examId, array $data)
    {
        $exam = Exam::findOrFail($examId);
        $exam->update($data);
        return $exam->fresh();
    }

    public function deleteExam($examId)
    {
        $exam = Exam::findOrFail($examId);
        return $exam->delete();
    }

    public function getTeacherAnalytics($teacherId)
    {
        $classes = ClassModel::where('teacher_id', $teacherId)->get();
        $classIds = $classes->pluck('id');

        $totalStudents = Student::whereIn('class_id', $classIds)->count();
        $totalExams = Exam::where('teacher_id', $teacherId)->count();
        $activeExams = Exam::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->count();

        $students = Student::whereIn('class_id', $classIds)->get();
        $overallAvgScore = $students->avg('avg_score') ?? 0;

        // Top performing students across all classes
        $topStudents = Student::whereIn('class_id', $classIds)
            ->with('user:id,name,email')
            ->with('classModel:id,class_name')
            ->orderBy('avg_score', 'desc')
            ->limit(10)
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'email' => $student->user->email,
                    'student_code' => $student->student_code,
                    'class_name' => $student->classModel->class_name ?? 'N/A',
                    'avg_score' => $student->avg_score,
                ];
            });

        // Recent exam activity
        $recentExams = Exam::where('teacher_id', $teacherId)
            ->with('class:id,class_name')
            ->withCount('studentProgress')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'class_name' => $exam->class->class_name ?? 'N/A',
                    'is_active' => $exam->is_active,
                    'total_attempts' => $exam->student_progress_count,
                    'created_at' => $exam->created_at,
                ];
            });

        // Classes performance
        $classesPerformance = $classes->map(function($class) {
            $students = $class->students;
            $avgScore = $students->avg('avg_score') ?? 0;

            return [
                'class_id' => $class->id,
                'class_name' => $class->class_name,
                'students_count' => $students->count(),
                'average_score' => round($avgScore, 2),
            ];
        });

        return [
            'total_classes' => $classes->count(),
            'total_students' => $totalStudents,
            'total_exams' => $totalExams,
            'active_exams' => $activeExams,
            'overall_average_score' => round($overallAvgScore, 2),
            'top_students' => $topStudents,
            'recent_exams' => $recentExams,
            'classes_performance' => $classesPerformance,
        ];
    }

    public function getStudentGrades($classId)
    {
        $students = Student::where('class_id', $classId)
            ->with('user:id,name,email')
            ->get();

        return $students->map(function($student) {
            // Get lesson scores
            $lessonScores = StudentLessonProgress::where('student_id', $student->id)
                ->select('lesson_id', DB::raw('MAX(score) as max_score'))
                ->groupBy('lesson_id')
                ->get();

            // Get chapter exercise scores
            $chapterScores = StudentChapterExerciseProgress::where('student_id', $student->id)
                ->select('chapter_exercise_id', DB::raw('MAX(score) as max_score'))
                ->groupBy('chapter_exercise_id')
                ->get();

            // Get exam scores
            $examScores = StudentExamProgress::where('student_id', $student->id)
                ->where('is_completed', true)
                ->select('exam_id', 'score', 'submitted_at')
                ->orderBy('submitted_at', 'desc')
                ->get();

            return [
                'student_id' => $student->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'student_code' => $student->student_code,
                'avg_score' => $student->avg_score,
                'lessons_completed' => $lessonScores->count(),
                'chapters_completed' => $chapterScores->count(),
                'exams_completed' => $examScores->count(),
                'lesson_average' => round($lessonScores->avg('max_score') ?? 0, 2),
                'chapter_average' => round($chapterScores->avg('max_score') ?? 0, 2),
                'exam_average' => round($examScores->avg('score') ?? 0, 2),
            ];
        });
    }
}
