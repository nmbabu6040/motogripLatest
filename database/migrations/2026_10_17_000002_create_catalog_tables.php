<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $t->string('slug')->unique();
            $t->json('name');
            $t->json('description')->nullable();
            $t->json('seo_title')->nullable();
            $t->json('seo_description')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['parent_id', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $t->string('slug')->unique();
            $t->string('sku', 64)->unique();
            $t->json('name');
            $t->json('short_description')->nullable();
            $t->json('description')->nullable();
            $t->json('handling_storage')->nullable();
            $t->string('product_type', 80)->nullable();
            $t->string('application', 120)->nullable()->index();
            $t->string('viscosity_grade', 40)->nullable()->index();
            $t->string('api_classification', 40)->nullable()->index();
            $t->string('iso_vg_grade', 20)->nullable()->index();
            $t->json('seo_title')->nullable();
            $t->json('seo_description')->nullable();
            $t->string('canonical_url')->nullable();
            $t->boolean('noindex')->default(false);
            $t->string('status', 20)->default('draft');          // draft | published | archived
            $t->boolean('is_featured')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'product_category_id']);
            $t->index(['status', 'is_featured']);
        });

        Schema::create('product_features', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 20);                              // feature | benefit | application
            $t->json('text');
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        // Only rows with is_verified = true are ever rendered publicly.
        Schema::create('product_specifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->json('name');
            $t->string('value', 120);
            $t->string('unit', 30)->nullable();
            $t->string('test_method', 60)->nullable();           // e.g. an ASTM/ISO method reference supplied by the lab
            $t->boolean('is_typical')->default(true);            // typical value vs guaranteed limit
            $t->boolean('is_verified')->default(false);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['product_id', 'is_verified']);
        });

        Schema::create('product_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20);                              // api | acea | oem | other
            $t->string('name', 120);
            $t->string('reference', 120)->nullable();
            $t->foreignId('evidence_document_id')->nullable();   // FK added after documents table
            $t->timestamp('verified_at')->nullable();            // null = never shown publicly
            $t->timestamps();
        });

        Schema::create('packaging_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('label', 60);
            $t->decimal('size_value', 8, 2);
            $t->string('size_unit', 10);                         // ml | l | g | kg
            $t->string('sku_suffix', 20)->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['product_id', 'is_active']);
            $t->index(['size_unit', 'size_value']);
        });

        Schema::create('product_related', function (Blueprint $t) {
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $t->string('relation', 20)->default('related');      // related | alternative
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->primary(['product_id', 'related_product_id']);
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20)->index();                     // tds | sds | catalog | brochure | guide | presentation | other
            $t->json('title');
            $t->string('disk', 30)->default('local');            // private disk by default
            $t->string('path');
            $t->string('original_name');
            $t->string('mime', 100);
            $t->unsignedBigInteger('size');
            $t->char('checksum', 64)->index();                   // sha256, duplicate detection
            $t->string('visibility', 20)->default('public');     // public | authenticated | restricted
            $t->string('language', 5)->default('en');
            $t->string('version', 20)->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::table('product_approvals', fn (Blueprint $t) => $t->foreign('evidence_document_id')->references('id')->on('documents')->nullOnDelete());

        // Privacy-safe aggregates: no IPs, no user ids.
        Schema::create('product_views', function (Blueprint $t) {
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->date('viewed_on');
            $t->unsignedInteger('views')->default(0);
            $t->primary(['product_id', 'viewed_on']);
        });

        Schema::create('document_downloads', function (Blueprint $t) {
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->date('downloaded_on');
            $t->unsignedInteger('downloads')->default(0);
            $t->primary(['document_id', 'downloaded_on']);
        });
    }

    public function down(): void
    {
        foreach (['document_downloads', 'product_views', 'product_approvals', 'documents', 'product_related', 'packaging_options', 'product_specifications', 'product_features', 'products', 'product_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
