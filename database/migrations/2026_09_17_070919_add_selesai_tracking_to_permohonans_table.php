<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->foreignId('selesai_oleh')
                ->nullable()
                ->after('diproses_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('selesai_at')
                ->nullable()
                ->after('selesai_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('permohonans', function (Blueprint $table) {
            $table->dropForeign(['selesai_oleh']);
            $table->dropColumn([
                'selesai_oleh',
                'selesai_at',
            ]);
        });
    }
};
