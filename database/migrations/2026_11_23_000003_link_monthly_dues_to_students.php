<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->constrained('records')->restrictOnDelete();
            $table->date('billing_month')->nullable();
            $table->unique(['student_id', 'billing_month']);
        });

        // Only link known demo fixtures. Unstructured records remain available for review.
        if (!app()->environment('local')) {
            return;
        }
        DB::table('records')->where('section', 'dues')->whereNotNull('date')->get()->each(function ($due) {
            $payload = json_decode($due->payload ?? '', true);
            if (!str_starts_with($payload['notes'] ?? '', 'Data fiktif untuk presentasi.')) {
                return;
            }
            $name = strstr($due->title, ' · iuran bulan ini', true);
            if ($name === false) {
                return;
            }
            $matches = DB::table('records')->where('section', 'students')->where('title', $name)->pluck('id');
            if ($matches->count() === 1) {
                $month = substr($due->date, 0, 7).'-01';
                $exists = DB::table('records')->where('student_id', $matches->first())->where('billing_month', $month)->exists();
                if (!$exists) {
                    DB::table('records')->where('id', $due->id)->update(['student_id' => $matches->first(), 'billing_month' => $month]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropUnique(['student_id', 'billing_month']);
            $table->dropColumn(['student_id', 'billing_month']);
        });
    }
};