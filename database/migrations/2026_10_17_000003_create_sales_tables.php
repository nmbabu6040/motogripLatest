<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20)->index();                     // contact | product | quotation | corporate | bulk | workshop_fleet
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name', 120);
            $t->string('email');
            $t->string('phone', 32)->nullable();
            $t->string('company', 160)->nullable();
            $t->text('message');
            $t->string('status', 20)->default('new')->index();   // new | in_progress | responded | closed | spam
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->date('follow_up_on')->nullable()->index();
            $t->string('locale', 5)->default('en');
            $t->timestamp('consent_at')->nullable();
            $t->char('ip_hash', 64)->nullable();                 // salted hash for rate limiting / abuse review, not raw IP
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'created_at']);
        });

        Schema::create('dealer_applications', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->char('tracking_token_hash', 64);                 // applicant tracks status with reference + token
            $t->string('business_name', 160);
            $t->string('contact_name', 120);
            $t->string('email');
            $t->string('phone', 32);
            $t->string('district', 80)->index();
            $t->string('city', 80)->nullable();
            $t->string('address', 255)->nullable();
            $t->string('business_type', 40);                     // dealer | distributor | workshop | fleet | other
            $t->unsignedTinyInteger('years_in_business')->nullable();
            $t->text('message')->nullable();
            $t->string('status', 20)->default('submitted')->index(); // submitted | under_review | more_info | approved | rejected
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->date('follow_up_on')->nullable();
            $t->timestamp('consent_at')->nullable();
            $t->char('ip_hash', 64)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('dealers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dealer_application_id')->nullable()->constrained()->nullOnDelete();
            $t->string('slug')->unique();
            $t->string('name', 160);
            $t->string('district', 80)->index();
            $t->string('city', 80)->nullable();
            $t->string('address', 255)->nullable();
            $t->string('phone', 32)->nullable();
            $t->string('email')->nullable();
            $t->boolean('show_phone')->default(false);           // privacy controls: hidden unless admin opts in
            $t->boolean('show_email')->default(false);
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->json('business_hours')->nullable();
            $t->boolean('is_published')->default(false)->index();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('internal_notes', function (Blueprint $t) {
            $t->id();
            $t->morphs('noteable');                              // inquiries, dealer applications
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('body');
            $t->timestamps();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->string('locale', 5)->default('en');
            $t->char('token_hash', 64)->unique();                // confirm / unsubscribe token
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamp('unsubscribed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['newsletter_subscribers', 'internal_notes', 'dealers', 'dealer_applications', 'inquiries'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
