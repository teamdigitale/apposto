<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slack_channel')->nullable()->after('description');
            $table->text('drive_folder')->nullable()->after('slack_channel');
            $table->text('documentation_url')->nullable()->after('drive_folder');
            $table->text('resources_notes')->nullable()->after('documentation_url');
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['slack_channel', 'drive_folder', 'documentation_url', 'resources_notes']);
        });
    }
};