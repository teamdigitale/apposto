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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('desk_id');
            $table->foreign('desk_id')
                ->references('id')
                ->on('desks')->onDelete('cascade');

            $table->dateTime('from_date')->nullable();
            $table->dateTime('to_date')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->integer('status')->default(0);
            //esistono 3 status nella prenotazione
            // 0 confermata
            // 1 cancellata
            // 2 "rubata da qualche utente con profilo superiore"
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
