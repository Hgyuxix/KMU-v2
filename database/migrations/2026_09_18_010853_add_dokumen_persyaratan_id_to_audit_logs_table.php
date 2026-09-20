<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('dokumen_persyaratan_id')
                ->nullable()
                ->after('permohonan_id')
                ->constrained('dokumen_persyaratans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['dokumen_persyaratan_id']);
            $table->dropColumn('dokumen_persyaratan_id');
        });
    }
};
