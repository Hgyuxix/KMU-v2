<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSurat extends Model
{
    protected $fillable = [
        'layanan_id',
        'judul',
        'isi_template',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }
}
