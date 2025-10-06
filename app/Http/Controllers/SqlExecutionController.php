<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SchemaConnectionService;
use App\Models\SqlQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SqlExecutionController extends Controller
{
    protected $schemaService;

    public function __construct(SchemaConnectionService $schemaService)
    {
        $this->schemaService = $schemaService;
    }

    public function executeQuery(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:sql_questions,id',
            'query' => 'required|string',
        ]);

        $questionId = $request->input('question_id');
        $studentQuery = $request->input('query');

        // Execute student query
        $studentResults = $this->schemaService->executeStudentQuery($questionId, $studentQuery);

        if (!$studentResults['success']) {
            return response()->json([
                'success' => false,
                'error' => $studentResults['error'],
                'message' => 'Error executing student query.'
            ]);
        }

        $expectedResults = $this->schemaService->getExpectedResults($questionId);

        $isCorrect = $this->compareResults($studentResults['results'], $expectedResults);

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'student_results' => $studentResults['results'],
            'expected_results' => $expectedResults,
            'rows_affected' => $studentResults['rows_affected'],
        ]);
    }

    private function compareResults($studentResults, $expectedResults)
    {
        return json_encode($studentResults) === json_encode($expectedResults);
    }

    public function getQuestion($id): JsonResponse
    {
        try {
            $question = SqlQuestion::with('questionSchema')->find($id);
            
            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'question' => [
                    'id' => $question->id,
                    'title' => $question->title,
                    'description' => $question->description,
                    'schema_name' => $question->questionSchema->schema_name ?? 'Unknown'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching question',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
