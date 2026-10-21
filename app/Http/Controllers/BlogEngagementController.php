<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Services\LikeService;
use App\Support\Notify;
use Illuminate\Http\Request;

class BlogEngagementController extends Controller
{
    public function like(Request $request, BlogPost $post, LikeService $likes)
    {
        abort_unless($post->isPublished(), 404);
        $likes->toggle($post, $request);

        return back();
    }

    public function comment(Request $request, BlogPost $post)
    {
        abort_unless($post->isPublished(), 404);
        $user = $request->user();
        abort_unless($user || config('motogrip.features.guest_comments'), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
            'guest_name' => [$user ? 'nullable' : 'required', 'string', 'max:80'],
            'guest_email' => [$user ? 'nullable' : 'required', 'email:rfc', 'max:190'],
            'website' => ['nullable', 'max:0'],   // honeypot
        ]);

        if ($request->filled('website')) {
            return back()->with('status', __('site.comment_pending'));
        }

        // Replies attach to a top-level comment of this post; deeper replies are flattened to that thread.
        $parentId = null;
        if (! empty($data['parent_id'])) {
            $parent = Comment::where('blog_post_id', $post->id)->where('status', 'approved')->find($data['parent_id']);
            abort_unless($parent, 422);
            $parentId = $parent->parent_id ?? $parent->id;
        }

        $links = preg_match_all('~https?://|www\.~i', $data['body']);
        $trusted = $user?->can('comments.moderate');
        $status = $trusted ? 'approved' : ($links > 2 ? 'spam' : 'pending');

        $comment = Comment::create([
            'blog_post_id' => $post->id, 'parent_id' => $parentId, 'user_id' => $user?->id,
            'guest_name' => $user ? null : $data['guest_name'], 'guest_email' => $user ? null : $data['guest_email'],
            'body' => $data['body'], 'status' => $status, 'ip_hash' => Notify::ipHash($request->ip()),
        ]);

        if ($status === 'approved') {
            $post->increment('comments_count');
        }

        return back()->with('status', $status === 'approved' ? __('site.comment_posted') : __('site.comment_pending'));
    }

    public function report(Request $request, Comment $comment)
    {
        abort_unless($comment->status === 'approved', 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $hash = Notify::ipHash($request->ip());

        $already = CommentReport::where('comment_id', $comment->id)->where(fn ($q) => $q->where('ip_hash', $hash)->when($request->user(), fn ($w) => $w->orWhere('user_id', $request->user()->id)))->exists();
        if (! $already) {
            CommentReport::create(['comment_id' => $comment->id, 'user_id' => $request->user()?->id, 'ip_hash' => $hash, 'reason' => $data['reason']]);
        }

        return back()->with('status', __('site.report_thanks'));
    }
}
