<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permohonan extends Model
{
    protected $fillable = [
        'layanan_id',
        'kelurahan_id',
        'dibuat_oleh',
        'nama_lengkap',
        'tanggal_lahir',
        'nik',
        'rt',
        'rw',
        'data_surat',
        'status',
        'diproses_oleh',
        'diproses_at',
        'alasan_penolakan',
        'nomor_surat',
        'catatan_revisi',
        'selesai_oleh',
        'selesai_at',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'data_surat' => 'array',
        'diproses_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function penyelesai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selesai_oleh');
    }

    public function dokumenPersyaratans(): HasMany
    {
        return $this->hasMany(DokumenPersyaratan::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

}
