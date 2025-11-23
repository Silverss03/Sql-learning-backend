<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\ExamRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Models\Teacher;
use App\Models\StudentExamProgress;

class ExamController extends Controller
{
    protected $examRepository;
    protected $studentRepository;

    public function __construct(
        ExamRepositoryInterface $examRepository,
        StudentRepositoryInterface $studentRepository
    ) {
        $this->examRepository = $examRepository;
        $this->studentRepository = $studentRepository;
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

            $exams = $this->examRepository->getFutureExamsByClass($student->class_id);

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

            $exam = $this->examRepository->findById($examId);

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

            $data = $this->examRepository->getExamWithQuestions($examId);

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
            $isActive = $exam->is_active || ($exam->start_time <= $now && $now <= $exam->end_time);
            if (!$isActive) {
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
                        'message' => 'Exam already completed',
                        'success' => false,
                        'remark' => 'This exam has already been completed'
                    ], 403);
                }
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

            $exam->delete();

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
