<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Favorite;
use App\Notifications\NewPostByFavoritedUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_followers_are_notified_when_favorited_user_creates_post()
    {
        Notification::fake();

        // Create users
        $author = User::factory()->create();
        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();
        $nonFollower = User::factory()->create();

        // Create favorites (followers)
        Favorite::create([
            'user_id' => $follower1->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $author->id,
        ]);

        Favorite::create([
            'user_id' => $follower2->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $author->id,
        ]);

        // Author creates a post
        $postData = [
            'title' => 'Test Post Title',
            'body' => 'Test post body content',
        ];

        $this->actingAs($author)
            ->postJson(route('posts.store'), $postData)
            ->assertStatus(201);

        // Assert notifications were sent to followers
        Notification::assertSentTo(
            [$follower1, $follower2],
            NewPostByFavoritedUser::class,
            function ($notification, $channels, $notifiable) use ($author) {
                return $notification->getAuthor()->id === $author->id;
            }
        );

        // Assert notification was not sent to non-follower
        Notification::assertNotSentTo(
            [$nonFollower],
            NewPostByFavoritedUser::class
        );
    }

    public function test_notification_has_correct_content()
    {
        // Create users
        $author = User::factory()->create(['name' => 'Author Name']);
        $follower = User::factory()->create(['name' => 'Follower Name']);

        // Create post
        $post = Post::factory()->create([
            'title' => 'Test Post Title',
            'body' => 'Test post body content',
            'user_id' => $author->id,
        ]);

        // Create notification instance
        $notification = new NewPostByFavoritedUser($post, $author);

        // Get mail message
        $mail = $notification->toMail($follower);

        // Assert mail content
        $this->assertEquals("{$author->name} has published a new post", $mail->subject);
        $this->assertStringContainsString("Hello {$follower->name}!", $mail->greeting);
        $this->assertStringContainsString("{$author->name} has published a new post:", $mail->introLines[0]);
        $this->assertStringContainsString("Title: {$post->title}", $mail->introLines[1]);
        $this->assertStringContainsString("Content: {$post->body}", $mail->introLines[2]);
    }
}
