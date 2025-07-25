<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHalfDaySessionAndDayTypeToLeaveApplications extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('day_type')->nullable()->after('leave_type');
            $table->string('start_half_session')->nullable(); // 'morning', 'afternoon', or null
            $table->string('end_half_session')->nullable();   // 'morning', 'afternoon', or null
            $table->string('supporting_document_path')->nullable()->after('reason');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn(['day_type', 'start_half_session', 'end_half_session', 'supporting_document_path']);
        });
    }
}
