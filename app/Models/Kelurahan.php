<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kelurahan extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama',
        'kode_wilayah',
        'nama_lurah',
        'nip_lurah',
        'nama_sekretaris',
        'nip_sekretaris',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permohonans(): HasMany {
        return $this->hasMany(Permohonan::class);
    }
}
