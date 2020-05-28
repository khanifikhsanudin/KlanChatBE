<?php

function waktu_lalu($timestamp)
{
    date_default_timezone_set('Asia/Jakarta');
    $selisih = time() - strtotime($timestamp) ;
    $detik = $selisih ;
    $menit = round($selisih / 60 );
    $jam = round($selisih / 3600 );
    $hari = round($selisih / 86400 );
    $minggu = round($selisih / 604800 );
    $bulan = round($selisih / 2419200 );
    $tahun = round($selisih / 29030400 );
    if ($detik <= 60) {
        $waktu = 'baru saja';
    } else if ($menit <= 60) {
        $waktu = "· ".$menit.' menit lalu';
    } else if ($jam <= 24) {
        $waktu = "· ".$jam.' jam lalu';
    } else if ($hari <= 7) {
        $waktu = "· ".$hari.' hari lalu';
    } else if ($minggu <= 4) {
        $waktu = "· ".$minggu.' minggu lalu';
    } else if ($bulan <= 12) {
        $waktu = "· ".$bulan.' bulan lalu';
    } else {
        $waktu = "· ".$tahun.' tahun lalu';
    }
    return $waktu;
}

