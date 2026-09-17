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
        Schema::create('permohonans', function (Blueprint $table) {
            $table->id();

            // Layanan yang dipilih warga
            $table->foreignId('layanan_id')
                ->constrained('layanans')
                ->restrictOnDelete();

            // Data pemohon
            $table->string('nama_lengkap');
            $table->date('tanggal_lahir');
            $table->text('nik');
            $table->string('rt', 3);
            $table->string('rw', 3);

            // Status proses permohonan
            $table->string('status')->default('diajukan');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permohonans');
    }
};
