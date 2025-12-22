<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            $table->text('shipping_address');
            $table->string('shipping_zip_code');
            $table->decimal('shipping_cost', 10, 2);
            $table->string('shipping_method');
            $table->timestamp('delivery_date')->nullable();
            $table->string('tracking_code')->nullable();

            $table->string('status')->default('Pendente');
            $table->timestamp('order_date');
            $table->decimal('sub_total', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            
            $table->string('applied_coupon')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_session_id')->nullable();
            
            $table->string('refund_type')->nullable();
            $table->decimal('refund_requested_amount', 10, 2)->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->text('refund_rejection_reason')->nullable();
            $table->string('refund_rejection_proof')->nullable();
            $table->string('reverse_logistics_code')->nullable();
            $table->text('return_instructions')->nullable();
            
            $table->string('customer_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payment_warning')->nullable();
            
            $table->timestamps();
        });

        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('status');
            $table->text('message')->nullable();
            $table->string('changed_by')->nullable();
            $table->timestamp('timestamp');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_histories');
        Schema::dropIfExists('orders');
    }
};