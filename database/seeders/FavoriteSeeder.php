<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\Post;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Favorite::factory()
            ->count(5)
            ->state(function () {
                return [
                    'favoritable_type' => Post::class,
                    'favoritable_id'   => function (array $attributes) {
                        return $attributes['post_id'];
                    }
                ];
            })
            ->create();
    }
}
