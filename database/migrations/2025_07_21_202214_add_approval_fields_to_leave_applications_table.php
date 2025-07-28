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
        Schema::table('leave_applications', function (Blueprint $table) {
            // ✅ FIX: Add the 'rejection_reason' column first. We'll place it after 'status'.
            $table->text('rejection_reason')->nullable()->after('status');

            // Now, these lines will work because 'rejection_reason' exists.
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // ✅ FIX: Make sure the down method can remove ALL new columns.
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['rejection_reason', 'approved_by', 'approved_at']);
        });
    }
};