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
    Schema::table('permohonans', function (Blueprint $table) {
        $table->foreignId('kelurahan_id')->nullable()->after('layanan_id')
            ->constrained('kelurahans')->restrictOnDelete();
        $table->foreignId('dibuat_oleh')->nullable()->after('kelurahan_id')
            ->constrained('users')->nullOnDelete();
        $table->foreignId('diproses_oleh')->nullable()->after('status')
            ->constrained('users')->nullOnDelete();
        $table->timestamp('diproses_at')->nullable()->after('diproses_oleh');
        $table->text('alasan_penolakan')->nullable()->after('diproses_at');
        $table->string('nomor_surat')->nullable()->unique()->after('alasan_penolakan');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            //
        });
    }
};
