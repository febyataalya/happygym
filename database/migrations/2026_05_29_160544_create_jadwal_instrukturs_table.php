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
        Schema::create('jadwal_instrukturs', function (Blueprint $table) {
    $table->id();

        $table->unsignedBigInteger('instruktur_id');

        $table->date('tanggal');

        $table->enum('status', [
            'available',
            'full'
        ])->default('available');

        $table->timestamps();

        $table->foreign('instruktur_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_instrukturs');
    }
};
