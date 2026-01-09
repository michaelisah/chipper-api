<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\CreateFavoriteRequest;
use Illuminate\Http\Response;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\FavoritesCollection;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()->with('favoritable')->get();
        return new FavoritesCollection($favorites);
    }

    public function storePost(CreateFavoriteRequest $request, Post $post)
    {
        $request->user()->favorites()->create([
            'favoritable_type' => Post::class,
            'favoritable_id'   => $post->id
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroyPost(Request $request, Post $post)
    {
        $favorite = $request->user()->favorites()
            ->where('favoritable_type', Post::class)
            ->where('favoritable_id', $post->id)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }

    public function storeUser(Request $request, User $user)
    {
        // Prevent users from favoriting themselves
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot favorite yourself'
            ], Response::HTTP_BAD_REQUEST);
        }

        $request->user()->favorites()->create([
            'favoritable_type' => User::class,
            'favoritable_id'   => $user->id
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroyUser(Request $request, User $user)
    {
        $favorite = $request->user()->favorites()
            ->where('favoritable_type', User::class)
            ->where('favoritable_id', $user->id)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
