<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Like;
use App\Support\Notify;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeService
{
    /** Signed-in users are keyed by user id; guests by a salted hash of IP and browser. Uniqueness is enforced by the database. */
    private function identity(Request $request): array
    {
        return $request->user()
            ? ['user_id' => $request->user()->id, 'visitor_hash' => null]
            : ['user_id' => null, 'visitor_hash' => Notify::ipHash($request->ip().'|'.substr((string) $request->userAgent(), 0, 120))];
    }

    private function query(BlogPost $post, Request $request)
    {
        $id = $this->identity($request);

        return Like::where('blog_post_id', $post->id)->when($id['user_id'], fn ($q) => $q->where('user_id', $id['user_id']), fn ($q) => $q->where('visitor_hash', $id['visitor_hash']));
    }

    public function hasLiked(BlogPost $post, Request $request): bool
    {
        return $this->query($post, $request)->exists();
    }

    public function toggle(BlogPost $post, Request $request): bool
    {
        return DB::transaction(function () use ($post, $request) {
            if ($existing = $this->query($post, $request)->first()) {
                $existing->delete();
                $liked = false;
            } else {
                try {
                    Like::create(['blog_post_id' => $post->id] + $this->identity($request));
                } catch (UniqueConstraintViolationException) {
                    // A concurrent request already recorded this like.
                }
                $liked = true;
            }
            $post->forceFill(['likes_count' => Like::where('blog_post_id', $post->id)->count()])->saveQuietly();

            return $liked;
        });
    }
}
