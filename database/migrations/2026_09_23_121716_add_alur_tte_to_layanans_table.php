<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanans', function (Blueprint $table) {
            $table->enum('alur_tte', [
                'tanpa_tte',
                'lurah_saja',
                'lurah_dan_camat',
            ])->default('lurah_saja')->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('layanans', function (Blueprint $table) {
            $table->dropColumn('alur_tte');
        });
    }
};
