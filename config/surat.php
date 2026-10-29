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

        'alamat_domisili' => [
            'label' => 'Alamat Domisili',
            'type' => 'textarea',
        ],

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

        'jenis_kelamin' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Jenis Kelamin',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
            'help' => 'Pilih jenis kelamin pemohon.',
        ],

        'tempat_lahir' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Tempat Lahir',
            'type' => 'text',
            'help' => 'Kota atau kabupaten tempat pemohon lahir.',
        ],

        'status' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Status Perkawinan',
            'type' => 'select',
            'options' => [
                'Belum Kawin',
                'Kawin',
                'Cerai Hidup',
                'Cerai Mati',
            ],
            'help' => 'Status perkawinan pemohon saat ini.',
        ],

        'agama' => [
            'group' => 'Data Pemohon (tambahan)',
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
            'help' => 'Agama yang dianut oleh pemohon.',
        ],

        'pekerjaan' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Pekerjaan',
            'type' => 'text',
            'help' => 'Pekerjaan atau profesi pemohon saat ini.',
        ],

        'alamat' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Alamat',
            'type' => 'textarea',
            'help' => 'Alamat lengkap tempat tinggal pemohon.',
        ],

        'kota' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Kota',
            'type' => 'text',
            'help' => 'Kota domisili pemohon.',
        ],

        'berlaku_mulai' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Berlaku Mulai',
            'type' => 'date',
            'help' => 'Tanggal awal masa berlaku surat pengantar.',
        ],

        'berlaku_sampai' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Berlaku Sampai',
            'type' => 'date',
            'help' => 'Tanggal akhir masa berlaku surat pengantar.',
        ],

        'nama_pasangan' => [
            'group' => 'Data Pasangan',
            'label' => 'Nama Suami / Istri',
            'type' => 'text',
            'help' => 'Nama lengkap suami atau istri yang bersangkutan.',
        ],

        'alasan_perceraian' => [
            'group' => 'Alasan Perceraian',
            'label' => 'Alasan Perceraian',
            'type' => 'textarea',
            'help' => 'Uraikan alasan pokok pengajuan permohonan cerai.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 5. SURAT PENGANTAR SANTUNAN KEMATIAN
    |--------------------------------------------------------------------------
    */
    5 => [

        'jenis_kelamin' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Jenis Kelamin',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
            'help' => 'Pilih jenis kelamin pemohon.',
        ],

        'tempat_lahir' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Tempat Lahir',
            'type' => 'text',
            'help' => 'Kota atau kabupaten tempat pemohon lahir.',
        ],

        'status' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Status Perkawinan',
            'type' => 'select',
            'options' => [
                'Belum Kawin',
                'Kawin',
                'Cerai Hidup',
                'Cerai Mati',
            ],
            'help' => 'Status perkawinan pemohon saat ini.',
        ],

        'agama' => [
            'group' => 'Data Pemohon (tambahan)',
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
            'help' => 'Agama yang dianut oleh pemohon.',
        ],

        'pekerjaan' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Pekerjaan',
            'type' => 'text',
            'help' => 'Pekerjaan atau profesi pemohon saat ini.',
        ],

        'alamat' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Alamat',
            'type' => 'textarea',
            'help' => 'Alamat tempat tinggal pemohon.',
        ],

        'kota' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Kota',
            'type' => 'text',
            'help' => 'Kota domisili pemohon.',
        ],

        'berlaku_mulai' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Berlaku Mulai',
            'type' => 'date',
            'help' => 'Tanggal awal masa berlaku surat pengantar.',
        ],

        'berlaku_sampai' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Berlaku Sampai',
            'type' => 'date',
            'help' => 'Tanggal akhir masa berlaku surat pengantar.',
        ],

        'alamat_almarhum' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Alamat Almarhum / Almarhumah',
            'type' => 'textarea',
            'help' => 'Alamat domisili terakhir almarhum/almarhumah.',
        ],

        'rt_almarhum' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'RT Almarhum / Almarhumah',
            'type' => 'text',
            'help' => 'Nomor RT domisili terakhir almarhum/almarhumah.',
        ],

        'rw_almarhum' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'RW Almarhum / Almarhumah',
            'type' => 'text',
            'help' => 'Nomor RW domisili terakhir almarhum/almarhumah.',
        ],

        'nama_almarhum' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Nama Almarhum / Almarhumah',
            'type' => 'text',
            'help' => 'Nama lengkap almarhum/almarhumah yang meninggal dunia.',
        ],

        'tanggal_meninggal' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Tanggal Meninggal',
            'type' => 'date',
            'help' => 'Tanggal meninggalnya almarhum/almarhumah.',
        ],

        'hubungan_ahli_waris' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Hubungan dengan Almarhum / Almarhumah',
            'type' => 'text',
            'help' => 'Hubungan kekerabatan pemohon dengan almarhum/almarhumah (contoh: Anak Kandung, Suami, Istri).',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 6. SURAT KUASA PENGAMBILAN PENSIUN
    |--------------------------------------------------------------------------
    */
    6 => [

        'jenis_kelamin' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Jenis Kelamin',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
            'help' => 'Jenis kelamin penerima kuasa.',
        ],

        'tempat_lahir' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Tempat Lahir',
            'type' => 'text',
            'help' => 'Tempat lahir penerima kuasa.',
        ],

        'status' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Status Perkawinan',
            'type' => 'select',
            'options' => [
                'Belum Kawin',
                'Kawin',
                'Cerai Hidup',
                'Cerai Mati',
            ],
            'help' => 'Status perkawinan penerima kuasa saat ini.',
        ],

        'agama' => [
            'group' => 'Data Penerima Kuasa',
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
            'help' => 'Agama yang dianut oleh penerima kuasa.',
        ],

        'pekerjaan' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Pekerjaan',
            'type' => 'text',
            'help' => 'Pekerjaan penerima kuasa saat ini.',
        ],

        'alamat' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Alamat',
            'type' => 'textarea',
            'help' => 'Alamat tempat tinggal penerima kuasa.',
        ],

        'kota' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Kota',
            'type' => 'text',
            'help' => 'Kota domisili penerima kuasa.',
        ],

        'berlaku_mulai' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Berlaku Mulai',
            'type' => 'date',
            'help' => 'Tanggal awal masa berlaku surat kuasa.',
        ],

        'berlaku_sampai' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Berlaku Sampai',
            'type' => 'date',
            'help' => 'Tanggal akhir masa berlaku surat kuasa.',
        ],

        'nama_pemberi_kuasa' => [
            'group' => 'Data Pemberi Kuasa',
            'label' => 'Nama Pemberi Kuasa',
            'type' => 'text',
            'help' => 'Nama lengkap pensiunan atau orang yang memberikan kuasa.',
        ],

        'nama_penerima_kuasa' => [
            'group' => 'Data Penerima Kuasa',
            'label' => 'Nama Penerima Kuasa',
            'type' => 'text',
            'help' => 'Nama lengkap orang yang diberi kuasa untuk mengambil pensiun.',
        ],

        'keperluan' => [
            'group' => 'Data Pemberi Kuasa',
            'label' => 'Keperluan Pengambilan Pensiun',
            'type' => 'textarea',
            'help' => 'Keperluan atau peruntukan kuasa pengambilan pensiun.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 7. SURAT KETERANGAN PENGHASILAN ORANG TUA
    |--------------------------------------------------------------------------
    */
    7 => [

        'tempat_lahir_orang_tua' => [
            'label' => 'Tempat Lahir Orang Tua / Wali',
            'type' => 'text',
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

        'alamat' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Alamat Pemohon',
            'type' => 'textarea',
            'help' => 'Alamat domisili pemohon saat ini.',
        ],

        'pekerjaan' => [
            'group' => 'Data Pemohon (tambahan)',
            'label' => 'Pekerjaan Pemohon',
            'type' => 'text',
            'help' => 'Pekerjaan atau profesi pemohon saat ini.',
        ],

        'nama_ktp' => [
            'group' => 'Dokumen KTP',
            'label' => 'Nama sesuai KTP',
            'type' => 'text',
            'help' => 'Penulisan nama lengkap sebagaimana tercantum pada KTP.',
        ],

        'nomor_ktp' => [
            'group' => 'Dokumen KTP',
            'label' => 'Nomor KTP',
            'type' => 'text',
            'help' => '16 digit Nomor Induk Kependudukan pada KTP.',
        ],

        'nama_kk' => [
            'group' => 'Dokumen KK',
            'label' => 'Nama sesuai KK',
            'type' => 'text',
            'help' => 'Penulisan nama lengkap sebagaimana tercantum pada Kartu Keluarga.',
        ],

        'nomor_kk' => [
            'group' => 'Dokumen KK',
            'label' => 'Nomor KK',
            'type' => 'text',
            'help' => '16 digit nomor Kartu Keluarga.',
        ],

        'nama_lain' => [
            'group' => 'Dokumen Pembanding (opsional)',
            'label' => 'Nama sesuai Dokumen Pembanding',
            'type' => 'text',
            'required' => false,
            'help' => 'Nama yang tercantum pada dokumen pembanding (kosongkan jika tidak ada).',
        ],

        'dokumen_perbedaan' => [
            'group' => 'Dokumen Pembanding (opsional)',
            'label' => 'Jenis Dokumen Pembanding',
            'type' => 'text',
            'required' => false,
            'help' => 'Jenis dokumen pembanding, misal: Ijazah, Akta Kelahiran, Sertifikat (kosongkan jika tidak ada).',
        ],

        'nama_yang_benar' => [
            'group' => 'Pernyataan Nama yang Benar',
            'label' => 'Nama yang Benar',
            'type' => 'text',
            'help' => 'Nama yang dinyatakan benar dan digunakan dalam surat pernyataan ini.',
        ],

        'nomor_dokumen_perbedaan' => [
            'group' => 'Dokumen Pembanding (opsional)',
            'label' => 'Nomor Dokumen Pembanding',
            'type' => 'text',
            'required' => false,
            'help' => 'Nomor dokumen pembanding yang bersangkutan (kosongkan jika tidak ada).',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 11. SURAT KETERANGAN USAHA
    |--------------------------------------------------------------------------
    */
    11 => [

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

        'berlaku_mulai' => [
            'label' => 'Berlaku Mulai',
            'type' => 'date',
        ],

        'berlaku_sampai' => [
            'label' => 'Berlaku Sampai',
            'type' => 'date',
        ],

        'rt_usaha' => [
            'label' => 'RT Usaha',
            'type' => 'text',
        ],

        'rw_usaha' => [
            'label' => 'RW Usaha',
            'type' => 'text',
        ],

        'nama_usaha' => [
            'label' => 'Nama Usaha',
            'type' => 'text',
        ],

        'tanggal_berdiri_usaha' => [
            'label' => 'Tanggal Berdiri Usaha',
            'type' => 'date',
            'required' => true,
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
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Nama Almarhum / Almarhumah',
            'type' => 'text',
            'help' => 'Nama lengkap almarhum/almarhumah yang telah meninggal dunia.',
        ],

        'jenis_kelamin_almarhum' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Jenis Kelamin Almarhum / Almarhumah',
            'type' => 'select',
            'options' => [
                'Laki-laki',
                'Perempuan',
            ],
            'help' => 'Pilih jenis kelamin almarhum/almarhumah.',
        ],

        'alamat_almarhum' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Alamat Almarhum / Almarhumah',
            'type' => 'textarea',
            'help' => 'Alamat domisili terakhir almarhum/almarhumah.',
        ],

        'tanggal_meninggal' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Tanggal Meninggal',
            'type' => 'date',
            'help' => 'Tanggal almarhum/almarhumah meninggal dunia.',
        ],

        'tempat_meninggal' => [
            'group' => 'Data Almarhum / Almarhumah',
            'label' => 'Tempat Meninggal',
            'type' => 'text',
            'help' => 'Kota atau tempat almarhum/almarhumah meninggal dunia.',
        ],

        'nomor_surat_kematian' => [
            'group' => 'Dokumen Kematian',
            'label' => 'Nomor Surat Keterangan Kematian',
            'type' => 'text',
            'help' => 'Nomor surat kematian dari kelurahan/rumah sakit atau akta kematian.',
        ],

        'tanggal_surat_kematian' => [
            'group' => 'Dokumen Kematian',
            'label' => 'Tanggal Surat Keterangan Kematian',
            'type' => 'date',
            'help' => 'Tanggal penerbitan surat keterangan kematian.',
        ],

        'ahli_waris' => [
            'group' => 'Data Ahli Waris',
            'label' => 'Data Ahli Waris',
            'type' => 'repeatable',
            'help' => 'Daftar nama seluruh ahli waris yang sah.',
            'fields' => [
                'nama' => [
                    'label' => 'Nama Ahli Waris',
                    'type' => 'text',
                ],
                'jenis_kelamin' => [
                    'label' => 'Jenis Kelamin',
                    'type' => 'select',
                    'options' => [
                        'Laki-laki',
                        'Perempuan',
                    ],
                ],
                'alamat' => [
                    'label' => 'Alamat',
                    'type' => 'textarea',
                ],
                'hubungan' => [
                    'label' => 'Hubungan dengan Almarhum / Almarhumah',
                    'type' => 'select',
                    'options' => [
                        'Suami',
                        'Istri',
                        'Anak Kandung',
                        'Anak Angkat',
                        'Ibu',
                        'Ayah',
                        'Saudari',
                        'Saudara',
                        'Cucu',
                        'Kakek',
                        'Nenek',
                        'Paman',
                        'Bibi',
                        'Keponakan',
                        'Sepupu',
                    ],
                ],
            ],
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
