<?php

namespace Tests\Unit\TeacherManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class TeacherRetrievalTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_get_all_teachers()
    {
        $admin = $this->createAdmin();
        $this->createTeacher(['email' => 'teacher1@test.com']);
        $this->createTeacher(['email' => 'teacher2@test.com']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/admin/teachers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Teachers retrieved successfully'
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function non_admin_cannot_get_teachers_list()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/admin/teachers');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_teachers_list()
    {
        $response = $this->getJson('/api/admin/teachers');

        $response->assertStatus(401);
    }
}
