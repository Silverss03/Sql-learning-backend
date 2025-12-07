<?php

namespace Tests\Unit\UserManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarUploadTest extends TestCase
{
    use CreatesUsers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake the Google Drive storage
        Storage::fake('google');
    }

    /** @test */
    public function user_can_upload_valid_avatar_image()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ])
            ->assertJsonStructure([
                'data' => ['avatar_url'],
                'message',
                'success',
                'remark'
            ]);

        // Verify file was stored
        $fileName = $user->id . '_avatar_' . time() . '.jpg';
        Storage::disk('google')->assertExists('/user_picture/' . $fileName);

        // Verify database was updated
        $user->refresh();
        $this->assertNotNull($user->image_url);
    }

    /** @test */
    public function user_can_upload_png_image()
    {
        $user = $this->createTeacher();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('avatar.png', 500, 500);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);

        $user->refresh();
        $this->assertStringContainsString('.png', $user->image_url);
    }

    /** @test */
    public function user_can_upload_gif_image()
    {
        $user = $this->createAdmin();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('avatar.gif', 400, 400);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);

        $user->refresh();
        $this->assertStringContainsString('.gif', $user->image_url);
    }

    /** @test */
    public function old_avatar_is_deleted_when_uploading_new_one()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        // Upload first avatar
        $firstFile = UploadedFile::fake()->image('first_avatar.jpg');
        $this->postJson('/api/user/avatar', ['avatar' => $firstFile]);

        $user->refresh();
        $oldAvatarUrl = $user->image_url;
        $oldFileName = basename($oldAvatarUrl);

        // Upload second avatar
        sleep(1); // Ensure different timestamp
        $secondFile = UploadedFile::fake()->image('second_avatar.jpg');
        $response = $this->postJson('/api/user/avatar', ['avatar' => $secondFile]);

        $response->assertStatus(200);

        // Verify old avatar was deleted
        Storage::disk('google')->assertMissing('/user_picture/' . $oldFileName);

        // Verify new avatar exists
        $user->refresh();
        $newFileName = basename($user->image_url);
        Storage::disk('google')->assertExists('/user_picture/' . $newFileName);

        // Verify URLs are different
        $this->assertNotEquals($oldAvatarUrl, $user->image_url);
    }

    /** @test */
    public function avatar_field_is_required()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/user/avatar', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function avatar_must_be_an_image()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function avatar_file_size_cannot_exceed_2mb()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        // Create a file larger than 2MB (2048KB)
        $file = UploadedFile::fake()->image('large_avatar.jpg')->size(3000);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function avatar_file_at_max_size_is_accepted()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        // Create a file exactly at 2MB (2048KB)
        $file = UploadedFile::fake()->image('max_avatar.jpg')->size(2048);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_upload_avatar()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function response_includes_avatar_url()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['avatar_url'],
                'message',
                'success',
                'remark'
            ]);

        $avatarUrl = $response->json('data.avatar_url');
        $this->assertNotEmpty($avatarUrl);
        $this->assertStringContainsString('user_picture', $avatarUrl);
    }

    /** @test */
    public function avatar_path_is_stored_in_database()
    {
        $user = $this->createTeacher();
        $this->actingAs($user, 'sanctum');

        $this->assertNull($user->image_url);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->image_url);
        $this->assertStringContainsString('user_picture', $user->image_url);
    }

    /** @test */
    public function student_can_upload_avatar()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $file = UploadedFile::fake()->image('student_avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);
    }

    /** @test */
    public function teacher_can_upload_avatar()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $file = UploadedFile::fake()->image('teacher_avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);
    }

    /** @test */
    public function admin_can_upload_avatar()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $file = UploadedFile::fake()->image('admin_avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);
    }

    /** @test */
    public function filename_includes_user_id_and_timestamp()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $fileName = basename($user->image_url);

        // Verify filename format: {user_id}_avatar_{timestamp}.{extension}
        $this->assertStringStartsWith($user->id . '_avatar_', $fileName);
        $this->assertStringEndsWith('.jpg', $fileName);
    }

    /** @test */
    public function avatar_upload_works_for_user_without_existing_avatar()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        // Ensure user has no avatar
        $this->assertNull($user->image_url);

        $file = UploadedFile::fake()->image('new_avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar updated successfully'
            ]);

        $user->refresh();
        $this->assertNotNull($user->image_url);
    }

    /** @test */
    public function invalid_file_type_returns_validation_error()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->create('video.mp4', 500, 'video/mp4');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function response_format_is_consistent_with_api_standards()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['avatar_url'],
                'message',
                'success',
                'remark'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Avatar updated successfully', $response->json('message'));
        $this->assertNotEmpty($response->json('remark'));
    }
}
