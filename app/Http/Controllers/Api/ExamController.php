<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamAuditLog;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\ExamRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Models\Teacher;
use App\Models\StudentExamProgress;
use App\Services\RedisCacheService;
use Illuminate\Support\Facades\Cache;

class ExamController extends Controller
{
    protected $examRepository;
    protected $studentRepository;
    protected $cache;

    public function __construct(
        ExamRepositoryInterface $examRepository,
        StudentRepositoryInterface $studentRepository,
        RedisCacheService $cache
    ) {
        $this->examRepository = $examRepository;
        $this->studentRepository = $studentRepository;
        $this->cache = $cache;
    }

    public function index(Request $request)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            // Cache future exams for 15 minutes (as exam schedules can change)
            $exams = Cache::remember(
                "exams:class:{$student->class_id}:future",
                RedisCacheService::CACHE_SHORT,
                function () use ($student) {
                    return $this->examRepository->getFutureExamsByClass($student->class_id);
                }
            );

            return response()->json([
                'data' => $exams,
                'message' => 'Future exams retrieved successfully',
                'success' => true,
                'remark' => 'All active exams scheduled for the future'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exams',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $examId)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if(!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            // Cache exam details with lock to prevent stampede when 100+ students start exam simultaneously
            $exam = $this->cache->rememberWithLock(
                "exams:{$examId}:details",
                RedisCacheService::CACHE_MEDIUM,
                function () use ($examId) {
                    return $this->examRepository->findById($examId);
                }
            );

            if (!$exam) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam not found',
                    'success' => false,
                    'remark' => 'The requested exam does not exist'
                ], 404);
            }

            if($exam->class_id !== $student->class_id) {
                return response()->json([
                    'data' => null,
                    'message' => 'Unauthorized',
                    'success' => false,
                    'remark' => 'The exam does not belong to the student\'s class'
                ], 403);
            }

            $now = now();
            if(!$exam->is_active || $now->lt($exam->start_time) || $now->gt($exam->end_time)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam is not currently active',
                    'success' => false,
                    'remark' => 'The current time is outside the exam schedule'
                ], 403);
            }

            $progress = StudentExamProgress::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();

            if($progress && $progress->is_completed) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam already completed',
                    'success' => false,
                    'remark' => 'The student has already completed this exam'
                ], 403);
            }

            // Cache exam with questions using lock (HIGH RISK: all students query same exam at start time)
            $data = $this->cache->rememberWithLock(
                "exams:{$examId}:questions",
                RedisCacheService::CACHE_MEDIUM,
                function () use ($examId) {
                    return $this->examRepository->getExamWithQuestions($examId);
                }
            );

            return response()->json([
                'data' => $data,
                'message' => 'Exam retrieved successfully',
                'success' => true,
                'remark' => 'Single exam details'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function logAudit(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'session_token' => 'required|string',
                'event_type' => 'required|string',
            ]);

            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $progress = StudentExamProgress::where('student_id', $student->id)
                ->where('exam_id', $request->exam_id)
                ->where('session_token', $request->session_token)
                ->first();

            if (!$progress) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam progress not found',
                    'success' => false,
                    'remark' => 'No matching exam session for the student'
                ], 404);
            }

            $auditLog = ExamAuditLog::create([
                'student_id' => $student->id,
                'exam_id' => $request->exam_id,
                'session_token' => $request->session_token,
                'event_type' => $request->event_type,
            ]);

            return response()->json([
                'data' => $auditLog,
                'message' => 'Audit log created successfully',
                'success' => true,
                'remark' => 'Exam audit event recorded'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to create audit log',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getExamHistory(Request $request)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            // Cache exam history for 10 minutes
            $examHistory = Cache::remember(
                "students:{$student->id}:exam_history",
                600, // 10 minutes
                function () use ($student) {
                    return $this->examRepository->getExamHistoryByStudent($student->id);
                }
            );

            return response()->json([
                'data' => $examHistory,
                'message' => 'Exam history retrieved successfully',
                'success' => true,
                'remark' => 'All past exam attempts by the student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exam history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function start(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'device_fingerprint' => 'required|string'
            ]);

            $student = $this->studentRepository->findByUserId($request->user()->id);
            
            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $exam = $this->examRepository->findById($request->exam_id);

            $now = now();
            if (!$exam->is_active || $now->lt($exam->start_time) || $now->gt($exam->end_time)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam is not currently active',
                    'success' => false,
                    'remark' => 'The current time is outside the exam schedule'
                ], 403);
            }

            $existingProgress = StudentExamProgress::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
                
            if ($existingProgress) {
                if ($existingProgress->is_completed) {
                    return response()->json([
                        'data' => null,
                        'message' => 'Sinh viên đã làm bài thi',
                        'success' => false,
                        'remark' => 'This exam has already been completed'
                    ], 403);
                }
                
                return response()->json([
                    'data' => null,
                    'message' => 'Sinh viên đã bắt đầu làm bài thi',
                    'success' => false,
                    'remark' => 'Student has already started this exam and cannot restart it'
                ], 403);
            }

            $data = $this->examRepository->startExam(
                $request->exam_id,
                $student->id,
                $request->device_fingerprint
            );

            return response()->json([
                'data' => $data,
                'message' => 'Exam started successfully',
                'success' => true,
                'remark' => 'Exam session initialized for the student'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to start exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function submit(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'score' => 'required|numeric|min:0',
                'session_token' => 'required|string',
                'device_fingerprint' => 'required|string',
            ]);

            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            // Acquire lock to prevent concurrent submissions
            $lockKey = "exam:submit:{$request->exam_id}:student:{$student->id}";
            $lock = Cache::lock($lockKey, 10); // 10 second timeout

            if (!$lock->get()) {
                return response()->json([
                    'data' => null,
                    'message' => 'Submission already in progress',
                    'success' => false,
                    'remark' => 'Please wait for the current submission to complete'
                ], 409); // HTTP 409 Conflict
            }

            try {
                // Double-check if already submitted (after acquiring lock)
                $existingProgress = StudentExamProgress::where('student_id', $student->id)
                    ->where('exam_id', $request->exam_id)
                    ->first();

                if ($existingProgress && $existingProgress->is_completed) {
                    return response()->json([
                        'data' => null,
                        'message' => 'Exam already submitted',
                        'success' => false,
                        'remark' => 'This exam has already been completed'
                    ], 403);
                }

                // Proceed with submission
                $result = $this->examRepository->submitExam(
                    $request->exam_id,
                    $student->id,
                    $request->session_token,
                    $request->device_fingerprint,
                    $request->score
                );

                if ($result === null) {
                    return response()->json([
                        'data' => null,
                        'message' => 'Invalid submission',
                        'success' => false,
                        'remark' => 'Session invalid, exam completed, or device mismatch'
                    ], 403);
                }

                // Clear student caches after exam submission
                $this->cache->clearStudentCache($student->id);
                Cache::forget("students:{$student->id}:exam_history");

                if ($result['has_violations']) {
                    return response()->json([
                        'data' => null,
                        'message' => 'Exam violations detected',
                        'success' => false,
                        'remark' => 'Exam marked as invalid due to violations (score: 0)'
                    ], 403);
                }

                return response()->json([
                    'data' => $result['progress'],
                    'message' => 'Exam submitted successfully',
                    'success' => true,
                    'remark' => 'Submission record created'
                ]);

            } finally {
                // Always release the lock
                $lock->release();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to submit exam result',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function activateExam(Request $request, $examId)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        
        if(!$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only teachers can start exams'
            ], 403);
        }

        try {
            $exam = $this->examRepository->findById($examId);

            if (!$exam) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam not found',
                    'success' => false,
                    'remark' => 'The requested exam does not exist'
                ], 404);
            }

            if ($exam->is_active) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam is already active',
                    'success' => false,
                    'remark' => 'The exam has already been started'
                ], 400);
            }

            $exam = $this->examRepository->updateExamStatus($examId, true);

            // Clear exam caches after activation
            Cache::forget("exams:{$examId}:details");
            Cache::forget("exams:{$examId}:questions");
            Cache::forget("exams:class:{$exam->class_id}:future");

            return response()->json([
                'data' => $exam,
                'message' => 'Exam started successfully',
                'success' => true,
                'remark' => 'Exam is now active'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to start exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $examId)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        
        if(!$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only teachers can update exams'
            ], 403);
        }

        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'duration_minutes' => 'nullable|integer|min:1',
                'start_time' => 'nullable|date|after:now',
                'end_time' => 'nullable|date|after:start_time',
                'is_active' => 'boolean',
            ]);

            $exam = $this->examRepository->findById($examId);
            
            if (!$exam) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam not found',
                    'success' => false,
                    'remark' => 'The requested exam does not exist'
                ], 404);
            }

            $exam->update($request->only(['title', 'description', 'duration_minutes', 'start_time', 'end_time', 'is_active']));

            // Clear exam caches after update
            Cache::forget("exams:{$examId}:details");
            Cache::forget("exams:{$examId}:questions");
            Cache::forget("exams:class:{$exam->class_id}:future");

            return response()->json([
                'data' => $exam,
                'message' => 'Exam updated successfully',
                'success' => true,
                'remark' => 'Exam record updated'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to update exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $examId)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        
        if(!$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only teachers can delete exams'
            ], 403);
        }

        try {
            $exam = $this->examRepository->findById($examId);
            
            if (!$exam) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam not found',
                    'success' => false,
                    'remark' => 'The requested exam does not exist'
                ], 404);
            }

            $classId = $exam->class_id;
            $exam->delete();

            // Clear exam caches after deletion
            Cache::forget("exams:{$examId}:details");
            Cache::forget("exams:{$examId}:questions");
            Cache::forget("exams:class:{$classId}:future");

            return response()->json([
                'data' => null,
                'message' => 'Exam deleted successfully',
                'success' => true,
                'remark' => 'Exam record deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
