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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sur_name')->nullable();
            $table->string('phone', 12);
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->integer('role_id')->default(0);
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            // $table->string('schema_name')->default('public');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
