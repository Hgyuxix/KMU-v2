<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add support for uploading more than the minimum number of files
     * for a single requirement.
     */
    public function up(): void
    {
        if (Schema::hasTable('persyaratans') && ! Schema::hasColumn('persyaratans', 'multiple')) {
            Schema::table('persyaratans', function (Blueprint $table) {
                $table->boolean('multiple')->default(false);
            });
        }
    }

    /**
     * Remove the multiple-upload flag when rolling this migration back.
     */
    public function down(): void
    {
        if (Schema::hasTable('persyaratans') && Schema::hasColumn('persyaratans', 'multiple')) {
            Schema::table('persyaratans', function (Blueprint $table) {
                $table->dropColumn('multiple');
            });
        }
    }
};
