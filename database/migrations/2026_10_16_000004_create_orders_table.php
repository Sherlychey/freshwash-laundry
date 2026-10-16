<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 30)->unique();
            $table->string('tracking_token', 64)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('online')->index();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->unsignedSmallInteger('quantity_items')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('delivery_method', 20)->default('pickup');
            $table->text('delivery_address')->nullable();
            $table->dateTime('pickup_schedule')->nullable();
            $table->dateTime('delivery_schedule')->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('order_status', 40)->default('pending_confirmation')->index();
            $table->dateTime('estimated_completion_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['order_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
