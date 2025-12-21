<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\ChapterExerciseRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Models\Admin;

class ChapterExerciseController extends Controller
{
    protected $chapterExerciseRepository;
    protected $studentRepository;

    public function __construct(
        ChapterExerciseRepositoryInterface $chapterExerciseRepository,
        StudentRepositoryInterface $studentRepository
    ) {
        $this->chapterExerciseRepository = $chapterExerciseRepository;
        $this->studentRepository = $studentRepository;
    }

    public function store(Request $request)
    {
        $admin = Admin::where('user_id', $request->user_id)->first();

        if(!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can create chapter exercises'
            ], 403);
        }

        try {
            $request->validate([
                'topic_id' => 'required|exists:topics,id',
                'is_active' => 'boolean',
                'activated_at' => 'nullable|date',
            ]);

            $chapterExercise = $this->chapterExerciseRepository->create([
                'topic_id' => $request->topic_id,
                'is_active' => $request->is_active ?? false,
                'activated_at' => $request->activated_at,
            ]);

            return response()->json([
                'data' => $chapterExercise,
                'message' => 'Chapter exercise created successfully',
                'success' => true,
                'remark' => 'New chapter exercise record created'
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
                'message' => 'Failed to create chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function show($chapterExerciseId)
    {
        try {
            $data = $this->chapterExerciseRepository->getWithQuestions($chapterExerciseId);

            if ($data === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Chapter exercise not found',
                    'success' => false,
                    'remark' => 'The requested chapter exercise does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $data,
                'message' => 'Chapter exercise retrieved successfully',
                'success' => true,
                'remark' => 'Single chapter exercise details'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $chapterExerciseId)
    {
        $admin = Admin::where('user_id', $request->user_id)->first();

        if(!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can update chapter exercises'
            ], 403);
        }

        try {
            $request->validate([
                'topic_id' => 'nullable|exists:topics,id',
                'is_active' => 'boolean',
                'activated_at' => 'nullable|date',
            ]);

            $chapterExercise = $this->chapterExerciseRepository->update(
                $chapterExerciseId,
                $request->only(['topic_id', 'is_active', 'activated_at'])
            );

            if ($chapterExercise === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Chapter exercise not found',
                    'success' => false,
                    'remark' => 'The requested chapter exercise does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $chapterExercise,
                'message' => 'Chapter exercise updated successfully',
                'success' => true,
                'remark' => 'Chapter exercise record updated'
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
                'message' => 'Failed to update chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $chapterExerciseId)
    {
        $admin = Admin::where('user_id', $request->user_id)->first();

        if(!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can delete chapter exercises'
            ], 403);
        }

        try {
            $deleted = $this->chapterExerciseRepository->delete($chapterExerciseId);

            if (!$deleted) {
                return response()->json([
                    'data' => null,
                    'message' => 'Chapter exercise not found',
                    'success' => false,
                    'remark' => 'The requested chapter exercise does not exist'
                ], 404);
            }

            return response()->json([
                'data' => null,
                'message' => 'Chapter exercise deleted successfully',
                'success' => true,
                'remark' => 'Chapter exercise record deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function submit(Request $request)
    {
        try {
            $request->validate([
                'chapter_exercise_id' => 'required|exists:chapter_exercises,id',
                'score' => 'required|numeric|min:0',
                'user_id' => 'required|exists:users,id'
            ]);

            $student = $this->studentRepository->findByUserId($request->user_id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            $progress = $this->chapterExerciseRepository->submitExercise(
                $student->id,
                $request->chapter_exercise_id,
                $request->score
            );

            return response()->json([
                'data' => $progress,
                'message' => 'Chapter exercise submitted successfully',
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
                'message' => 'Failed to submit chapter exercise result',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $chapterExercises = $this->chapterExerciseRepository->getAllExercises();

            return response()->json([
                'data' => $chapterExercises,
                'message' => 'Chapter exercises retrieved successfully',
                'success' => true,
                'remark' => 'List of all chapter exercises with topics'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve chapter exercises',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
