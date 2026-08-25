<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('presences')
            ->select('user_id', 'date', DB::raw('MAX(id) as keep_id'))
            ->groupBy('user_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('presences')
                ->where('user_id', $row->user_id)
                ->where('date', $row->date)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('presences', function (Blueprint $table) {
            $table->unique(['user_id', 'date'], 'presences_user_id_date_unique');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['desk_id', 'status', 'from_date', 'to_date'], 'bookings_overlap_lookup_index');
            $table->index(['plan_id', 'is_exclusive', 'status'], 'bookings_exclusive_plan_index');
            $table->index(['user_id', 'status', 'from_date'], 'bookings_user_status_from_index');
        });
    }

    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            $table->dropUnique('presences_user_id_date_unique');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_overlap_lookup_index');
            $table->dropIndex('bookings_exclusive_plan_index');
            $table->dropIndex('bookings_user_status_from_index');
        });
    }
};
