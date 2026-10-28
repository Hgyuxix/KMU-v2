<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\TemplateSurat;
use Illuminate\Database\Seeder;

class TemplateSuratSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Sumber redaksi: form client pada Form Surat KMU(1).zip.
         * ID 8, 9, 12, 13 tidak dimasukkan karena form client belum tersedia.
         *
         * Aturan isi template (mengikuti preview.blade.php):
         * - Judul dan "Nomor:" dicetak preview dari kolom `judul` dan
         *   nomor_surat, jadi TIDAK ditulis lagi di isi_template.
         * - Blok TTE Camat dicetak preview di bawah isi surat, jadi nama
         *   dan NIP Camat tidak ditulis di isi_template.
         * - Baris "Label : isi" tampil sebagai tabel. Baris diakhiri ":"
         *   tampil sebagai paragraf. Spasi di awal/tengah baris tidak
         *   dipertahankan, jadi tanda tangan ditulis bertumpuk, bukan
         *   berkolom.
         * - Tidak ada "a.n." / Sekretaris. Penandatangan langsung Lurah
         *   (data dari kelurahan yang menangani) dan Camat.
         */
        $templates = [

            // KIP (SK/Pengantar). Data kelurahan dan Lurah otomatis.
            1 => [
                'judul' => 'SURAT KETERANGAN TIDAK MAMPU',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini, menerangkan dengan sesungguhnya bahwa :

                Nama Lengkap : {{ nama_lengkap }} / {{ jenis_kelamin }}
                Tempat&Tanggal Lahir / Status : {{ tempat_lahir }}, {{ tanggal_lahir }} / {{ status }}
                Kewarganegaraan & Agama : Indonesia / {{ agama }}
                Pekerjaan : {{ pekerjaan }}
                Tempat Tinggal : {{ alamat }}

                Kota : {{ kota }}
                Bukti Diri : KK/KTP No. {{ nik }}
                Keperluan : Kelengkapan Administrasi Permohonan Pengajuan KIP
                Berlaku Mulai : {{ berlaku_mulai }} s/d {{ berlaku_sampai }}
                Keterangan lain-lain : Yang bersangkutan benar warga Kelurahan {{ nama_kelurahan }} Kecamatan Magelang Utara Kota Magelang dan kurang mampu

                Demikian untuk menjadikan maklum bagi yang berkepentingan.

                No :
                Tanggal :

                Magelang, {{ tanggal }}

                Pemohon
                {{ nama_lengkap }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            //Pernyataan belum menikah. Data kelurahan dan Lurah otomatis.
            2 => [
                'judul' => 'SURAT PERNYATAAN BELUM MENIKAH',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah:

                Nama : {{ nama_lengkap }}
                Tempat dan tanggal lahir : {{ tempat_lahir }}, {{ tanggal_lahir }}
                Agama : {{ agama }}
                Pekerjaan : {{ pekerjaan }}
                Alamat : {{ alamat }}
                Kec. Magelang Utara Kota Magelang

                Menyatakan bahwa sampai saat ini saya belum pernah menikah ({{ status_pernyataan }}) dengan siapapun.

                Demikian surat pernyataan ini saya buat dengan sebenarnya, dan jika pada di kemudian hari ada pernyataan saya yang tidak benar maka saya siap di proses secara hukum dan tidak akan melibatkan pihak kelurahan {{ nama_kelurahan }}.

                Magelang, {{ tanggal }}

                [[DECLARANT_SIGNATURE_BEGIN]]
                Yang Membuat Pernyataan
                Materai Rp.10.000,-
                [[RUANG_TTD_PERNYATAAN]]
                {{ nama_lengkap }}
                [[DECLARANT_SIGNATURE_END]]

                Saksi – Saksi :

                [[WITNESS_GRID_BEGIN]]
                1. {{ saksi_1 }}
                2. {{ saksi_2 }}
                3. {{ saksi_3 }}
                4. {{ saksi_4 }}
                [[WITNESS_GRID_END]]
                TEXT
            ],

            //Keterangan domisili. Data kelurahan dan Lurah otomatis.
            3 => [
                'judul' => 'SURAT KETERANGAN DOMISILI',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini :

                Nama : {{ nama_lurah }}
                NIP : {{ nip_lurah }}
                Jabatan : Lurah {{ nama_kelurahan }}

                Dengan ini menerangkan bahwa "{{ nama_lengkap }}" yang beralamat di {{ alamat_domisili }} RT {{ rt }} RW {{ rw }} Kelurahan {{ nama_kelurahan }} Kecamatan Magelang Utara Kota Magelang benar berdomisili di alamat tersebut dan masuk wilayah Kelurahan {{ nama_kelurahan }}, Kecamatan Magelang Utara, Kota Magelang.

                Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.

                Magelang, {{ tanggal }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            // Perceraian
            4 => [
                'judul' => 'SURAT PENGANTAR CERAI',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini, menerangkan dengan sesungguhnya bahwa :

                Nama Lengkap : {{ nama_lengkap }} / {{ jenis_kelamin }}
                Tempat&Tanggal Lahir / Status : {{ tempat_lahir }}, {{ tanggal_lahir }} / {{ status }}
                Kewarganegaraan & Agama : Indonesia / {{ agama }}
                Pekerjaan : {{ pekerjaan }}
                Tempat Tinggal : {{ alamat }}

                Kota : {{ kota }}
                Bukti Diri : KK/KTP No. {{ nik }}
                Keperluan : Kelengkapan Administrasi Permohonan Pengajuan Perceraian an. {{ nama_lengkap }} dan {{ nama_pasangan }} ke Pengadilan Agama Kota Magelang
                Berlaku Mulai : {{ berlaku_mulai }} s/d {{ berlaku_sampai }}
                Keterangan lain-lain : Yang bersangkutan benar warga Kelurahan {{ nama_kelurahan }} Kecamatan Magelang Utara Kota Magelang

                Demikian untuk menjadikan maklum bagi yang berkepentingan.

                No :
                Tanggal :

                Magelang, {{ tanggal }}

                Pemohon
                {{ nama_lengkap }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            // Santunan kematian (Alm. bertempat tinggal di ...)
            5 => [
                'judul' => 'SURAT PENGANTAR PENGAJUAN SANTUNAN KEMATIAN',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini, menerangkan dengan sesungguhnya bahwa :

                Nama Lengkap : {{ nama_lengkap }} / {{ jenis_kelamin }}
                Tempat&Tanggal Lahir / Status : {{ tempat_lahir }}, {{ tanggal_lahir }} / {{ status }}
                Kewarganegaraan & Agama : Indonesia / {{ agama }}
                Pekerjaan : {{ pekerjaan }}
                Tempat Tinggal : {{ alamat }}

                Kota : {{ kota }}
                Bukti Diri : KK/KTP No. {{ nik }}
                Keperluan : Persyaratan Pengajuan Usulan Permohonan Bantuan Santunan Kematian an. Alm. {{ nama_almarhum }}
                Berlaku Mulai : {{ berlaku_mulai }} s/d {{ berlaku_sampai }}
                Keterangan lain-lain : Bahwa Alm. {{ nama_almarhum }} Benar-benar bertempat tinggal di {{ alamat_almarhum }} RT {{ rt_almarhum }} RW {{ rw_almarhum }} Kelurahan {{ nama_kelurahan }} Kecamatan Magelang Utara Kota Magelang

                Demikian untuk menjadikan maklum bagi yang berkepentingan.

                No :
                Tanggal :

                Magelang, {{ tanggal }}

                Pemohon
                {{ nama_lengkap }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            // Pensiun (surat kuasa).
            6 => [
                'judul' => 'SURAT KETERANGAN KUASA PENGAMBILAN PENSIUN',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini, menerangkan dengan sesungguhnya bahwa :

                Nama Lengkap : {{ nama_penerima_kuasa }} / {{ jenis_kelamin }}
                Tempat&Tanggal Lahir / Status : {{ tempat_lahir }}, {{ tanggal_lahir }} / {{ status }}
                Kewarganegaraan & Agama : Indonesia / {{ agama }}
                Pekerjaan : {{ pekerjaan }}
                Tempat Tinggal : {{ alamat }}

                Kota : {{ kota }}
                Bukti Diri : KK/KTP No. {{ nik }}
                Keperluan : Surat kuasa pengambilan pensiun an. {{ nama_pemberi_kuasa }}
                Berlaku Mulai : {{ berlaku_mulai }} s/d {{ berlaku_sampai }}
                Keterangan lain-lain : Yang bersangkutan benar warga Kelurahan Kramat Selatan Kecamatan Magelang Utara Kota Magelang, an. {{ nama_pemberi_kuasa }} benar masih hidup dan sudah lanjut usia

                Demikian untuk menjadikan maklum bagi yang berkepentingan.

                No :
                Tanggal :

                Magelang, {{ tanggal }}

                Pemohon
                {{ nama_lengkap }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            // Penghasilan orang tua. Data kelurahan dan Lurah otomatis.
            7 => [
                'judul' => 'SURAT KETERANGAN PENGHASILAN ORANG TUA',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini:

                Nama : {{ nama_lengkap }}
                NIK : {{ nik }}
                Tempat, Tanggal Lahir : {{ tempat_lahir_orang_tua }}, {{ tanggal_lahir }}
                Pekerjaan : {{ pekerjaan }}
                Agama : {{ agama_orang_tua }}
                Alamat : {{ alamat_orang_tua }}

                Adalah Wali dari :

                Nama : {{ nama_anak }}
                Jenis Kelamin : {{ jenis_kelamin_anak }}
                Alamat : {{ alamat_anak }}

                adalah benar berstatus sebagai warga kami yang bekerja sebagai {{ pekerjaan }} dan berpenghasilan per bulan rata-rata : {{ penghasilan_per_bulan }}

                Demikian surat keterangan ini dibuat dengan sebenar-benarnya dan data yang diisikan sesuai kondisi yang sesungguhnya berdasarkan keterangan dari yang bersangkutan untuk keperluan {{ keperluan }}.

                Apabila di kemudian hari ditemukan perbedaan data, maka yang bersangkutan sanggup bertanggung jawab tanpa melibatkan pihak manapun.

                Magelang, {{ tanggal }}

                Yang Membuat Pernyataan

                Materai 10000

                {{ nama_lengkap }}

                Tercatat di registrasi Kelurahan
                Nomor : ....
                Tanggal : ....
                TEXT
            ],

            //surat pernyataan beda nama.
            10 => [
                'judul' => 'SURAT PERNYATAAN BEDA NAMA',
                'isi_template' => <<<'TEXT'
                Saya yang bertanda tangan di bawah ini:

                Nama : {{ nama_lengkap }}
                NIK : {{ nik }}
                Alamat : {{ alamat }}
                Kec. Magelang Utara Kota Magelang
                Pekerjaan : {{ pekerjaan }}

                Menerangkan bahwa terdapat perbedaan “Nama” pada :

                KTP : {{ nama_ktp }}
                Nomor : {{ nomor_ktp }}

                KK : {{ nama_kk }}
                Nomor : {{ nomor_kk }}

                {{ dokumen_perbedaan }} : {{ nama_sertifikat }}
                Nomor : {{ nomor_sertifikat }}

                Nama yang benar : {{ nama_yang_benar }}

                Data tersebut diatas adalah benar nama orang yang sama (satu orang).

                Demikian surat pernyataan ini dibuat untuk dipergunakan sebagaimana mestinya dan harap menjadikan maklum.

                Apabila di kemudian hari ternyata saya melanggar atau pernyataan ini tidak benar maka saya siap bertanggung jawab sesuai hukum yang berlaku tanpa melibatkan pihak manapun.

                Magelang, {{ tanggal }}

                Yang membuat pernyataan

                Materai 10.000

                (....................................)

                Mengetahui

                Ketua RT
                (..................................)

                Ketua RW
                (..................................)

                Lurah {{ nama_kelurahan }}
                {{ nama_lurah }}
                TEXT
            ],

            // KUR
            11 => [
                'judul' => 'SURAT KETERANGAN USAHA',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan di bawah ini, menerangkan dengan sesungguhnya bahwa :

                Nama Lengkap : {{ nama_lengkap }} / {{ jenis_kelamin }}
                Tempat&Tanggal Lahir / Status : {{ tempat_lahir }}, {{ tanggal_lahir }} / {{ status }}
                Kewarganegaraan & Agama : Indonesia / {{ agama }}
                Pekerjaan : {{ pekerjaan }}
                Tempat Tinggal : {{ alamat }}

                Kota : {{ kota }}
                Bukti Diri : KK/KTP No. {{ nik }}
                Keperluan : Kelengkapan Administrasi Permohonan Pengajuan KUR
                Berlaku Mulai : {{ berlaku_mulai }} s/d {{ berlaku_sampai }}
                Keterangan lain-lain : Bahwa ybs mempunyai usaha "{{ nama_usaha }}" kurang lebih selama {{ lama_usaha }} di wilayah RT {{ rt_usaha }} RW {{ rw_usaha }} Kel. {{ nama_kelurahan }} Kec. Magelang Utara Kota Magelang menurut surat pengantar RT RW No. {{ nomor_surat_rt_rw }} Tanggal {{ tanggal_surat_rt_rw }}

                Demikian untuk menjadikan maklum bagi yang berkepentingan.

                No :
                Tanggal :

                Magelang, {{ tanggal }}

                Pemohon
                {{ nama_lengkap }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            //Surat Keterangan Ahli Waris. Data kelurahan dan Lurah otomatis.
            14 => [
                'judul' => 'SURAT KETERANGAN AHLI WARIS',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan dibawah ini :

                Nama : {{ nama_lurah }}
                Jabatan : Lurah {{ nama_kelurahan }}

                Dengan ini menerangkan bahwa :

                Nama : {{ nama_almarhum }}
                Jenis Kelamin : {{ jenis_kelamin_almarhum }}
                Alamat : {{ alamat_almarhum }}
                Kec. Magelang Utara Kota Magelang

                Nama tersebut diatas adalah benar telah meninggal dunia pada tanggal {{ tanggal_meninggal }} di {{ tempat_meninggal }} menurut Akta Kematian Nomor {{ nomor_akta_kematian }} Tanggal {{ tanggal_akta_kematian }} yang dikeluarkan oleh Dinas Kependudukan dan Pencatatan Sipil Kota Magelang, dan sebagai ahli warisnya adalah :

                {{ ahli_waris }}

                Demikian Surat Keterangan Ahli Waris ini kami berikan untuk dapat dipergunakan seperlunya.

                Para Ahli Waris :
                Meterai 10.000

                {{ ahli_waris_ttd }}

                Magelang, {{ tanggal }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            // Wali Nikah
            15 => [
                'judul' => 'SURAT KETERANGAN WALI NIKAH',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan dibawah ini Lurah {{ nama_kelurahan }} Kecamatan Magelang Utara Kota Magelang menerangkan dengan sesungguhnya bahwa:

                Nama : {{ nama_wali }}
                Tempat/Tgl lahir : {{ tempat_lahir_wali }}, {{ tanggal_lahir_wali }}
                Agama : {{ agama_wali }}
                Pekerjaan : {{ pekerjaan_wali }}
                Tempat tinggal : {{ tempat_tinggal_wali }}

                Adalah WALI NIKAH dari seorang perempuan:

                Nama : {{ nama_calon_mempelai }}
                Tempat/Tgl lahir : {{ tempat_lahir_calon }}, {{ tanggal_lahir_calon }}
                Agama : {{ agama_calon }}
                Pekerjaan : {{ pekerjaan_calon }}
                Tempat tinggal : {{ tempat_tinggal_calon }}

                Hubungan wali/status terhadap perempuan tsb : {{ hubungan_wali }}

                Demikian surat keterangan ini dibuat untuk menjadikan periksa dan dapat dipergunakan seperlunya.

                Magelang, {{ tanggal }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],

            // Wali Hakim
            16 => [
                'judul' => 'SURAT KETERANGAN WALI HAKIM',
                'isi_template' => <<<'TEXT'
                Yang bertanda tangan dibawah ini Lurah {{ nama_kelurahan }} Kec. Magelang Utara Kota Magelang menerangkan dengan sesungguhnya :

                I.
                1. Nama Lengkap dan aliasnya : {{ nama_calon_wanita }}
                2. Binti : {{ binti }}
                3. Umur : {{ umur_wanita }}
                4. Agama : {{ agama_wanita }}
                5. Pekerjaan : {{ pekerjaan_wanita }}
                6. Tempat Tinggal : {{ tempat_tinggal_wanita }}

                Akan melangsungkan pernikahan besuk pada tanggal {{ tanggal_pernikahan }} dengan seorang laki-laki :

                II.
                1. Nama Lengkap dan aliasnya : {{ nama_calon_pria }}
                2. Bin : {{ bin }}
                3. Umur : {{ umur_pria }}
                4. Agama : {{ agama_pria }}
                5. Pekerjaan : {{ pekerjaan_pria }}
                6. Tempat Tinggal : {{ tempat_tinggal_pria }}

                Dengan wali hakim dikarenakan / disebabkan : {{ alasan_wali_hakim }}

                Demikian surat keterangan ini dibuat agar menjadikan maklum dan dipergunakan seperlunya.

                Magelang, {{ tanggal }}

                Mempelai Perempuan
                {{ nama_calon_wanita }}

                LURAH {{ nama_kelurahan }}
                {{ nama_lurah }}
                NIP. {{ nip_lurah }}
                TEXT
            ],
        ];

        foreach ($templates as $layananId => $template) {
            $layanan = Layanan::find($layananId);

            if (!$layanan) {
                continue;
            }

            TemplateSurat::updateOrCreate(
                ['layanan_id' => $layananId],
                [
                    'judul' => $template['judul'],
                    'isi_template' => $template['isi_template'],
                    'aktif' => true,
                ]
            );
        }
                // Nonaktifkan template lama yang tidak dikelola seeder ini.
        TemplateSurat::whereNotIn(
            'layanan_id',
            array_keys($templates)
        )->update(['aktif' => false]);
    }
}
