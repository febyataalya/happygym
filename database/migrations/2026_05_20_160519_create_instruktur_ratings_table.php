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
        Schema::create('instruktur_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instruktur_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('booking_pt_id')->nullable();
            $table->integer('rating');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->foreign('instruktur_id')->references('instruktur_id')->on('instrukturs')->onDelete('cascade');
            $table->foreign('member_id')->references('member_id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instruktur_ratings');
    }
};
