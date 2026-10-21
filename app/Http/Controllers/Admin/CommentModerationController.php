<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentModerationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'pending');

        $comments = Comment::with(['post', 'user'])->withCount(['reports' => fn ($q) => $q->whereNull('resolved_at')])
            ->when($filter === 'reported', fn ($q) => $q->has('reports'))
            ->when(in_array($filter, ['pending', 'approved', 'rejected', 'spam'], true), fn ($q) => $q->where('status', $filter))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.blog.comments', ['comments' => $comments, 'filter' => $filter]);
    }

    public function update(Request $request, Comment $comment)
    {
        $status = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'spam'])]])['status'];

        $comment->update(['status' => $status]);
        $comment->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);
        $this->recount($comment->blog_post_id);

        activity('admin')->causedBy($request->user())->performedOn($comment)->withProperties(['status' => $status])->log('comment.moderated');

        return back()->with('status', 'Comment '.$status.'.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $postId = $comment->blog_post_id;
        activity('admin')->causedBy($request->user())->performedOn($comment)->log('comment.deleted');
        $comment->delete();
        $this->recount($postId);

        return back()->with('status', 'Comment deleted.');
    }

    private function recount(int $postId): void
    {
        BlogPost::whereKey($postId)->update(['comments_count' => Comment::where('blog_post_id', $postId)->where('status', 'approved')->count()]);
    }
}
