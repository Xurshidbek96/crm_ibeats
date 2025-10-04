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
        Schema::create('monthlies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->integer('payment_month');
            $table->string('month');
            $table->float('summa', 14,2);
            $table->float('rest_summa', 14,2)->nullable();
            $table->string('comment')->nullable();
            $table->integer('status')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthlies');
    }
};
