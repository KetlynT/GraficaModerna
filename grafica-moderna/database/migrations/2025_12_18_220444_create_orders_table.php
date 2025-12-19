use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Guid Id
            $table->uuid('user_id'); // string UserId
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('delivery_date')->nullable();
            
            // Valores monetários
            $table->decimal('sub_total', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('shipping_cost', 10, 2);
            $table->string('shipping_method');
            $table->decimal('total_amount', 10, 2);
            
            $table->string('applied_coupon')->nullable();
            $table->string('status')->default('Pendente'); // OrderStatus Enum
            $table->string('tracking_code')->nullable();
            
            // Stripe
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            
            // Reembolso e Logística Reversa
            $table->string('reverse_logistics_code')->nullable();
            $table->text('return_instructions')->nullable();
            $table->string('refund_type')->nullable();
            $table->decimal('refund_requested_amount', 10, 2)->nullable();
            $table->string('refund_rejection_reason')->nullable();
            $table->string('refund_rejection_proof')->nullable();

            // Endereço e Auditoria
            $table->text('shipping_address');
            $table->string('shipping_zip_code');
            $table->string('customer_ip')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps(); // CreatedAt, UpdatedAt
            
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};