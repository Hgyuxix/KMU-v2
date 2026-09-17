<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_persyaratans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('permohonan_id')
                ->constrained('permohonans')
                ->cascadeOnDelete();

            $table->foreignId('persyaratan_id')
                ->constrained('persyaratans')
                ->restrictOnDelete();

            $table->string('file_path');
            $table->string('file_original_name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_persyaratans');
    }
};
