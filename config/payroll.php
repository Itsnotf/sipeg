<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pembagi Prorata
    |--------------------------------------------------------------------------
    |
    | true  : pembagi = jumlah hari bulan kalender (28/29/30/31). Pekerja yang
    |         hadir sebulan penuh selalu menerima tepat satu gaji bulanan.
    | false : pembagi tetap 30 hari, mengikuti kebiasaan sebagian perusahaan.
    |         Pada bulan 31 hari, hadir penuh menghasilkan lebih dari 1 bulan.
    |
    */

    'hari_per_bulan_kalender' => true,

    'hari_per_bulan_tetap' => 30,

    /*
    |--------------------------------------------------------------------------
    | Pembulatan Nominal
    |--------------------------------------------------------------------------
    |
    | Jumlah angka di belakang koma untuk setiap komponen gaji. Rupiah tidak
    | memiliki satuan di bawah 1, sehingga nilai bawaan 0 mencegah munculnya
    | pecahan sen yang tidak mungkin dibayarkan. Setiap komponen dibulatkan
    | sebelum dipakai menghitung komponen berikutnya agar slip selalu menjumlah.
    |
    */

    'pembulatan' => 0,

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Cashbon
    |--------------------------------------------------------------------------
    |
    | Dua kebijakan berbeda yang kebetulan bernilai sama. Jangan disatukan.
    |
    | batas_potongan    : proporsi maksimal gaji bersih yang boleh dipotong
    |                       dalam SATU periode. Sisa hutang terbawa ke periode
    |                       berikutnya sebagai cicilan.
    | maks_hutang_bulan : total hutang berjalan tidak boleh melebihi sekian kali
    |                     gaji bersih bulanan. Menolak pengajuan yang melebihi.
    |
    | Keduanya sengaja berbeda satuan. Bila plafon pinjaman juga dinyatakan
    | sebagai proporsi gaji sebulan dan nilainya sama dengan batas potongan,
    | setiap pinjaman selalu lunas dalam satu periode dan mekanisme cicilan
    | tidak pernah terpakai.
    |
    */

    'batas_potongan' => 0.5,

    'maks_hutang_bulan' => 3,

    /*
    |--------------------------------------------------------------------------
    | Jendela Koreksi Cashbon
    |--------------------------------------------------------------------------
    |
    | Jumlah hari sejak dibuat selama cashbon masih boleh diubah atau dihapus.
    | Cashbon yang sudah dialokasikan ke penggajian terbayar tetap terkunci
    | tanpa memandang jendela ini.
    |
    */

    'jendela_koreksi_hari' => 1,

    /*
    |--------------------------------------------------------------------------
    | BPJS
    |--------------------------------------------------------------------------
    |
    | Persentase bawaan saat jabatan belum menetapkan nilainya sendiri.
    |
    */

    'bpjs_persen_default' => 5.0,

];
