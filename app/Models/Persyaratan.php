<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Persyaratan extends Model
{
    protected $fillable = [
        'layanan_id',
        'nama',
        'wajib',
        'butuh_ttd_warga',
        'tipe_file',
        'maks_size',
    ];

    protected $casts = [
        'wajib' => 'boolean',
        'butuh_ttd_warga' => 'boolean',
        'maks_size' => 'integer',
    ];

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }
}
