<?php

namespace App\Models;

use App\Models\User;
use App\Models\DokumenPersyaratan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReview extends Model
{
    protected $fillable = [
        'dokumen_persyaratan_id',
        'stage',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function dokumenPersyaratan(): BelongsTo
    {
        return $this->belongsTo(DokumenPersyaratan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
