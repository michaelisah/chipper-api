<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostImageTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_create_post_with_image()
    {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('post-image.jpg', 1200, 800);

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'Test Post with Image',
                'body' => 'This is a test post with an image attachment.',
                'image' => $image,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                    'user',
                ]
            ])
            ->assertJsonFragment([
                'title' => 'Test Post with Image',
                'body' => 'This is a test post with an image attachment.',
            ]);

        // Assert the image was stored
        $post = Post::first();
        $this->assertNotNull($post->image_path);
        Storage::disk('public')->assertExists($post->image_path);
    }

    public function test_user_can_create_post_without_image()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'Test Post without Image',
                'body' => 'This is a test post without an image attachment.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                    'user',
                ]
            ])
            ->assertJsonFragment([
                'title' => 'Test Post without Image',
                'body' => 'This is a test post without an image attachment.',
                'image_url' => null,
            ]);

        // Assert no image was stored
        $post = Post::first();
        $this->assertNull($post->image_path);
    }

    public function test_user_can_update_post_with_new_image()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Post',
            'body' => 'Original content',
        ]);

        $image = UploadedFile::fake()->image('updated-image.png', 800, 600);

        $response = $this->actingAs($user)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Post',
                'body' => 'Updated content',
                'image' => $image,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Updated Post',
                'body' => 'Updated content',
            ]);

        // Assert the image was stored
        $updatedPost = Post::find($post->id);
        $this->assertNotNull($updatedPost->image_path);
        Storage::disk('public')->assertExists($updatedPost->image_path);
    }

    public function test_invalid_image_upload_is_rejected()
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'Test Post with Invalid File',
                'body' => 'This is a test post with an invalid file attachment.',
                'image' => $invalidFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        // Assert no post was created
        $this->assertEquals(0, Post::count());
    }
}
