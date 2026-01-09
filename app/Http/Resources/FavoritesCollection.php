<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\Post;
use App\Models\User;
use App\Http\Resources\PostResource;

class FavoritesCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $posts = $this->collection
            ->filter(fn($favorite) => $favorite->favoritable_type === Post::class)
            ->map(fn($favorite) => new PostResource($favorite->favoritable))
            ->values();
            
        $users = $this->collection
            ->filter(fn($favorite) => $favorite->favoritable_type === User::class)
            ->map(fn($favorite) => [
                'id' => $favorite->favoritable->id,
                'name' => $favorite->favoritable->name,
            ])
            ->values();
            
        return [
            'data' => [
                'posts' => $posts,
                'users' => $users,
            ],
        ];
    }
}
