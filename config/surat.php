<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 1. Surat Keterangan Tidak Mampu
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
            'label' => 'Alamat Lengkap',
            'type' => 'textarea',
        ],

        'keperluan' => [
            'label' => 'Keperluan Surat',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 2. Surat Keterangan Belum Menikah
    |--------------------------------------------------------------------------
    */
    2 => [

        'keperluan' => [
            'label' => 'Keperluan Surat',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 3. Surat Keterangan Domisili
    |--------------------------------------------------------------------------
    */
    3 => [

        'keperluan' => [
            'label' => 'Keperluan Domisili',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 4. Surat Pengantar Permohonan Cerai
    |--------------------------------------------------------------------------
    */
    4 => [

        'nama_pasangan' => [
            'label' => 'Nama Suami/Istri',
            'type' => 'text',
        ],

        'alasan_perceraian' => [
            'label' => 'Alasan Perceraian',
            'type' => 'textarea',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 5. Santunan Kematian
    |--------------------------------------------------------------------------
    */
    5 => [

        'nama_almarhum' => [
            'label' => 'Nama Almarhum/Almarhumah',
            'type' => 'text',
        ],

        'tanggal_meninggal' => [
            'label' => 'Tanggal Meninggal',
            'type' => 'date',
        ],

        'hubungan_ahli_waris' => [
            'label' => 'Hubungan dengan Almarhum/Almarhumah',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 6. Surat Kuasa Pengambilan Pensiun
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
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 7. Surat Keterangan Penghasilan
    |--------------------------------------------------------------------------
    */
    7 => [

        'pekerjaan' => [
            'label' => 'Pekerjaan',
            'type' => 'text',
        ],

        'penghasilan_per_bulan' => [
            'label' => 'Penghasilan per Bulan',
            'type' => 'money',
        ],

        'sumber_penghasilan' => [
            'label' => 'Sumber Penghasilan',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 8. Surat Keterangan Izin Penggunaan Tanah
    |--------------------------------------------------------------------------
    */
    8 => [

        'nama_pemilik_tanah' => [
            'label' => 'Nama Pemilik Tanah',
            'type' => 'text',
        ],

        'nik_pemilik_tanah' => [
            'label' => 'NIK Pemilik Tanah',
            'type' => 'text',
        ],

        'lokasi_tanah' => [
            'label' => 'Lokasi Tanah',
            'type' => 'textarea',
        ],

        'nomor_bukti_tanah' => [
            'label' => 'Nomor Bukti Kepemilikan Tanah',
            'type' => 'text',
        ],

        'keperluan_penggunaan' => [
            'label' => 'Keperluan Penggunaan Tanah',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 9. Surat Keterangan Janda/Duda
    |--------------------------------------------------------------------------
    */
    9 => [

        'nama_pasangan' => [
            'label' => 'Nama Pasangan',
            'type' => 'text',
        ],

        'status_janda_duda' => [
            'label' => 'Status Janda/Duda',
            'type' => 'select',
            'options' => [
                'Janda',
                'Duda',
            ],
        ],

        'sebab' => [
            'label' => 'Sebab Menjadi Janda/Duda',
            'type' => 'select',
            'options' => [
                'Cerai Hidup',
                'Cerai Mati',
            ],
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 10. Surat Keterangan Beda Nama
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
    ],


    /*
    |--------------------------------------------------------------------------
    | 11. Surat Keterangan Usaha
    |--------------------------------------------------------------------------
    */
    11 => [

        'nama_usaha' => [
            'label' => 'Nama Usaha',
            'type' => 'text',
        ],

        'jenis_usaha' => [
            'label' => 'Jenis Usaha',
            'type' => 'text',
        ],

        'alamat_usaha' => [
            'label' => 'Alamat Usaha',
            'type' => 'textarea',
        ],

        'lama_usaha' => [
            'label' => 'Lama Berdiri Usaha',
            'type' => 'date',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 12. Surat Pengantar Persyaratan Pendaftaran TNI/Polri
    |--------------------------------------------------------------------------
    */
    12 => [

        'jenis_pendaftaran' => [
            'label' => 'Jenis Pendaftaran',
            'type' => 'select',
            'options' => [
                'TNI',
                'Polri',
            ],
        ],

        'nama_instansi' => [
            'label' => 'Instansi Tujuan',
            'type' => 'text',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 13. Surat Keterangan Persyaratan Pembetulan Sertifikat
    |--------------------------------------------------------------------------
    */
    13 => [

        'nomor_sertifikat' => [
            'label' => 'Nomor Sertifikat',
            'type' => 'text',
        ],

        'jenis_kesalahan' => [
            'label' => 'Jenis/Keterangan Kesalahan',
            'type' => 'textarea',
        ],

        'data_seharusnya' => [
            'label' => 'Data yang Seharusnya',
            'type' => 'textarea',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 14. Surat Keterangan Ahli Waris
    |--------------------------------------------------------------------------
    */
    14 => [

        'nama_almarhum' => [
            'label' => 'Nama Almarhum/Almarhumah',
            'type' => 'text',
        ],

        'tanggal_meninggal' => [
            'label' => 'Tanggal Meninggal',
            'type' => 'date',
        ],

        'jumlah_ahli_waris' => [
            'label' => 'Jumlah Ahli Waris',
            'type' => 'number',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | 15. Surat Keterangan Wali Nikah/Hakim
    |--------------------------------------------------------------------------
    */
    15 => [

        'nama_calon_mempelai' => [
            'label' => 'Nama Calon Mempelai',
            'type' => 'text',
        ],

        'nama_wali' => [
            'label' => 'Nama Wali Nikah',
            'type' => 'text',
        ],

        'alasan_wali_hakim' => [
            'label' => 'Alasan Penggunaan Wali Hakim',
            'type' => 'textarea',
        ],
    ],

];
