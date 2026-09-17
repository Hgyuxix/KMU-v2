<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_surats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('layanan_id')
                ->constrained('layanans')
                ->cascadeOnDelete();

            $table->string('judul');
            $table->text('isi_template');

            $table->boolean('aktif')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_surats');
    }
};
