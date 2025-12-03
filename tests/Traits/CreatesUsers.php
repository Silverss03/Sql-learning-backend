<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CreatesUsers
{
    protected function createAdmin(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));

        Admin::create(['user_id' => $user->id]);

        return $user;
    }

    protected function createTeacher(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'teacher',
            'email' => 'teacher@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));

        Teacher::create(['user_id' => $user->id]);

        return $user;
    }

    protected function createStudent(array $attributes = []): User
    {
        $teacherUser = User::where('role', 'teacher')->first();
        
        if (!$teacherUser) {
            // Create a test teacher if none exists
            $teacherUser = User::factory()->create([
                'role' => 'teacher',
                'email' => 'class-teacher@test.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            $teacher = Teacher::create(['user_id' => $teacherUser->id]);
        } else {
            $teacher = $teacherUser->teacher;
        }

        $class = ClassModel::firstOrCreate(
            ['class_name' => 'Test Class'],
            [
                'class_code' => 'TEST-' . strtoupper(Str::random(4)),
                'description' => 'Test class description',
                'teacher_id' => $teacher->id
            ]
        );

        $user = User::factory()->create(array_merge([
            'role' => 'student',
            'email' => 'student@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));

        Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'student_code' => 'STU-' . strtoupper(Str::random(6)),
        ]);

        return $user;
    }

    protected function actingAsAdmin()
    {
        $admin = $this->createAdmin();
        return $this->actingAs($admin, 'sanctum');
    }

    protected function actingAsTeacher()
    {
        $teacher = $this->createTeacher();
        return $this->actingAs($teacher, 'sanctum');
    }

    protected function actingAsStudent()
    {
        $student = $this->createStudent();
        return $this->actingAs($student, 'sanctum');
    }
}
