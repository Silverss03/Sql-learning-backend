<?php

namespace Tests\Unit\StudentManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class StudentRetrievalTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_get_all_students()
    {
        $admin = $this->createAdmin();
        $this->createStudent(['email' => 'student1@test.com']);
        $this->createStudent(['email' => 'student2@test.com']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/admin/students');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Students retrieved successfully'
            ]);
    }

    /** @test */
    public function non_admin_cannot_get_students_list()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/admin/students');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }
}
