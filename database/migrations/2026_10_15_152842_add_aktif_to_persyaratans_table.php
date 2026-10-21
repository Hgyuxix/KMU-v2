<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('persyaratans', 'aktif')) {
            Schema::table('persyaratans', function (Blueprint $table) {
                $table->boolean('aktif')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('persyaratans', 'aktif')) {
            Schema::table('persyaratans', function (Blueprint $table) {
                $table->dropColumn('aktif');
            });
        }
    }
};
