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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('NumberOrder')->nullable()->default(null)->startFrom(100000);
            $table->foreignId('client_id')->constrained();
            $table->foreignId('device_id')->constrained();
            $table->foreignId('user_id')->constrained('employees');
            $table->integer('pay_type');
            $table->boolean('is_cash');
            $table->bigInteger('body_price')->nullable();
            $table->bigInteger('summa');
            $table->double('initial_payment', 14, 2)->nullable();
            $table->double('rest_summa', 14, 2)->nullable();
            $table->float('discount', 14,2)->nullable();
            $table->float('benefit', 14,2)->nullable();
            $table->integer('box');
            $table->integer('pay_day');
            $table->integer('status')->default(0);
            $table->dateTime('startDate')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('type')->default('device');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
