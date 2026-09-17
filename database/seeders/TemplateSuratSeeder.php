<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\TemplateSurat;
use Illuminate\Database\Seeder;

class TemplateSuratSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // =====================================================
            // 1. SKTM
            // =====================================================
            1 => [
                'judul' => 'SURAT KETERANGAN TIDAK MAMPU',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini:

1. Nama
   : '{{ nama_pejabat }}'

2. Jabatan
   : {{ jabatan_pejabat }}

Dengan ini menerangkan bahwa:

1. Nama Lengkap
   : {{ nama_lengkap }}

2. Jenis Kelamin
   : {{ jenis_kelamin }}

3. Tempat / Tanggal Lahir
   : {{ tempat_lahir }}, {{ tanggal_lahir }}

4. Warga Negara / Agama
   : Indonesia / {{ agama }}

5. No. KTP / NIK
   : {{ nik }}

6. Pekerjaan
   : {{ pekerjaan }}

7. Alamat
   : {{ alamat }}
     RT {{ rt }} / RW {{ rw }}

Menerangkan dengan sebenarnya bahwa yang bersangkutan betul warga Kecamatan Magelang Utara dengan keadaan ekonominya TIDAK MAMPU.

Surat keterangan ini diperlukan untuk {{ keperluan }}.

Demikian surat keterangan ini kami buat atas permintaan yang bersangkutan dan dapat digunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 2. BELUM MENIKAH
            // =====================================================
            2 => [
                'judul' => 'SURAT KETERANGAN BELUM MENIKAH',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tempat / Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Berdasarkan data administrasi dan dokumen persyaratan yang diberikan, yang bersangkutan sampai dengan surat ini diterbitkan belum menikah.

Surat keterangan ini dibuat untuk keperluan {{ keperluan }}.

Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 3. DOMISILI
            // =====================================================
            3 => [
                'judul' => 'SURAT KETERANGAN DOMISILI',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Yang bersangkutan benar berdomisili di wilayah Kecamatan Magelang Utara.

Surat keterangan domisili ini dibuat untuk keperluan {{ keperluan }}.

Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 4. PERMOHONAN CERAI
            // =====================================================
            4 => [
                'judul' => 'SURAT PENGANTAR PERMOHONAN CERAI',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Pemohon
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nama Suami / Istri
: {{ nama_pasangan }}

Alasan Perceraian
: {{ alasan_perceraian }}

Yang bersangkutan mengajukan permohonan perceraian dan surat ini dibuat sebagai surat pengantar untuk keperluan proses perceraian sesuai ketentuan yang berlaku.

Demikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 5. SANTUNAN KEMATIAN
            // =====================================================
            5 => [
                'judul' => 'SURAT KETERANGAN SANTUNAN KEMATIAN',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Ahli Waris / Pemohon
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nama Almarhum / Almarhumah
: {{ nama_almarhum }}

Tanggal Meninggal
: {{ tanggal_meninggal }}

Hubungan dengan Almarhum / Almarhumah
: {{ hubungan_ahli_waris }}

Surat keterangan ini dibuat untuk keperluan pengajuan santunan kematian berdasarkan dokumen persyaratan yang telah diberikan.

Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 6. KUASA PENGAMBILAN PENSIUN
            // =====================================================
            6 => [
                'judul' => 'SURAT KUASA PENGAMBILAN PENSIUN',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini:

Pemberi Kuasa
: {{ nama_pemberi_kuasa }}

Penerima Kuasa
: {{ nama_penerima_kuasa }}

Dengan ini memberikan kuasa untuk melakukan pengambilan pensiun dengan identitas pemohon:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Keperluan Pengambilan Pensiun
: {{ keperluan }}

Surat kuasa ini dibuat berdasarkan dokumen persyaratan yang telah diberikan dan untuk dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 7. PENGHASILAN
            // =====================================================
            7 => [
                'judul' => 'SURAT KETERANGAN PENGHASILAN',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Pekerjaan
: {{ pekerjaan }}

Berdasarkan keterangan yang diberikan, yang bersangkutan mempunyai penghasilan sebagai berikut:

Penghasilan per Bulan
: {{ penghasilan_per_bulan }}

Sumber Penghasilan
: {{ sumber_penghasilan }}

Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 8. IZIN PENGGUNAAN TANAH
            // =====================================================
            8 => [
                'judul' => 'SURAT KETERANGAN IZIN PENGGUNAAN TANAH',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Pemohon
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nama Pemilik Tanah
: {{ nama_pemilik_tanah }}

NIK Pemilik Tanah
: {{ nik_pemilik_tanah }}

Lokasi Tanah
: {{ lokasi_tanah }}

Nomor Bukti Kepemilikan Tanah
: {{ nomor_bukti_tanah }}

Keperluan Penggunaan Tanah
: {{ keperluan_penggunaan }}

Surat keterangan ini dibuat berdasarkan dokumen kepemilikan dan persetujuan pemilik tanah yang telah diberikan.

Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 9. JANDA / DUDA
            // =====================================================
            9 => [
                'judul' => 'SURAT KETERANGAN JANDA / DUDA',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nama Pasangan
: {{ nama_pasangan }}

Status
: {{ status_janda_duda }}

Sebab
: {{ sebab }}

Berdasarkan dokumen pendukung yang telah diberikan, yang bersangkutan berstatus janda / duda.

Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 10. BEDA NAMA
            // =====================================================
            10 => [
                'judul' => 'SURAT KETERANGAN BEDA NAMA',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nama pada Dokumen Lain
: {{ nama_lain }}

Dokumen yang Berbeda Nama
: {{ dokumen_perbedaan }}

Nama yang Benar
: {{ nama_yang_benar }}

Berdasarkan dokumen dan keterangan yang diberikan, nama-nama tersebut merupakan satu orang yang sama.

Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 11. USAHA
            // =====================================================
            11 => [
                'judul' => 'SURAT KETERANGAN USAHA',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Yang bersangkutan benar memiliki dan menjalankan usaha dengan keterangan:

Nama Usaha
: {{ nama_usaha }}

Jenis Usaha
: {{ jenis_usaha }}

Alamat Usaha
: {{ alamat_usaha }}

Lama Menjalankan Usaha
: {{ lama_usaha }}

Surat keterangan ini dibuat berdasarkan data dan dokumen pendukung yang telah diberikan.

Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 12. TNI / POLRI
            // =====================================================
            12 => [
                'judul' => 'SURAT PENGANTAR PERSYARATAN PENDAFTARAN TNI/POLRI',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Yang bersangkutan akan mengikuti pendaftaran:

Jenis Pendaftaran
: {{ jenis_pendaftaran }}

Instansi Tujuan
: {{ nama_instansi }}

Surat pengantar ini dibuat untuk melengkapi persyaratan pendaftaran TNI/Polri berdasarkan dokumen yang telah diberikan.

Demikian surat pengantar ini dibuat agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 13. PEMBETULAN SERTIFIKAT
            // =====================================================
            13 => [
                'judul' => 'SURAT KETERANGAN PERSYARATAN PEMBETULAN SERTIFIKAT',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Lengkap
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nomor Sertifikat
: {{ nomor_sertifikat }}

Jenis / Keterangan Kesalahan
: {{ jenis_kesalahan }}

Data yang Seharusnya
: {{ data_seharusnya }}

Surat keterangan ini dibuat untuk melengkapi persyaratan pembetulan sertifikat tanah sesuai dengan dokumen pendukung yang telah diberikan.

Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 14. AHLI WARIS
            // =====================================================
            14 => [
                'judul' => 'SURAT KETERANGAN AHLI WARIS',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Ahli Waris / Pemohon
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Nama Almarhum / Almarhumah
: {{ nama_almarhum }}

Tanggal Meninggal
: {{ tanggal_meninggal }}

Jumlah Ahli Waris
: {{ jumlah_ahli_waris }}

Surat keterangan ini dibuat berdasarkan dokumen dan keterangan ahli waris yang telah diberikan.

Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],

            // =====================================================
            // 15. WALI NIKAH / HAKIM
            // =====================================================
            15 => [
                'judul' => 'SURAT KETERANGAN WALI NIKAH / HAKIM',
                'isi_template' => <<<'TEXT'
Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama Calon Mempelai
: {{ nama_calon_mempelai }}

Nama Wali Nikah
: {{ nama_wali }}

Nama Lengkap Pemohon
: {{ nama_lengkap }}

NIK
: {{ nik }}

Tanggal Lahir
: {{ tanggal_lahir }}

RT / RW
: {{ rt }} / {{ rw }}

Alasan Penggunaan Wali Hakim
: {{ alasan_wali_hakim }}

Surat keterangan ini dibuat berdasarkan dokumen dan keterangan yang telah diberikan untuk memenuhi persyaratan wali nikah / wali hakim.

Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.
TEXT
            ],
        ];

        foreach ($templates as $layananId => $template) {

            $layanan = Layanan::find($layananId);

            if (!$layanan) {
                continue;
            }

            TemplateSurat::updateOrCreate(
                [
                    'layanan_id' => $layananId,
                ],
                [
                    'judul' => $template['judul'],
                    'isi_template' => $template['isi_template'],
                    'aktif' => true,
                ]
            );
        }
    }
}
