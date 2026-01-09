<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Requests\DestroyPostRequest;
use App\Notifications\NewPostByFavoritedUser;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * @group Posts
 *
 * API endpoints for managing posts
 */
class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->orderByDesc('created_at')->get();
        return PostResource::collection($posts);
    }

    public function store(CreatePostRequest $request)
    {
        $user = $request->user();
        $postData = [
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'user_id' => $user->id,
        ];
        
        // Handle image upload if present
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('posts', 'public');
            $postData['image_path'] = $imagePath;
        }

        // Create a new post
        $post = Post::create($postData);
        
        // Find all users who have favorited this user
        $this->notifyFollowers($user, $post);

        return new PostResource($post);
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $postData = [
            'title' => $request->input('title'),
            'body' => $request->input('body'),
        ];
        
        // Handle image upload if present
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            
            $image = $request->file('image');
            $imagePath = $image->store('posts', 'public');
            $postData['image_path'] = $imagePath;
        }

        $post->update($postData);

        return new PostResource($post);
    }

    public function destroy(DestroyPostRequest $request, Post $post)
    {
        // Delete associated image if exists
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
        
        $post->delete();

        return response()->noContent();
    }
    
    /**
     * Notify followers about a new post
     */
    protected function notifyFollowers(User $author, Post $post)
    {
        // Find all users who have favorited this author
        $followers = User::whereHas('favorites', function ($query) use ($author) {
            $query->where('favoritable_type', User::class)
                  ->where('favoritable_id', $author->id);
        })->get();
        
        // Send notifications asynchronously
        if ($followers->count() > 0) {
            Notification::send($followers, new NewPostByFavoritedUser($post, $author));
        }
    }
}
