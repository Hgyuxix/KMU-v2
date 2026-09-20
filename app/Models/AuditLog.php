<?php

namespace App\Models;

use App\Models\DokumenPersyaratan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'permohonan_id',
        'dokumen_persyaratan_id',
        'user_id',
        'aksi',
        'status_sebelum',
        'status_sesudah',
        'catatan',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dokumenPersyaratan(): BelongsTo
    {
        return $this->belongsTo(DokumenPersyaratan::class);
    }
}
