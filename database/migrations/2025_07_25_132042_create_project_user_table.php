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
        // This creates the 'project_user' table to link projects and users.
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            // Link to the 'projects' table
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            // Link to the 'users' table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};