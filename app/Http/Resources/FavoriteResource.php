<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Post;
use App\Models\User;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        
        if ($this->favoritable_type === Post::class) {
            $data['favoritable_type'] = 'post';
            $data['favoritable_id'] = $this->favoritable_id;
            $data['post'] = new PostResource($this->favoritable);
        } elseif ($this->favoritable_type === User::class) {
            $data['favoritable_type'] = 'user';
            $data['favoritable_id'] = $this->favoritable_id;
            $data['user'] = [
                'id' => $this->favoritable->id,
                'name' => $this->favoritable->name,
            ];
        }
        
        return $data;
    }
}
