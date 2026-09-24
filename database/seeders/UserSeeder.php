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

            /*
             * Migrasi akun lama:
             * role kelurahan -> fo
             */
            $legacyEmail = 'kelurahan.' . $slug . self::EMAIL_DOMAIN;

            User::updateOrCreate(
                ['email' => $legacyEmail],
                [
                    'name' => 'FO ' . $kelurahan->nama,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'role' => 'fo',
                    'kelurahan_id' => $kelurahan->id,
                    'is_active' => true,
                ]
            );

            /*
             * Kasi Pemerintahan per kelurahan
             */
            $kasiPemEmail =
                'kasi.pemerintahan.' . $slug . self::EMAIL_DOMAIN;

            User::updateOrCreate(
                ['email' => $kasiPemEmail],
                [
                    'name' => 'Kasi Pemerintahan ' . $kelurahan->nama,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'role' => 'kasi_pemerintahan',
                    'kelurahan_id' => $kelurahan->id,
                    'is_active' => true,
                ]
            );

            /*
             * Lurah per kelurahan
             */
            $lurahEmail =
                'lurah.' . $slug . self::EMAIL_DOMAIN;

            User::updateOrCreate(
                ['email' => $lurahEmail],
                [
                    'name' => 'Lurah ' . $kelurahan->nama,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'role' => 'lurah',
                    'kelurahan_id' => $kelurahan->id,
                    'is_active' => true,
                ]
            );
        }

        /*
         * Migrasi akun kecamatan lama
         * menjadi Kasi Umum.
         */
        User::updateOrCreate(
            ['email' => 'kecamatan@kecmagelangutara.test'],
            [
                'name' => 'Kasi Umum Kecamatan Magelang Utara',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => 'kasi_umum',
                'kelurahan_id' => null,
                'is_active' => true,
            ]
        );

        /*
         * Sekcam - shared seluruh kecamatan
         */
        User::updateOrCreate(
            ['email' => 'sekcam@kecmagelangutara.test'],
            [
                'name' => 'Sekretaris Kecamatan Magelang Utara',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => 'sekcam',
                'kelurahan_id' => null,
                'is_active' => true,
            ]
        );

        /*
         * Camat - shared seluruh kecamatan
         */
        User::updateOrCreate(
            ['email' => 'camat@kecmagelangutara.test'],
            [
                'name' => 'Camat Magelang Utara',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => 'camat',
                'kelurahan_id' => null,
                'is_active' => true,
            ]
        );

        /*
         * Administrator
         */
        User::updateOrCreate(
            ['email' => 'admin@kecmagelangutara.test'],
            [
                'name' => 'Administrator KMU',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => 'admin',
                'kelurahan_id' => null,
                'is_active' => true,
            ]
        );
    }
}
