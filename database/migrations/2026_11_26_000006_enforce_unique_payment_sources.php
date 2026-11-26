<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->string('student_registration_source', 20)->nullable()->virtualAs(
                "CASE WHEN section = 'students' THEN NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.registration_id')), 'null') END"
            );
            $table->string('cash_payment_source', 32)->nullable()->virtualAs(
                "CASE WHEN section = 'cashbook' THEN COALESCE(
                    CONCAT('REG-', NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.registration_id')), 'null')),
                    CONCAT('TEAM-', NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.team_registration_id')), 'null')),
                    CONCAT('IURAN-', NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.dues_id')), 'null'))
                ) END"
            );
            $table->unique('student_registration_source', 'records_student_registration_source_unique');
            $table->unique('cash_payment_source', 'records_cash_payment_source_unique');
            $table->index(['section', 'status', 'created_at'], 'records_section_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropUnique('records_student_registration_source_unique');
            $table->dropUnique('records_cash_payment_source_unique');
            $table->dropIndex('records_section_status_created_index');
            $table->dropColumn(['student_registration_source', 'cash_payment_source']);
        });
    }
};
