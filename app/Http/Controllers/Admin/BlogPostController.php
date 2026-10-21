<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostRevision;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::with(['author', 'category'])
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where('title', 'like', "%{$t}%"))
            ->latest('updated_at')->paginate(20)->withQueryString();

        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.blog.form', ['post' => new BlogPost(['status' => 'draft']), 'categories' => BlogCategory::orderBy('slug')->get(), 'tagNames' => '']);
    }

    public function store(Request $request)
    {
        $post = DB::transaction(function () use ($request) {
            $post = new BlogPost(['author_id' => $request->user()->id]);
            $this->save($request, $post);
            return $post;
        });
        activity('admin')->causedBy($request->user())->performedOn($post)->log('post.created');

        return redirect()->route('admin.blog.edit', $post)->with('status', 'Article saved.');
    }

    public function edit(BlogPost $post)
    {
        return view('admin.blog.form', ['post' => $post->load(['tags', 'media']), 'categories' => BlogCategory::orderBy('slug')->get(), 'tagNames' => $post->tags->map(fn ($t) => $t->getTranslation('name', 'en', false))->join(', '), 'revisions' => $post->revisions()->with('user')->limit(10)->get()]);
    }

    public function update(Request $request, BlogPost $post)
    {
        DB::transaction(function () use ($request, $post) {
            $this->snapshot($post, $request->user()->id);
            $this->save($request, $post);
        });
        activity('admin')->causedBy($request->user())->performedOn($post)->withProperties(['status' => $post->status])->log('post.updated');

        return back()->with('status', 'Article saved.');
    }

    public function restore(Request $request, BlogPost $post, BlogPostRevision $revision)
    {
        abort_unless($revision->blog_post_id === $post->id, 404);
        $this->snapshot($post, $request->user()->id);

        $s = $revision->snapshot;
        $post->fill(['title' => $s['title'] ?? [], 'excerpt' => $s['excerpt'] ?? [], 'body' => $s['body'] ?? []])->save();
        activity('admin')->causedBy($request->user())->performedOn($post)->withProperties(['revision' => $revision->id])->log('post.revision_restored');

        return back()->with('status', 'Revision restored.');
    }

    public function destroy(Request $request, BlogPost $post)
    {
        activity('admin')->causedBy($request->user())->performedOn($post)->log('post.deleted');
        $post->delete();

        return redirect()->route('admin.blog.index')->with('status', 'Article moved to trash.');
    }

    private function snapshot(BlogPost $post, int $userId): void
    {
        BlogPostRevision::create(['blog_post_id' => $post->id, 'user_id' => $userId, 'snapshot' => [
            'title' => $post->getTranslations('title'), 'excerpt' => $post->getTranslations('excerpt'), 'body' => $post->getTranslations('body'), 'status' => $post->status,
        ]]);
        // Keep the newest 20 revisions.
        $old = BlogPostRevision::where('blog_post_id', $post->id)->latest('created_at')->latest('id')->skip(20)->take(1000)->pluck('id');
        BlogPostRevision::whereIn('id', $old)->delete();
    }

    private function save(Request $request, BlogPost $post): void
    {
        $user = $request->user();

        $d = $request->validate([
            'title.en' => ['required', 'string', 'max:200'], 'title.bn' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:160', Rule::unique('blog_posts', 'slug')->ignore($post->id)],
            'excerpt.en' => ['nullable', 'string', 'max:400'], 'excerpt.bn' => ['nullable', 'string', 'max:400'],
            'body.en' => ['nullable', 'string', 'max:100000'], 'body.bn' => ['nullable', 'string', 'max:100000'],
            'blog_category_id' => ['nullable', Rule::exists('blog_categories', 'id')],
            'tags' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(BlogPost::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'seo_title.en' => ['nullable', 'string', 'max:70'], 'seo_description.en' => ['nullable', 'string', 'max:170'],
            'canonical_url' => ['nullable', 'url', 'max:300'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'image_alt' => ['nullable', 'string', 'max:160'],
        ]);

        $wantsLive = in_array($d['status'], ['scheduled', 'published'], true);
        if ($wantsLive && $post->status !== $d['status'] && ! $user->can('blog.publish')) {
            throw ValidationException::withMessages(['status' => 'You do not have permission to schedule or publish articles.']);
        }
        if ($d['status'] === 'scheduled' && (empty($d['published_at']) || now()->gte($d['published_at']))) {
            throw ValidationException::withMessages(['published_at' => 'Choose a future date and time for a scheduled article.']);
        }

        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');
        // Article HTML is sanitised on save, before it is ever stored.
        $body = $clean(array_map(fn ($h) => $h === null ? null : Purifier::clean($h), $d['body'] ?? []));

        $post->fill([
            'blog_category_id' => $d['blog_category_id'] ?? null,
            'slug' => $d['slug'] ?? $post->slug ?? $this->uniqueSlug($d['title']['en'], $post),
            'title' => $clean($d['title']), 'excerpt' => $clean($d['excerpt'] ?? []), 'body' => $body,
            'seo_title' => $clean($d['seo_title'] ?? []), 'seo_description' => $clean($d['seo_description'] ?? []),
            'canonical_url' => $d['canonical_url'] ?? null, 'noindex' => $request->boolean('noindex'), 'is_featured' => $request->boolean('is_featured'),
            'status' => $d['status'], 'reading_time_minutes' => BlogPost::readingTime($body['en'] ?? reset($body) ?: ''),
            'published_at' => $d['status'] === 'published' ? ($d['published_at'] ?? $post->published_at ?? now()) : ($d['status'] === 'scheduled' ? $d['published_at'] : ($d['published_at'] ?? null)),
        ])->save();

        $tagIds = collect(explode(',', (string) ($d['tags'] ?? '')))->map(fn ($t) => trim($t))->filter()->unique()->take(12)->map(function ($name) {
            return BlogTag::firstOrCreate(['slug' => Str::slug($name) ?: Str::random(6)], ['name' => ['en' => $name]])->id;
        });
        $post->tags()->sync($tagIds);

        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $post->addMedia($file)->usingFileName(Str::random(24).'.'.$file->extension())->withCustomProperties(['alt' => $d['image_alt'] ?? ''])->toMediaCollection('featured');
        }
    }

    private function uniqueSlug(string $title, BlogPost $post): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        for ($i = 2; BlogPost::withTrashed()->where('slug', $slug)->where('id', '!=', $post->id ?? 0)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        return $slug;
    }
}
