<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FavoriteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Favorite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $post = Post::factory()->create();

        return [
            'post_id'          => $post->id,
            'user_id'          => User::factory(),
            'favoritable_type' => Post::class,
            'favoritable_id'   => $post->id,
        ];
    }

    /**
     * Configure the model factory to create a favorite for a user.
     */
    public function forUser(): self
    {
        return $this->state(function () {
            $user = User::factory()->create();

            return [
                'post_id'          => null,
                'favoritable_type' => User::class,
                'favoritable_id'   => $user->id,
            ];
        });
    }
}
