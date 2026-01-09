<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Post;
use App\Models\User;

class NewPostByFavoritedUser extends Notification implements ShouldQueue
{
    use Queueable;

    protected $post;
    protected $author;

    /**
     * Create a new notification instance.
     */
    public function __construct(Post $post, User $author)
    {
        $this->post = $post;
        $this->author = $author;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("{$this->author->name} has published a new post")
                    ->greeting("Hello {$notifiable->name}!")
                    ->line("{$this->author->name} has published a new post:")
                    ->line("Title: {$this->post->title}")
                    ->line("Content: " . substr($this->post->body, 0, 100) . (strlen($this->post->body) > 100 ? '...' : ''))
                    ->action('View Post', url("/posts/{$this->post->id}"))
                    ->line('Thank you for using Chipper!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
        ];
    }
    
    /**
     * Get the post associated with the notification.
     */
    public function getPost(): Post
    {
        return $this->post;
    }
    
    /**
     * Get the author associated with the notification.
     */
    public function getAuthor(): User
    {
        return $this->author;
    }
}
