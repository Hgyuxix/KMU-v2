<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kelurahan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $kelurahans = Kelurahan::all();

        foreach ($kelurahans as $kelurahan) {
            $email = 'kelurahan.' . \Illuminate\Support\Str::slug($kelurahan->nama) . '@kecmagelangutara.test';

            User::updateOrCreate(['email' => $email], [
                'name' => 'Staf Kelurahan ' . $kelurahan->nama,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'kelurahan',
                'kelurahan_id' => $kelurahan->id,
            ]);
        }

        User::updateOrCreate(['email' => 'kecamatan@kecmagelangutara.test'], [
            'name' => 'Staf Kecamatan Magelang Utara',
            'email' => 'kecamatan@kecmagelangutara.test',
            'password' => Hash::make('password'),
            'role' => 'kecamatan',
            'kelurahan_id' => null,
        ]);

        User::updateOrCreate(['email' => 'admin@kecmagelangutara.test'], [
            'name' => 'Administrator KMU',
            'email' => 'admin@kecmagelangutara.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'kelurahan_id' => null,
            'is_active' => true,
        ]);
    }
}
