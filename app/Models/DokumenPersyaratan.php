<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPersyaratan extends Model
{
    protected $fillable = [
        'permohonan_id',
        'persyaratan_id',
        'file_path',
        'file_original_name',
        'status',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function persyaratan(): BelongsTo
    {
        return $this->belongsTo(Persyaratan::class);
    }
}
