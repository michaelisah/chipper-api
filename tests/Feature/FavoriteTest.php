<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the favorites table with the polymorphic relationship for testing
        $this->artisan('migrate:fresh');
    }

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.posts.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.posts.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id'          => $user->id,
            'favoritable_type' => 'App\\Models\\Post',
            'favoritable_id'   => $post->id,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Create favorite directly
        Favorite::create([
            'user_id'          => $user->id,
            'favoritable_type' => 'App\\Models\\Post',
            'favoritable_id'   => $post->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.posts.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id'          => $user->id,
            'favoritable_type' => 'App\\Models\\Post',
            'favoritable_id'   => $post->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.posts.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    // User favorites tests

    public function test_a_guest_can_not_favorite_a_user()
    {
        $userToFavorite = User::factory()->create();

        $this->postJson(route('favorites.users.store', ['user' => $userToFavorite]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_another_user()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id'          => $user->id,
            'favoritable_type' => 'App\\Models\\User',
            'favoritable_id'   => $userToFavorite->id,
        ]);
    }

    public function test_a_user_cannot_favorite_themselves()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $user]))
            ->assertStatus(400)
            ->assertJsonFragment(['message' => 'You cannot favorite yourself']);
    }

    public function test_a_user_can_remove_another_user_from_favorites()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        // Create favorite directly
        Favorite::create([
            'user_id'          => $user->id,
            'favoritable_type' => 'App\\Models\\User',
            'favoritable_id'   => $userToFavorite->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.users.destroy', ['user' => $userToFavorite]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id'          => $user->id,
            'favoritable_type' => 'App\\Models\\User',
            'favoritable_id'   => $userToFavorite->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_user()
    {
        $user = User::factory()->create();
        $userToUnfavorite = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.users.destroy', ['user' => $userToUnfavorite]))
            ->assertNotFound();
    }
}
