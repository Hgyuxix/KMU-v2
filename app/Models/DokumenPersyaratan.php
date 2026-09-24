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
        'uuid',
        'jenis',
        'menggantikan_id',
        'file_hash',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function persyaratan(): BelongsTo
    {
        return $this->belongsTo(Persyaratan::class);
    }

    public function dokumenPengganti()
    {
        return $this->hasOne(
            self::class,
            'menggantikan_id'
        );
    }

    public function dokumenYangDigantikan()
    {
        return $this->belongsTo(
            self::class,
            'menggantikan_id'
        );
    }
}
