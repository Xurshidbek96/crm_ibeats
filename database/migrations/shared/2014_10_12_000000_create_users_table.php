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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone1')->unique();
            $table->string('phone2')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('passport')->length(9)->nullable();
            $table->string('place_of_issue')->nullable();
            $table->date('date_of_issue')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('gender')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('place_of_residence')->nullable();
            $table->string('family_status')->nullable();
            $table->integer('number_of_children')->nullable();
            $table->integer('role_id')->default(2);
            $table->integer('status')->default(0)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
