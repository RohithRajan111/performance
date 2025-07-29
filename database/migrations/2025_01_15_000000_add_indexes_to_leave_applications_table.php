<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // Check if indexes don't already exist before creating them
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('leave_applications');
            
            // Index for user_id and status combination (most common query pattern)
            if (!isset($indexes['leave_applications_user_status_index'])) {
                $table->index(['user_id', 'status'], 'leave_applications_user_status_index');
            }
            
            // Index for date range queries
            if (!isset($indexes['leave_applications_date_range_index'])) {
                $table->index(['start_date', 'end_date'], 'leave_applications_date_range_index');
            }
            
            // Index for status queries (for managers viewing all applications)
            if (!isset($indexes['leave_applications_status_index'])) {
                $table->index('status', 'leave_applications_status_index');
            }
            
            // Index for user_id and date range (for overlap checking)
            if (!isset($indexes['leave_applications_user_dates_index'])) {
                $table->index(['user_id', 'start_date', 'end_date'], 'leave_applications_user_dates_index');
            }
            
            // Index for approved_by for approval tracking (only if column exists)
            if (Schema::hasColumn('leave_applications', 'approved_by') && !isset($indexes['leave_applications_approved_by_index'])) {
                $table->index('approved_by', 'leave_applications_approved_by_index');
            }
            
            // Index for leave_type for filtering (only if column exists)
            if (Schema::hasColumn('leave_applications', 'leave_type') && !isset($indexes['leave_applications_leave_type_index'])) {
                $table->index('leave_type', 'leave_applications_leave_type_index');
            }
            
            // Composite index for year-based leave balance calculations
            if (!isset($indexes['leave_applications_balance_calc_index'])) {
                $table->index(['user_id', 'status', 'start_date'], 'leave_applications_balance_calc_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex('leave_applications_user_status_index');
            $table->dropIndex('leave_applications_date_range_index');
            $table->dropIndex('leave_applications_status_index');
            $table->dropIndex('leave_applications_user_dates_index');
            
            if (Schema::hasColumn('leave_applications', 'approved_by')) {
                $table->dropIndex('leave_applications_approved_by_index');
            }
            
            if (Schema::hasColumn('leave_applications', 'leave_type')) {
                $table->dropIndex('leave_applications_leave_type_index');
            }
            
            $table->dropIndex('leave_applications_balance_calc_index');
        });
    }
};