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
        'current_stage',
        'no_kk',
        'kasi_pemerintahan_oleh',
        'kasi_pemerintahan_at',
        'lurah_oleh',
        'lurah_at',
        'kasi_umum_oleh',
        'kasi_umum_at',
        'sekcam_oleh',
        'sekcam_at',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'data_surat' => 'array',
        'diproses_at' => 'datetime',
        'selesai_at' => 'datetime',
        'kasi_pemerintahan_at' => 'datetime',
        'lurah_at' => 'datetime',
        'kasi_umum_at' => 'datetime',
        'sekcam_at' => 'datetime',
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
