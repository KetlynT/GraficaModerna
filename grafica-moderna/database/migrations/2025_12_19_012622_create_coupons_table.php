<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->decimal('discount_percentage', 5, 2);
            $table->timestamp('expiry_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Referência ao User
            $table->string('coupon_code');
            $table->uuid('order_id'); // Referência ao Order
            $table->timestamp('used_at')->useCurrent();
            
            // Índices para performance
            $table->index('user_id');
            $table->index('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};