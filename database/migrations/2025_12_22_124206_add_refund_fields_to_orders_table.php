<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Valor efetivamente reembolsado (pode ser menor que o total)
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('total');
            
            // Caminho do arquivo de comprovante
            $table->string('refund_proof')->nullable()->after('refunded_amount');
            
            // Motivo da recusa ou detalhes do admin
            $table->text('admin_notes')->nullable()->after('refund_proof');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount', 'refund_proof', 'admin_notes']);
        });
    }
};