<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = [
        'awal',
        'ttd_warga',
        'arsip_surat',
    ];

    private const PREVIOUS_TYPES = [
        'awal',
        'ttd_warga',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('dokumen_persyaratans', function (Blueprint $table) {
                $table->enum('jenis', self::TYPES)->default('awal')->change();
            });

            return;
        }

        if ($driver !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE dokumen_persyaratans MODIFY jenis ENUM('".implode("','", self::TYPES)."') NOT NULL DEFAULT 'awal'"
        );
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'mysql' && $driver !== 'sqlite') {
            return;
        }

        if (DB::table('dokumen_persyaratans')->where('jenis', 'arsip_surat')->exists()) {
            throw new RuntimeException(
                'Cannot remove arsip_surat while archived letter documents exist.'
            );
        }

        if ($driver === 'sqlite') {
            Schema::table('dokumen_persyaratans', function (Blueprint $table) {
                $table->enum('jenis', self::PREVIOUS_TYPES)->default('awal')->change();
            });

            return;
        }

        DB::statement(
            "ALTER TABLE dokumen_persyaratans MODIFY jenis ENUM('".implode("','", self::PREVIOUS_TYPES)."') NOT NULL DEFAULT 'awal'"
        );
    }
};
