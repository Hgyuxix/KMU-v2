<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {

            $table->foreignId('kasi_pemerintahan_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('kasi_pemerintahan_at')
                ->nullable();

            $table->foreignId('lurah_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('lurah_at')
                ->nullable();

            $table->foreignId('kasi_umum_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('kasi_umum_at')
                ->nullable();

            $table->foreignId('sekcam_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('sekcam_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->dropForeign([
                'kasi_pemerintahan_oleh',
            ]);

            $table->dropForeign([
                'lurah_oleh',
            ]);

            $table->dropForeign([
                'kasi_umum_oleh',
            ]);

            $table->dropForeign([
                'sekcam_oleh',
            ]);

            $table->dropColumn([
                'kasi_pemerintahan_oleh',
                'kasi_pemerintahan_at',
                'lurah_oleh',
                'lurah_at',
                'kasi_umum_oleh',
                'kasi_umum_at',
                'sekcam_oleh',
                'sekcam_at',
            ]);
        });
    }
};
