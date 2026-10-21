<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category') ? BlogCategory::where('slug', $request->query('category'))->first() : null;
        $tag = $request->query('tag') ? BlogTag::where('slug', $request->query('tag'))->first() : null;

        $posts = BlogPost::published()->with(['category', 'media'])
            ->when($category, fn ($q) => $q->where('blog_category_id', $category->id))
            ->when($tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->whereKey($tag->id)))
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', '%'.addcslashes($t, '%_\\').'%')->orWhere('excerpt', 'like', '%'.addcslashes($t, '%_\\').'%')))
            ->orderByDesc('is_featured')->latest('published_at')->paginate(9)->withQueryString();

        return view('blog.index', ['posts' => $posts, 'categories' => BlogCategory::orderBy('slug')->get(), 'activeCategory' => $category, 'activeTag' => $tag]);
    }

    public function show(Request $request, BlogPost $post)
    {
        abort_unless($post->isPublished() || $request->user()?->can('blog.manage'), 404);

        $post->load(['author', 'category', 'tags', 'media']);

        if ($post->isPublished()) {
            DB::table('blog_post_views')->upsert([['blog_post_id' => $post->id, 'viewed_on' => today()->toDateString(), 'views' => 1]], ['blog_post_id', 'viewed_on'], ['views' => DB::raw('views + 1')]);
        }

        $comments = $post->comments()->where('status', 'approved')->whereNull('parent_id')->with(['user', 'replies.user'])->oldest()->get();

        return view('blog.show', [
            'post' => $post, 'comments' => $comments,
            'liked' => app(\App\Services\LikeService::class)->hasLiked($post, $request),
            'related' => BlogPost::published()->where('id', '!=', $post->id)->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))->with('media')->latest('published_at')->limit(3)->get(),
        ]);
    }
}
