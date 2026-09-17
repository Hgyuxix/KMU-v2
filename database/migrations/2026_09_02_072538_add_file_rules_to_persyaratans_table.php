<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persyaratans', function (Blueprint $table) {
            $table->string('tipe_file')->default('pdf,jpg,jpeg,png');
            $table->unsignedInteger('maks_size')->default(5120);
        });
    }

    public function down(): void
    {
        Schema::table('persyaratans', function (Blueprint $table) {
            $table->dropColumn([
                'tipe_file',
                'maks_size',
            ]);
        });
    }
};
