<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persyaratans', function (Blueprint $table) {
            $table->boolean('butuh_ttd_warga')->default(false)->after('wajib');
        });
    }

    public function down(): void
    {
        Schema::table('persyaratans', function (Blueprint $table) {
            $table->dropColumn('butuh_ttd_warga');
        });
    }
};
