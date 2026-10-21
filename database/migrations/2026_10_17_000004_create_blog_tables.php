<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->json('bio')->nullable();
            $t->json('social_links')->nullable();
            $t->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('name');
            $t->json('description')->nullable();
            $t->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('name');
            $t->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('slug')->unique();
            $t->json('title');
            $t->json('excerpt')->nullable();
            $t->json('body')->nullable();                        // sanitized on save (mews/purifier)
            $t->json('seo_title')->nullable();
            $t->json('seo_description')->nullable();
            $t->string('canonical_url')->nullable();
            $t->boolean('noindex')->default(false);
            $t->string('status', 20)->default('draft');         // draft | review | scheduled | published
            $t->boolean('is_featured')->default(false);
            $t->unsignedSmallInteger('reading_time_minutes')->default(1);
            $t->unsignedInteger('likes_count')->default(0);      // denormalized, maintained from the likes table
            $t->unsignedInteger('comments_count')->default(0);   // approved comments only
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'published_at']);
        });

        Schema::create('blog_post_tag', function (Blueprint $t) {
            $t->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['blog_post_id', 'blog_tag_id']);
        });

        Schema::create('blog_post_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->json('snapshot');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('guest_name', 80)->nullable();
            $t->string('guest_email')->nullable();
            $t->text('body');
            $t->string('status', 20)->default('pending');        // pending | approved | rejected | spam
            $t->char('ip_hash', 64)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['blog_post_id', 'status', 'parent_id']);
        });

        Schema::create('comment_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->char('ip_hash', 64)->nullable();
            $t->string('reason', 255);
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        // Duplicate-like prevention is enforced by the database, not just by application code.
        Schema::create('likes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->char('visitor_hash', 64)->nullable();            // guests: salted hash, only if guest likes are enabled
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['blog_post_id', 'user_id']);
            $t->unique(['blog_post_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        foreach (['likes', 'comment_reports', 'comments', 'blog_post_revisions', 'blog_post_tag', 'blog_posts', 'blog_tags', 'blog_categories', 'author_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
