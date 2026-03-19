<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['join', 'leave']); // join = unirsi, leave = lasciare
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('role')->nullable(); // Per richieste join
            $table->text('message')->nullable(); // Messaggio opzionale dell'utente
            $table->text('admin_notes')->nullable(); // Note admin
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // Chi ha approvato/rifiutato
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Evita richieste duplicate
            $table->unique(['user_id', 'project_id', 'type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_requests');
    }
};