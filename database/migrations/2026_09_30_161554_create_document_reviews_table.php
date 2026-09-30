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
        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dokumen_persyaratan_id')
                ->constrained('dokumen_persyaratans')
                ->cascadeOnDelete();

            $table->string('stage');

            $table->string('status')
                ->default('belum_dicek');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'dokumen_persyaratan_id',
                'stage'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_reviews');
    }
};
