<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->float('total_amount');

            $table->integer('discount_summa')->default(0);
            $table->integer('discount_percentage')->default(0);

            $table->foreignId('tariff_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();

            $table->dateTime('date')->nullable();

            $table->enum('provider', [
                'payme',
                'click',
                'uzum',
                'oson',
                'transfer',
                'waiting',
                'pay_later'
            ])->default('waiting');

            $table->enum('status', [
                'prepaid',
                'not_paid'
            ])->default('not_paid');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_orders');
    }
};
