<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\ExamAuditLogRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Models\Admin;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

class ExamAuditLogController extends Controller
{
    protected $auditLogRepository;
    protected $studentRepository;

    public function __construct(
        ExamAuditLogRepositoryInterface $auditLogRepository,
        StudentRepositoryInterface $studentRepository
    ) {
        $this->auditLogRepository = $auditLogRepository;
        $this->studentRepository = $studentRepository;
    }

    /**
     * Log a tab switch event during exam (Student)
     * POST /api/exam-audit-logs
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'session_token' => 'required|string',
                'event_type' => 'required|string|in:tab_switch',
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

            $auditLog = $this->auditLogRepository->create([
                'student_id' => $student->id,
                'exam_id' => $request->exam_id,
                'session_token' => $request->session_token,
                'event_type' => $request->event_type,
            ]);

            return response()->json([
                'data' => $auditLog,
                'message' => 'Tab switch logged successfully',
                'success' => true,
                'remark' => 'Event logged for exam monitoring'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to log tab switch',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit logs by exam (Admin/Teacher only)
     * GET /api/exam-audit-logs/exam/{examId}
     */
    public function getByExam(Request $request, $examId)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can view audit logs'
            ], 403);
        }

        try {
            $logs = $this->auditLogRepository->getByExam($examId);

            return response()->json([
                'data' => $logs,
                'message' => 'Exam audit logs retrieved successfully',
                'success' => true,
                'remark' => 'All audit logs for the specified exam'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exam audit logs',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit logs by student (Admin/Teacher only)
     * GET /api/exam-audit-logs/student/{studentId}
     */
    public function getByStudent(Request $request, $studentId)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can view audit logs'
            ], 403);
        }

        try {
            $logs = $this->auditLogRepository->getByStudent($studentId);

            return response()->json([
                'data' => $logs,
                'message' => 'Student audit logs retrieved successfully',
                'success' => true,
                'remark' => 'All audit logs for the specified student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve student audit logs',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tab switch count for a session (Admin/Teacher only)
     * GET /api/exam-audit-logs/session/{sessionToken}/count
     */
    public function getTabSwitchCount(Request $request, $sessionToken)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can view tab switch count'
            ], 403);
        }

        try {
            $count = $this->auditLogRepository->countTabSwitches($sessionToken);

            return response()->json([
                'data' => [
                    'session_token' => $sessionToken,
                    'tab_switch_count' => $count
                ],
                'message' => 'Tab switch count retrieved successfully',
                'success' => true,
                'remark' => 'Number of times student switched tabs during exam'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve tab switch count',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
