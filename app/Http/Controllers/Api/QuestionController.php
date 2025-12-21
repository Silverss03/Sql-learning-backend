<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Models\Teacher;
use App\Models\Admin;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    protected $questionRepository;

    public function __construct(QuestionRepositoryInterface $questionRepository)
    {
        $this->questionRepository = $questionRepository;
    }

    /**
     * Create an exercise (lesson, chapter, or exam) with questions
     */
    public function store(Request $request)
    {
        try {
            // Verify teacher authorization
            $user = $request->user();
            $teacher = Teacher::where('user_id', $user->id)->first();
            $admin = Admin::where('user_id', $user->id)->first();

            if (!$teacher && !$admin) {
                return response()->json([
                    'data' => null,
                    'message' => 'Unauthorized',
                    'success' => false,
                    'remark' => 'Only teachers and admins can create exercises and questions'
                ], 403);
            }

            // Validate the base request
            $validator = Validator::make($request->all(), [
                'exercise_type' => 'required|in:lesson,chapter,exam',
                'parent_id' => 'nullable|integer',
                'exam_title' => 'nullable|string|max:255',
                'exam_description' => 'nullable|string',
                'exam_duration_minutes' => 'required_if:exercise_type,exam|integer|min:1',
                'exam_start_time' => 'required_if:exercise_type,exam|date',
                'exam_end_time' => 'required_if:exercise_type,exam|date|after:exam_start_time',
                'class_id' => 'required_if:exercise_type,exam|exists:classes,id',
                'questions' => 'required|array|min:1',
                'questions.*.type' => 'required|in:multiple_choice,sql',
                'questions.*.order_index' => 'required|integer|min:0',
                'questions.*.title' => 'required|string|max:255',
                'questions.*.details' => 'required|array',
                // Multiple choice validations
                'questions.*.details.description' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_A' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_B' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_C' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_D' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.correct_answer' => 'required_if:questions.*.type,multiple_choice|in:A,B,C,D',
                // SQL question validations
                'questions.*.details.interaction_type' => 'required_if:questions.*.type,sql|string',
                'questions.*.details.question_data' => 'required_if:questions.*.type,sql',
                'questions.*.details.solution_data' => 'required_if:questions.*.type,sql',
                'questions.*.details.description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'data' => null,
                    'message' => 'Validation failed',
                    'success' => false,
                    'remark' => $validator->errors()
                ], 422);
            }

            // For exams, verify teacher owns the class
            if ($request->exercise_type === 'exam') {
                $class = ClassModel::where('id', $request->class_id)
                ->where('teacher_id', $teacher->id)
                ->first();
                
                if (!$class) {
                    return response()->json([
                        'data' => null,
                        'message' => 'Unauthorized',
                        'success' => false,
                        'remark' => 'You can only create exams for your own classes'
                    ], 403);
                }
            }
            $teacherId = ($request->exercise_type === 'exam' && $teacher) ? $teacher->id : null;
            
            // Prepare data for repository
            $data = [
                'exercise_type' => $request->exercise_type,
                'parent_id' => $request->parent_id,
                'teacher_id' => $teacherId,
                'created_by' => $user->id,
                'questions' => $request->questions,
            ];

            // Add exam-specific data if applicable
            if ($request->exercise_type === 'exam') {
                $data['exam_title'] = $request->exam_title;
                $data['exam_description'] = $request->exam_description;
                $data['exam_duration_minutes'] = $request->exam_duration_minutes;
                $data['exam_start_time'] = $request->exam_start_time;
                $data['exam_end_time'] = $request->exam_end_time;
                $data['class_id'] = $request->class_id;
            }

            // Create exercise with questions
            $result = $this->questionRepository->createExerciseWithQuestions($data);

            return response()->json([
                'data' => [
                    'exercise' => $result['exercise'],
                    'questions' => $result['questions']
                ],
                'message' => 'Exercise and questions created successfully',
                'success' => true,
                'remark' => 'New exercise created with ' . count($result['questions']) . ' questions'
            ], 201);

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
                'message' => 'Failed to create exercise and questions',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific question by ID
     */
    public function show($questionId)
    {
        try {
            $question = $this->questionRepository->findById($questionId);

            if (!$question) {
                return response()->json([
                    'data' => null,
                    'message' => 'Question not found',
                    'success' => false,
                    'remark' => 'The requested question does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $question,
                'message' => 'Question retrieved successfully',
                'success' => true,
                'remark' => 'Question details with type-specific data'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve question',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific question
     */
    public function update(Request $request, $questionId)
    {
        try {
            // Verify teacher authorization
            $teacher = Teacher::where('user_id', $request->user()->id)->first();

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Unauthorized',
                    'success' => false,
                    'remark' => 'Only teachers can update questions'
                ], 403);
            }

            // Validate the request
            $validator = Validator::make($request->all(), [
                'order_index' => 'nullable|integer|min:0',
                'title' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
                'details' => 'nullable|array',
                'details.description' => 'nullable|string',
                'details.answer_A' => 'nullable|string',
                'details.answer_B' => 'nullable|string',
                'details.answer_C' => 'nullable|string',
                'details.answer_D' => 'nullable|string',
                'details.correct_answer' => 'nullable|in:A,B,C,D',
                'details.interaction_type' => 'nullable|string',
                'details.question_data' => 'nullable',
                'details.solution_data' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'data' => null,
                    'message' => 'Validation failed',
                    'success' => false,
                    'remark' => $validator->errors()
                ], 422);
            }

            $question = $this->questionRepository->update($questionId, $request->all());

            if (!$question) {
                return response()->json([
                    'data' => null,
                    'message' => 'Question not found',
                    'success' => false,
                    'remark' => 'The requested question does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $question,
                'message' => 'Question updated successfully',
                'success' => true,
                'remark' => 'Question has been updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to update question',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific question
     */
    public function destroy(Request $request, $questionId)
    {
        try {
            // Verify teacher authorization
            $teacher = Teacher::where('user_id', $request->user()->id)->first();

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Unauthorized',
                    'success' => false,
                    'remark' => 'Only teachers can delete questions'
                ], 403);
            }

            $deleted = $this->questionRepository->delete($questionId);

            if (!$deleted) {
                return response()->json([
                    'data' => null,
                    'message' => 'Question not found',
                    'success' => false,
                    'remark' => 'The requested question does not exist'
                ], 404);
            }

            return response()->json([
                'data' => null,
                'message' => 'Question deleted successfully',
                'success' => true,
                'remark' => 'Question and related data have been deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete question',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
