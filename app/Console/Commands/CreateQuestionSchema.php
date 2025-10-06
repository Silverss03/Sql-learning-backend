<?php

namespace App\Console\Commands;

use App\Models\QuestionSchemas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class CreateQuestionSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:create 
                           {name : The schema name} 
                           {--description= : Schema description}
                           {--setup-file= : SQL setup file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new question schema with tables and data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schemaName = $this->argument('name');
        $description = $this->option('description');
        $setupFile = $this->option('setup-file');

        try {
            //create database schema
            $this->createDatabase($schemaName);

            //register schema in question_schemas table
            $this->registerSchema($schemaName, $description);

            //setup tables and data if setup file is provided
            if($setupFile && file_exists($setupFile)){
                $this->setupSchemaData($schemaName, $setupFile);
            }

            $this->info("Schema '{$schemaName}' created and set up successfully.");
        } catch (\Exception $e) {
            $this->error("Error creating schema: " . $e->getMessage());
            return 1;
        }
        return 0;
    }

    private function createDatabase($schemaName)
    {
        // Temporarily set the default connection's database to null
        $defaultConnection = config('database.default');
        $originalDatabase = config("database.connections.{$defaultConnection}.database");

        config(["database.connections.$defaultConnection.database" => null]); //Connects to MySQL server without selecting a database
        DB::purge($defaultConnection); // Clears Laravel's connection cache so it uses the new config

        //Create database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$schemaName}`");
        
        // Restore the original database configuration
        config(["database.connections.$defaultConnection.database" => $originalDatabase]);
        DB::purge($defaultConnection);

        $this->info("Database '{$schemaName}' created successfully.");
    }

    private function registerSchema($schemaName, $description)
    {
        QuestionSchemas::create([
            'schema_name' => $schemaName,
            'schema_description' => $description ?? 'Schema for ' . $schemaName,
            'database_name' => $schemaName,
            'is_active' => true,
        ]);

        $this->info("Schema '{$schemaName}' registered successfully.");
    }

    private function setupSchemaData($schemaName, $setupFile)
    {
        //create dynamic connection for the new schema
        $connectionName = 'temp_' . $schemaName;
        $this->createDynamicConnection($schemaName, $connectionName);

        //read and execute the sql setup
        $sql = file_get_contents($setupFile);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if(!empty($statement)){
                DB::connection($connectionName)->statement($statement);
            }
        }
    }

    private function createDynamicConnection($schemaName, $connectionName)
    {
        $template = config('database.connections.question_template');
        $template['database'] = $schemaName;

        config(["database.connections.$connectionName" => $template]);
    }
}
