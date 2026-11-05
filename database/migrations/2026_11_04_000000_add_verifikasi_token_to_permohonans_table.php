<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->string('verifikasi_token', 32)->nullable()->unique();
        });

        DB::table('permohonans')
            ->whereNotNull('nomor_surat')
            ->where('nomor_surat', '<>', '')
            ->whereNull('verifikasi_token')
            ->orderBy('id')
            ->chunkById(100, function ($permohonans): void {
                foreach ($permohonans as $permohonan) {
                    do {
                        $token = Str::random(24);
                    } while (DB::table('permohonans')
                        ->where('verifikasi_token', $token)
                        ->exists());

                    DB::table('permohonans')
                        ->where('id', $permohonan->id)
                        ->whereNull('verifikasi_token')
                        ->update(['verifikasi_token' => $token]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->dropUnique(['verifikasi_token']);
            $table->dropColumn('verifikasi_token');
        });
    }
};
