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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('trans_id')->unique()->index('transId');
            $table->float('amount');

            $table->enum('provider', [
                'payme',
                'click',
                'uzum',
                'oson'
            ]);
            $table->enum('status', [
                'PREPARED',
                'COMPLETED',
                'CANCELED'
            ])->default('PREPARED');

            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();

            $table->string('prepare_id')->nullable();
            $table->string('paydoc_id')->nullable();
            $table->string('merchant_trans_id')->nullable();

            $table->string('sign')->nullable();
            $table->integer('reason')->nullable();
            $table->integer('state')->nullable();

            $table->dateTime('create_time')->nullable();
            $table->dateTime('perform_time')->nullable();
            $table->dateTime('cancel_time')->nullable();
            $table->bigInteger('provider_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
