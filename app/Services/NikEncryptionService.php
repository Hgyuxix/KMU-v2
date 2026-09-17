<?php

namespace App\Services;

use RuntimeException;

class NikEncryptionService
{
    private string $cipher = 'aes-256-gcm';

    private function getKey(): string
    {
        $key = config('app.nik_encryption_key');

        if (!$key) {
            throw new RuntimeException(
                'NIK_ENCRYPTION_KEY belum dikonfigurasi.'
            );
        }

        $decodedKey = base64_decode($key, true);

        if ($decodedKey === false || strlen($decodedKey) !== 32) {
            throw new RuntimeException(
                'NIK_ENCRYPTION_KEY harus berupa Base64 dari 32 byte.'
            );
        }

        return $decodedKey;
    }

    public function encrypt(string $nik): string
    {
        $iv = random_bytes(12);
        $tag = '';

        $encrypted = openssl_encrypt(
            $nik,
            $this->cipher,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new RuntimeException('Gagal mengenkripsi NIK.');
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $encryptedNik): string
    {
        $data = base64_decode($encryptedNik, true);

        if ($data === false || strlen($data) < 28) {
            throw new RuntimeException(
                'Data NIK terenkripsi tidak valid.'
            );
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new RuntimeException(
                'Gagal mendekripsi NIK.'
            );
        }

        return $decrypted;
    }
}
