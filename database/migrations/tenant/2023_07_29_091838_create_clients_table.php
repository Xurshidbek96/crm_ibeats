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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('middle_name')->nullable();
            $table->string('passport')->length(9)->nullable();
            $table->string('place_of_issue')->nullable();
            $table->date('date_of_issue')->nullable();
            $table->string('file_passport')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('gender')->nullable()->default(0);
            $table->string('place_of_birth')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->string('place_of_residence')->nullable();
            $table->string('workplace')->nullable();
            $table->string('specialization')->nullable();
            $table->string('family_status')->nullable();
            $table->integer('number_of_children')->nullable();
            $table->json('phones')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('file')->nullable();
            $table->integer('status')->nullable()->default(0);
            $table->string('bail_name')->nullable();
            $table->string('bail_phone')->nullable();
			$table->string('guarantor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
