<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'kelurahan')->update(['role' => 'fo']);
        DB::table('users')->where('role', 'kecamatan')->update(['role' => 'camat']);

        DB::table('permohonans')
            ->where('status', 'diajukan')
            ->where('current_stage', 'fo_input')
            ->update(['current_stage' => 'kasi_pemerintahan_review']);

        DB::table('permohonans')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->where('current_stage', 'fo_input')
            ->update(['current_stage' => 'selesai']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('role', ['fo', 'kasi_pemerintahan', 'lurah'])
            ->update(['role' => 'kelurahan']);

        DB::table('users')
            ->whereIn('role', ['kasi_umum', 'sekcam', 'camat'])
            ->update(['role' => 'kecamatan']);

        DB::table('permohonans')
            ->where('status', 'diajukan')
            ->where('current_stage', 'kasi_pemerintahan_review')
            ->update(['current_stage' => 'fo_input']);

        DB::table('permohonans')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->where('current_stage', 'selesai')
            ->update(['current_stage' => 'fo_input']);
    }
};
