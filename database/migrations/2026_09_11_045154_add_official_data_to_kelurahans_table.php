<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelurahans', function (Blueprint $table) {
            $table->string('nama_lurah')->nullable()->after('nama');
            $table->string('nip_lurah')->nullable()->after('nama_lurah');

            $table->string('nama_sekretaris')->nullable()->after('nip_lurah');
            $table->string('nip_sekretaris')->nullable()->after('nama_sekretaris');
        });
    }

    public function down(): void
    {
        Schema::table('kelurahans', function (Blueprint $table) {
            $table->dropColumn([
                'nama_lurah',
                'nip_lurah',
                'nama_sekretaris',
                'nip_sekretaris',
            ]);
        });
    }
};
