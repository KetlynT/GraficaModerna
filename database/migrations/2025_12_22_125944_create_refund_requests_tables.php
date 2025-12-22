<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela principal da solicitação
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('reason'); // Motivo do cliente
            $table->text('admin_notes')->nullable(); // Resposta do admin
            $table->decimal('refunded_amount', 10, 2)->nullable(); // Valor devolvido
            $table->string('proof_file')->nullable(); // Comprovante do admin
            $table->timestamps();
        });

        // Itens específicos da solicitação
        Schema::create('refund_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_requested'); // Quanto o cliente quer devolver
            $table->integer('quantity_restocked')->default(0); // Quanto o admin devolveu ao estoque
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_request_items');
        Schema::dropIfExists('refund_requests');
    }
};