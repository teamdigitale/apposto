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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('default_workstation_id')->nullable()->after('team_id');
            $table->foreign('default_workstation_id')->references('id')->on('desks')->onDelete('set null');
            $table->integer('ferie_totali')->default(26);
            $table->integer('ferie_usate')->default(0);
            $table->integer('giorni_in_smart')->default(0);
            $table->boolean('gestiamopresenze')->default(false);
            $table->boolean('superuser')->default(false);
            $table->boolean('addetto_emergenza')->default(false);
            $table->boolean('addetto_al_primo_soccorso')->default(false);
            $table->boolean('ruolo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
        //    $table->dropColumn('ruolo');
            $table->dropColumn('addetto_al_primo_soccorso');
            $table->dropColumn('addetto_emergenza');
            $table->dropColumn('superuser');
            $table->dropColumn('gestiamopresenze');
            $table->dropColumn('ferie_usate');
            $table->dropColumn('ferie_totali');
            $table->dropColumn('giorni_in_smart');
            $table->dropForeign(['default_workstation_id']);
            $table->dropColumn('default_workstation_id');
        });
    }
};
