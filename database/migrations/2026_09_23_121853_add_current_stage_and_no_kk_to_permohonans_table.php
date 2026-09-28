<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->enum('current_stage', [
                'fo_input',
                'kasi_pemerintahan_review',
                'lurah_review',
                'kasi_umum_review',
                'sekcam_review',
                'camat_review',
                'selesai',
            ])->default('fo_input')->after('status');

            $table->text('no_kk')->nullable()->after('nik');
        });
    }

    public function down(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->dropColumn([
                'current_stage',
                'no_kk',
            ]);
        });
    }
};
