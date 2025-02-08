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
            $table->integer('priority')->default(1);
            $table->boolean('allow_view')->default(0);
            $table->softDeletes();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->foreign('team_id')
                ->references('id')
                ->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('allow_view');
            $table->dropColumn('priority');
            $table->dropColumn('team_id');
            $table->dropForeign(['team_id']);
            
        });
    }
};
