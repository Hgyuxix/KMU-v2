<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_persyaratans', function (Blueprint $table) {
            $table->enum('jenis', [
                'awal',
                'ttd_warga',
            ])->default('awal')->after('persyaratan_id');

            $table->foreignId('menggantikan_id')
                ->nullable()
                ->constrained('dokumen_persyaratans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_persyaratans', function (Blueprint $table) {
            $table->dropForeign([
                'menggantikan_id',
            ]);

            $table->dropColumn([
                'jenis',
                'menggantikan_id',
            ]);
        });
    }
};
