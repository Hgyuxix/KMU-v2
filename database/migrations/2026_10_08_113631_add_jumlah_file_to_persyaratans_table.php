<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persyaratans') || Schema::hasColumn('persyaratans', 'jumlah_file')) {
            return;
        }

        Schema::table('persyaratans', function (Blueprint $table) {
            $table->unsignedTinyInteger('jumlah_file')->default(1);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('persyaratans') || ! Schema::hasColumn('persyaratans', 'jumlah_file')) {
            return;
        }

        Schema::table('persyaratans', function (Blueprint $table) {
            $table->dropColumn('jumlah_file');
        });
    }
};
