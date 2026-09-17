<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persyaratans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('layanan_id')
                ->constrained('layanans')
                ->cascadeOnDelete();

            $table->text('nama');

            $table->boolean('wajib')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persyaratans');
    }
};
