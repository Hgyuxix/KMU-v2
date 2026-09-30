<?php

namespace Database\Seeders;

use App\Models\Kelurahan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';
    private const EMAIL_DOMAIN = '@kecmagelangutara.test';

    public function run(): void
    {
        $kelurahans = Kelurahan::all();

        foreach ($kelurahans as $kelurahan) {
            $slug = Str::slug($kelurahan->nama);
            $this->upsertUser(
                'kelurahan.' . $slug . self::EMAIL_DOMAIN,
                'FO ' . $kelurahan->nama,
                'fo',
                $kelurahan->id
            );
            $this->upsertUser(
                'kasi-pemerintahan.' . $slug . self::EMAIL_DOMAIN,
                'Kasi Pemerintahan ' . $kelurahan->nama,
                'kasi_pemerintahan',
                $kelurahan->id
            );
            $this->upsertUser(
                'lurah.' . $slug . self::EMAIL_DOMAIN,
                'Lurah ' . $kelurahan->nama,
                'lurah',
                $kelurahan->id
            );
        }

        $this->upsertUser(
            'kecamatan' . self::EMAIL_DOMAIN,
            'Camat Magelang Utara',
            'camat'
        );
        $this->upsertUser(
            'kasi-umum' . self::EMAIL_DOMAIN,
            'Kasi Umum Kecamatan Magelang Utara',
            'kasi_umum'
        );
        $this->upsertUser(
            'sekcam' . self::EMAIL_DOMAIN,
            'Sekretaris Kecamatan Magelang Utara',
            'sekcam'
        );

        $this->upsertUser(
            'admin' . self::EMAIL_DOMAIN,
            'Administrator KMU',
            'admin'
        );
    }

    private function upsertUser(
        string $email,
        string $name,
        string $role,
        ?int $kelurahanId = null
    ): void {
        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => $role,
                'kelurahan_id' => $kelurahanId,
                'is_active' => true,
            ]
        );
    }
}
