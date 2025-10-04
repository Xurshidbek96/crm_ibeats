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
        Schema::create('setting_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id')->index();
            $table->integer('conts_id')->index();
            $table->text('actions');
            $table->text('action_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_permissions');
    }
};
