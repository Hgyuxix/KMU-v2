<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 1. SURAT KETERANGAN TIDAK MAMPU
    |--------------------------------------------------------------------------
    */
    1 => [

        'jenis_kelamin' => [
            'label' => 'Jenis Kelamin',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
        ],

        'tempat_lahir' => [
            'label' => 'Tempat Lahir',
            'type' => 'text',
        ],

        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                'Belum Kawin',
                'Kawin',
                'Cerai Hidup',
                'Cerai Mati',
            ],
        ],

        'agama' => [
            'label' => 'Agama',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan' => [
            'label' => 'Pekerjaan',
            'type' => 'text',
        ],

        'alamat' => [
            'label' => 'Alamat',
            'type' => 'textarea',
        ],

        'kota' => [
            'label' => 'Kota',
            'type' => 'text',
        ],

        'keperluan' => [
            'label' => 'Keperluan',
            'type' => 'textarea',
        ],

        'berlaku_mulai' => [
            'label' => 'Berlaku Mulai',
            'type' => 'date',
        ],

        'berlaku_sampai' => [
            'label' => 'Berlaku Sampai',
            'type' => 'date',
        ],

        'keterangan_lain' => [
            'label' => 'Keterangan Lain-lain',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 2. SURAT PERNYATAAN BELUM MENIKAH
    |--------------------------------------------------------------------------
    */
    2 => [

        'tempat_lahir' => [
            'label' => 'Tempat Lahir',
            'type' => 'text',
        ],

        'agama' => [
            'label' => 'Agama',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan' => [
            'label' => 'Pekerjaan',
            'type' => 'text',
        ],

        'alamat' => [
            'label' => 'Alamat',
            'type' => 'textarea',
        ],

        'status_pernyataan' => [
            'label' => 'Status Pernyataan',
            'type' => 'select',
            'options' => [
                'JEJAKA',
                'PERAWAN',
            ],
        ],

        'saksi_1' => [
            'label' => 'Saksi 1',
            'type' => 'text',
        ],

        'saksi_2' => [
            'label' => 'Saksi 2',
            'type' => 'text',
        ],

        'saksi_3' => [
            'label' => 'Saksi 3',
            'type' => 'text',
        ],

        'saksi_4' => [
            'label' => 'Saksi 4',
            'type' => 'text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 3. SURAT KETERANGAN DOMISILI
    |--------------------------------------------------------------------------
    */
    3 => [

        'keperluan' => [
            'label' => 'Keperluan',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 4. SURAT PENGANTAR CERAI
    |--------------------------------------------------------------------------
    */
    4 => [

        'nama_pasangan' => [
            'label' => 'Nama Suami / Istri',
            'type' => 'text',
        ],

        'alasan_perceraian' => [
            'label' => 'Alasan Perceraian',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 5. SURAT PENGANTAR SANTUNAN KEMATIAN
    |--------------------------------------------------------------------------
    */
    5 => [

        'jenis_kelamin' => [
            'label' => 'Jenis Kelamin',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
        ],

        'tempat_lahir' => [
            'label' => 'Tempat Lahir',
            'type' => 'text',
        ],

        'pekerjaan' => [
            'label' => 'Pekerjaan',
            'type' => 'text',
        ],

        'alamat' => [
            'label' => 'Alamat',
            'type' => 'textarea',
        ],

        'nama_almarhum' => [
            'label' => 'Nama Almarhum / Almarhumah',
            'type' => 'text',
        ],

        'tanggal_meninggal' => [
            'label' => 'Tanggal Meninggal',
            'type' => 'date',
        ],

        'hubungan_ahli_waris' => [
            'label' => 'Hubungan dengan Almarhum / Almarhumah',
            'type' => 'text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 6. SURAT KUASA PENGAMBILAN PENSIUN
    |--------------------------------------------------------------------------
    */
    6 => [

        'nama_pemberi_kuasa' => [
            'label' => 'Nama Pemberi Kuasa',
            'type' => 'text',
        ],

        'nama_penerima_kuasa' => [
            'label' => 'Nama Penerima Kuasa',
            'type' => 'text',
        ],

        'keperluan' => [
            'label' => 'Keperluan Pengambilan Pensiun',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 7. SURAT KETERANGAN PENGHASILAN ORANG TUA
    |--------------------------------------------------------------------------
    */
    7 => [

        'nik_orang_tua' => [
            'label' => 'NIK Orang Tua / Wali',
            'type' => 'text',
        ],

        'tempat_lahir_orang_tua' => [
            'label' => 'Tempat Lahir Orang Tua / Wali',
            'type' => 'text',
        ],

        'tanggal_lahir_orang_tua' => [
            'label' => 'Tanggal Lahir Orang Tua / Wali',
            'type' => 'date',
        ],

        'agama_orang_tua' => [
            'label' => 'Agama Orang Tua / Wali',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan' => [
            'label' => 'Pekerjaan Orang Tua / Wali',
            'type' => 'text',
        ],

        'alamat_orang_tua' => [
            'label' => 'Alamat Orang Tua / Wali',
            'type' => 'textarea',
        ],

        'nama_anak' => [
            'label' => 'Nama Anak',
            'type' => 'text',
        ],

        'jenis_kelamin_anak' => [
            'label' => 'Jenis Kelamin Anak',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
        ],

        'alamat_anak' => [
            'label' => 'Alamat Anak',
            'type' => 'textarea',
        ],

        'penghasilan_per_bulan' => [
            'label' => 'Penghasilan per Bulan',
            'type' => 'text',
        ],

        'keperluan' => [
            'label' => 'Keperluan',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 8. NONAKTIF — FORM CLIENT BELUM TERSEDIA
    |--------------------------------------------------------------------------
    */
    8 => [],

    /*
    |--------------------------------------------------------------------------
    | 9. NONAKTIF — FORM CLIENT BELUM TERSEDIA
    |--------------------------------------------------------------------------
    */
    9 => [],

    /*
    |--------------------------------------------------------------------------
    | 10. SURAT PERNYATAAN BEDA NAMA
    |--------------------------------------------------------------------------
    */
    10 => [

        'nama_lain' => [
            'label' => 'Nama pada Dokumen Lain',
            'type' => 'text',
        ],

        'dokumen_perbedaan' => [
            'label' => 'Dokumen yang Berbeda Nama',
            'type' => 'text',
        ],

        'nama_yang_benar' => [
            'label' => 'Nama yang Benar',
            'type' => 'text',
        ],

        'nomor_dokumen_perbedaan' => [
            'label' => 'Nomor Dokumen',
            'type' => 'text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 11. SURAT KETERANGAN USAHA
    |--------------------------------------------------------------------------
    */
    11 => [

        'nama_usaha' => [
            'label' => 'Nama Usaha',
            'type' => 'text',
        ],

        'lama_usaha' => [
            'label' => 'Lama Menjalankan Usaha',
            'type' => 'text',
        ],

        'alamat_usaha' => [
            'label' => 'Alamat Usaha',
            'type' => 'textarea',
        ],

        'nomor_surat_rt_rw' => [
            'label' => 'Nomor Surat RT/RW',
            'type' => 'text',
        ],

        'tanggal_surat_rt_rw' => [
            'label' => 'Tanggal Surat RT/RW',
            'type' => 'date',
        ],

        'keperluan' => [
            'label' => 'Keperluan',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 12. NONAKTIF — FORM CLIENT BELUM TERSEDIA
    |--------------------------------------------------------------------------
    */
    12 => [],

    /*
    |--------------------------------------------------------------------------
    | 13. NONAKTIF — FORM CLIENT BELUM TERSEDIA
    |--------------------------------------------------------------------------
    */
    13 => [],

    /*
    |--------------------------------------------------------------------------
    | 14. SURAT KETERANGAN AHLI WARIS
    |--------------------------------------------------------------------------
    */
    14 => [

        'nama_almarhum' => [
            'label' => 'Nama Almarhum / Almarhumah',
            'type' => 'text',
        ],

        'jenis_kelamin_almarhum' => [
            'label' => 'Jenis Kelamin Almarhum / Almarhumah',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
        ],

        'alamat_almarhum' => [
            'label' => 'Alamat Almarhum / Almarhumah',
            'type' => 'textarea',
        ],

        'tanggal_meninggal' => [
            'label' => 'Tanggal Meninggal',
            'type' => 'date',
        ],

        'nomor_surat_kematian' => [
            'label' => 'Nomor Surat Keterangan Kematian',
            'type' => 'text',
        ],

        'tanggal_surat_kematian' => [
            'label' => 'Tanggal Surat Keterangan Kematian',
            'type' => 'date',
        ],

        'ahli_waris' => [
            'label' => 'Data Ahli Waris',
            'type' => 'textarea',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 15. SURAT KETERANGAN WALI NIKAH
    |--------------------------------------------------------------------------
    */
    15 => [

        'nama_wali' => [
            'label' => 'Nama Wali',
            'type' => 'text',
        ],

        'tempat_lahir_wali' => [
            'label' => 'Tempat Lahir Wali',
            'type' => 'text',
        ],

        'tanggal_lahir_wali' => [
            'label' => 'Tanggal Lahir Wali',
            'type' => 'date',
        ],

        'agama_wali' => [
            'label' => 'Agama Wali',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan_wali' => [
            'label' => 'Pekerjaan Wali',
            'type' => 'text',
        ],

        'alamat_wali' => [
            'label' => 'Tempat Tinggal Wali',
            'type' => 'textarea',
        ],

        'nama_calon_mempelai' => [
            'label' => 'Nama Calon Mempelai',
            'type' => 'text',
        ],

        'tempat_lahir_calon' => [
            'label' => 'Tempat Lahir Calon Mempelai',
            'type' => 'text',
        ],

        'tanggal_lahir_calon' => [
            'label' => 'Tanggal Lahir Calon Mempelai',
            'type' => 'date',
        ],

        'agama_calon' => [
            'label' => 'Agama Calon Mempelai',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan_calon' => [
            'label' => 'Pekerjaan Calon Mempelai',
            'type' => 'text',
        ],

        'alamat_calon' => [
            'label' => 'Tempat Tinggal Calon Mempelai',
            'type' => 'textarea',
        ],

        'hubungan_wali' => [
            'label' => 'Hubungan Wali',
            'type' => 'text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 16. SURAT KETERANGAN WALI HAKIM / FORM BARU
    |--------------------------------------------------------------------------
    */
    16 => [

        'tanggal_pernikahan' => [
            'label' => 'Tanggal Pernikahan',
            'type' => 'date',
        ],

        'alasan_wali_hakim' => [
            'label' => 'Alasan Menggunakan Wali Hakim',
            'type' => 'textarea',
        ],

        'nama_calon_mempelai_perempuan' => [
            'label' => 'Nama Calon Mempelai Perempuan',
            'type' => 'text',
        ],

        'nama_ayah_calon' => [
            'label' => 'Nama Ayah Calon Mempelai',
            'type' => 'text',
        ],

        'umur_calon_perempuan' => [
            'label' => 'Umur Calon Mempelai Perempuan',
            'type' => 'number',
        ],

        'agama_calon_perempuan' => [
            'label' => 'Agama Calon Mempelai Perempuan',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan_calon_perempuan' => [
            'label' => 'Pekerjaan Calon Mempelai Perempuan',
            'type' => 'text',
        ],

        'alamat_calon_perempuan' => [
            'label' => 'Tempat Tinggal Calon Mempelai Perempuan',
            'type' => 'textarea',
        ],

        'nama_calon_mempelai_laki' => [
            'label' => 'Nama Calon Mempelai Laki-laki',
            'type' => 'text',
        ],

        'nama_ayah_calon_laki' => [
            'label' => 'Nama Ayah Calon Mempelai Laki-laki',
            'type' => 'text',
        ],

        'umur_calon_laki' => [
            'label' => 'Umur Calon Mempelai Laki-laki',
            'type' => 'number',
        ],

        'agama_calon_laki' => [
            'label' => 'Agama Calon Mempelai Laki-laki',
            'type' => 'select',
            'options' => [
                'Islam',
                'Kristen',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ],
        ],

        'pekerjaan_calon_laki' => [
            'label' => 'Pekerjaan Calon Mempelai Laki-laki',
            'type' => 'text',
        ],

        'alamat_calon_laki' => [
            'label' => 'Tempat Tinggal Calon Mempelai Laki-laki',
            'type' => 'textarea',
        ],
    ],

];
