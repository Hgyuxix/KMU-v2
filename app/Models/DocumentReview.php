<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReview extends Model
{
    public const STATUS_BELUM_DICEK = 'belum_dicek';
    public const STATUS_SESUAI = 'sesuai';
    public const STATUS_TIDAK_SESUAI = 'tidak_sesuai';

    public const STAGE_KASI_PEMERINTAHAN = 'kasi_pemerintahan_review';
    public const STAGE_LURAH = 'lurah_review';
    public const STAGE_KASI_UMUM = 'kasi_umum_review';
    public const STAGE_SEKCAM = 'sekcam_review';
    public const STAGE_CAMAT = 'camat_review';

    protected $fillable = [
        'dokumen_persyaratan_id',
        'stage',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function dokumenPersyaratan(): BelongsTo
    {
        return $this->belongsTo(DokumenPersyaratan::class, 'dokumen_persyaratan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForStage(Builder $query, string $stage): Builder
    {
        return $query->where('stage', $stage);
    }

    public function scopeForDocument(Builder $query, int $documentId): Builder
    {
        return $query->where('dokumen_persyaratan_id', $documentId);
    }
}
