<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('stage', 50)->nullable()->after('aksi');
            $table->index(['permohonan_id', 'aksi', 'id'], 'audit_logs_revision_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_revision_lookup_idx');
            $table->dropColumn('stage');
        });
    }
};
