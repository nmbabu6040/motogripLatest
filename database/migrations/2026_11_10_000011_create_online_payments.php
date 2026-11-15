<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per attempt to pay an invoice online. Card and wallet details never reach this site: the gateway's own page takes them.
        Schema::create('online_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->restrictOnDelete();
            $t->string('tran_id', 64)->unique();
            $t->decimal('amount', 12, 2);
            $t->char('currency', 3)->default('BDT');
            $t->string('status', 12)->default('initiated')->index();   // initiated | paid | failed | cancelled | review
            $t->string('val_id')->nullable();
            $t->string('bank_tran_id')->nullable();
            $t->string('payer_name', 160)->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone', 32)->nullable();
            $t->string('note')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        // Secret part of the link that is sent to the customer, so the invoice number alone is never enough.
        Schema::table('sales', function (Blueprint $t) {
            $t->string('pay_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $t) => $t->dropColumn('pay_token'));
        Schema::dropIfExists('online_payments');
    }
};
