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
            // Add the new columns
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason'); // Placed after rejection_reason for logical grouping

            // This single line creates the 'approved_by' column AND the foreign key constraint.
            // It links 'approved_by' to the 'id' on the 'users' table.
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approved_at');

            // The line below was redundant and has been REMOVED as it caused the error.
            // $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // The dropForeign method needs the column name(s) in an array
            // It will correctly drop the key created by the ->constrained() method above.
            $table->dropForeign(['approved_by']);

            // Drop the columns we added
            $table->dropColumn(['rejection_reason', 'approved_by', 'approved_at']);
        });
    }
};
