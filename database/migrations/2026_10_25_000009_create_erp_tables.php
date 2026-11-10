<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 10)->index();                     // customer | supplier
            $t->string('name', 160);
            $t->string('phone', 32)->nullable();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        // One stock-keeping unit = a product in one pack size ("Titan 15W-40" + "4 L"). Stock itself is the sum of its movements.
        Schema::create('stock_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('pack', 60)->default('');
            $t->decimal('sale_price', 12, 2)->default(0);
            $t->decimal('cost_price', 12, 2)->default(0);        // weighted average purchase cost
            $t->unsignedInteger('reorder_level')->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'pack']);
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $t->integer('quantity');                              // + in, - out
            $t->string('type', 20)->index();                      // purchase | sale | sale_return | purchase_return | adjustment | void
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->date('moved_on')->index();
            $t->string('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['reference_type', 'reference_id']);
            $t->index(['stock_item_id', 'moved_on']);
        });

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('supplier_id')->constrained('parties')->restrictOnDelete();
            $t->date('purchased_on')->index();
            $t->string('status', 10)->default('received')->index();   // received | void
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_cost', 12, 2);
            $t->decimal('line_total', 12, 2);
        });

        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->string('customer_name', 160)->nullable();         // walk-in customer
            $t->date('sold_on')->index();
            $t->string('status', 10)->default('confirmed')->index();  // confirmed | void
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('tax_percent', 5, 2)->default(0);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('unit_cost', 12, 2)->default(0);          // average cost when sold, for profit reports
            $t->decimal('line_total', 12, 2);
        });

        Schema::create('sale_returns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->restrictOnDelete();
            $t->date('returned_on')->index();
            $t->string('reason')->nullable();
            $t->decimal('refund_total', 12, 2);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('line_total', 12, 2);
        });

        Schema::create('purchase_returns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $t->date('returned_on')->index();
            $t->string('reason')->nullable();
            $t->decimal('credit_total', 12, 2);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $t->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_cost', 12, 2);
            $t->decimal('line_total', 12, 2);
        });

        Schema::create('challans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->restrictOnDelete();
            $t->date('delivered_on')->index();
            $t->string('receiver_name', 160)->nullable();
            $t->string('receiver_phone', 32)->nullable();
            $t->text('delivery_address')->nullable();
            $t->string('vehicle_no', 40)->nullable();
            $t->string('driver_name', 120)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('challan_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('challan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('payable_type');                           // App\Models\Sale | App\Models\Purchase
            $t->unsignedBigInteger('payable_id');
            $t->decimal('amount', 12, 2);
            $t->string('method', 20)->default('cash');
            $t->date('paid_on')->index();
            $t->string('reference')->nullable();
            $t->string('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        foreach (['payments', 'challan_items', 'challans', 'purchase_return_items', 'purchase_returns', 'sale_return_items', 'sale_returns', 'sale_items', 'sales', 'purchase_items', 'purchases', 'stock_movements', 'stock_items', 'parties'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
