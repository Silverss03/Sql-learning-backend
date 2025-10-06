<?php

namespace App\Services;

use App\Models\SQLQuestion;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\QuestionSchema;

class SchemaConnectionService
{

    public function createDynamicConnection($questionId)
    {
        $question = SQLQuestion::with('questionSchema')->findOrFail($questionId);

        if(!$question || !$question->questionSchema) {
            throw new \Exception("Question or associated schema not found.");
        }

        $schema = $question->questionSchema;
        $connectionName = "question_" . $schema->id;

        // create dynamic connection config 
        $template = config("database.connections.question_template");
        $template['database'] = $schema->database_name;

        Config::set("database.connections.$connectionName", $template);

        return $connectionName;
    }

    public function executeStudentQuery($questionId, $studentQuery)
    {
        $connectionName = $this->createDynamicConnection($questionId);

        try {
            //Execute student query
            $results = DB::connection($connectionName)->select($studentQuery);

            return [
                'success' => true,
                'results' => $results,
                'rows_affected' => count($results)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'results' => [],
            ];
        } finally {
            //clean connection
            DB::purge($connectionName);
        }
    }

    public function getExpectedResults($questionId)
    {
        $question = SQLQuestion::with('questionSchema')->findOrFail($questionId);
        $connectionName = $this->createDynamicConnection($questionId);

        try {
            return DB::connection($connectionName)->select($question->expected_result_query);
        } finally {
            DB::purge($connectionName);
        }
    }
}
