<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const STAGES = [
        'fo_input',
        'fo_ttd_warga',
        'kasi_pemerintahan_review',
        'lurah_review',
        'kasi_umum_review',
        'sekcam_review',
        'camat_review',
        'fo_finalisasi',
        'selesai',
    ];

    private const PREVIOUS_STAGES = [
        'fo_input',
        'kasi_pemerintahan_review',
        'lurah_review',
        'kasi_umum_review',
        'sekcam_review',
        'camat_review',
        'fo_finalisasi',
        'selesai',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE permohonans MODIFY current_stage ENUM('" . implode("','", self::STAGES) . "') NOT NULL DEFAULT 'fo_input'"
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('permohonans')->where('current_stage', 'fo_ttd_warga')->exists()) {
            throw new RuntimeException(
                'Cannot remove fo_ttd_warga while a permohonan is in that stage.'
            );
        }

        DB::statement(
            "ALTER TABLE permohonans MODIFY current_stage ENUM('" . implode("','", self::PREVIOUS_STAGES) . "') NOT NULL DEFAULT 'fo_input'"
        );
    }
};
