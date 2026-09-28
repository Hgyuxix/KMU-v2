<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_persyaratans', function (Blueprint $table) {
            $table->uuid('uuid')
                ->nullable()
                ->unique()
                ->after('id');
        });

        DB::table('dokumen_persyaratans')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(100, function ($documents): void {
                foreach ($documents as $document) {
                    $uuid = (string) Str::uuid();
                    $newPath = sprintf(
                        'dokumen/%d/%s.%s',
                        $document->permohonan_id,
                        $uuid,
                        preg_match('/^[a-zA-Z0-9]{1,10}$/', pathinfo($document->file_path, PATHINFO_EXTENSION))
                            ? strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION))
                            : 'bin'
                    );
                    $filePath = $document->file_path;

                    if (Storage::disk('local')->exists($document->file_path)) {
                        Storage::disk('local')->makeDirectory(dirname($newPath));
                        if (Storage::disk('local')->move($document->file_path, $newPath)) {
                            $filePath = $newPath;
                        }
                    }

                    DB::table('dokumen_persyaratans')
                        ->where('id', $document->id)
                        ->update([
                            'uuid' => $uuid,
                            'file_path' => $filePath,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('dokumen_persyaratans', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
