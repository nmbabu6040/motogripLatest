<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Affiliate tables exist but stay unused until config('motogrip.features.affiliate') is enabled.
    // No payment details are stored here by design; payouts are handled outside the app until legal/payment terms exist.
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('code', 40)->unique();                    // opaque public code, not derived from personal data
            $t->string('status', 20)->default('pending')->index(); // pending | approved | suspended
            $t->timestamp('terms_accepted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('affiliate_campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->unsignedSmallInteger('attribution_window_days')->default(30);
            $t->decimal('commission_rate', 5, 2)->nullable();    // percent, if commission model is percentage
            $t->decimal('commission_flat', 12, 2)->nullable();
            $t->char('currency', 3)->nullable();
            $t->boolean('is_active')->default(false);
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->timestamps();
        });

        Schema::create('referral_clicks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $t->foreignId('affiliate_campaign_id')->nullable()->constrained()->nullOnDelete();
            $t->char('token_hash', 64)->unique();                // cookie holds the token; only its hash is stored
            $t->string('landing_path', 255);
            $t->char('visitor_hash', 64)->nullable();
            $t->timestamp('clicked_at')->useCurrent()->index();
        });

        Schema::create('affiliate_conversions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $t->foreignId('referral_click_id')->nullable()->constrained('referral_clicks')->nullOnDelete();
            $t->foreignId('inquiry_id')->nullable()->unique()->constrained()->nullOnDelete();           // one conversion per lead
            $t->foreignId('dealer_application_id')->nullable()->unique()->constrained()->nullOnDelete();
            $t->string('type', 20)->default('lead');             // lead | dealer | sale (sale only if e-commerce is ever added)
            $t->string('status', 20)->default('pending')->index(); // pending | approved | rejected | reversed
            $t->timestamps();
        });

        Schema::create('affiliate_commissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('affiliate_conversion_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->char('currency', 3);
            $t->string('status', 20)->default('pending')->index(); // pending | approved | paid | cancelled
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->string('adjustment_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('backup_records', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20);                              // database | media | full
            $t->string('disk', 40);
            $t->string('path')->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->char('checksum', 64)->nullable();
            $t->string('status', 20)->default('running')->index(); // running | succeeded | failed
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamp('verified_at')->nullable();            // set only after an integrity check passes
            $t->timestamp('restore_tested_at')->nullable();      // set only after a real restore test
            $t->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('error')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['backup_records', 'affiliate_commissions', 'affiliate_conversions', 'referral_clicks', 'affiliate_campaigns', 'affiliates'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
