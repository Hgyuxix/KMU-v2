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
        'jumlah_file',
        'multiple',
        'aktif',
    ];

    protected $casts = [
        'jumlah_file' => 'integer',
        'multiple' => 'boolean',
        'aktif' => 'boolean',
        'wajib' => 'boolean',
        'butuh_ttd_warga' => 'boolean',
        'maks_size' => 'integer',
    ];

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }
}
