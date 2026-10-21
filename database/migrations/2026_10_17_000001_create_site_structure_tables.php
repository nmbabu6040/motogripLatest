<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('status', 20)->default('active')->index()->after('email');   // active | pending | suspended
            $t->string('phone', 32)->nullable();
            $t->string('locale', 5)->default('en');
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('group', 60);
            $t->string('key', 100);
            $t->text('value')->nullable();      // non-secret values only; secrets live in .env
            $t->string('type', 20)->default('string');
            $t->timestamps();
            $t->unique(['group', 'key']);
        });

        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->string('location', 40)->unique();   // header | footer_products | footer_company ...
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $t->json('label');
            $t->string('url')->nullable();
            $t->string('route_name')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('opens_in_new_tab')->default(false);
            $t->timestamps();
            $t->index(['menu_id', 'parent_id', 'sort_order']);
        });

        Schema::create('homepage_sections', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->json('content')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0)->index();
            $t->boolean('is_enabled')->default(true);
            $t->timestamps();
        });

        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('title');
            $t->json('body')->nullable();
            $t->json('seo_title')->nullable();
            $t->json('seo_description')->nullable();
            $t->string('canonical_url')->nullable();
            $t->boolean('noindex')->default(false);
            $t->string('status', 20)->default('draft')->index();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('redirects', function (Blueprint $t) {
            $t->id();
            $t->string('from_path')->unique();
            $t->string('to_url', 2048);
            $t->unsignedSmallInteger('status_code')->default(301);
            $t->unsignedBigInteger('hits')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('faqable');          // product, category, or null for global FAQs
            $t->json('question');
            $t->json('answer');
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('settings');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['status', 'phone', 'locale', 'approved_at', 'last_login_at']));
    }
};
