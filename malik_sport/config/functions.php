<?php
/**
 * Fungsi pengecekan ketersediaan lapangan
 * @param PDO $koneksi - Koneksi database
 * @param int $lapangan_id - ID lapangan
 * @param string $tanggal - Format Y-m-d
 * @param string $jam_mulai - Format H:i:s
 * @param string $jam_selesai - Format H:i:s
 * @return bool TRUE = Tersedia, FALSE = Bentrok
 */
function isLapanganAvailable($koneksi, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai) {
    // Prepared statement AMAN dari SQL Injection
    $stmt = $koneksi->prepare("
        SELECT id FROM jadwal_booking 
        WHERE lapangan_id = ? 
        AND tanggal_main = ? 
        AND status_booking != 'batal'
        AND (
            (jam_mulai < ? AND jam_selesai > ?)  -- Booking lama overlap awal baru
            OR 
            (jam_mulai < ? AND jam_selesai >= ?) -- Booking lama overlap akhir baru
            OR 
            (? <= jam_mulai AND ? >= jam_selesai) -- Booking baru overlap booking lama
        )
    ");
    
    $stmt->execute([
        $lapangan_id, 
        $tanggal, 
        $jam_selesai, $jam_mulai,  // Overlap 1
        $jam_selesai, $jam_mulai,  // Overlap 2  
        $jam_mulai, $jam_selesai   // Overlap 3
    ]);
    
    return $stmt->rowCount() == 0; // TRUE = Aman (kosong), FALSE = Bentrok
}

/**
 * Ambil jadwal sibuk lapangan pada tanggal tertentu
 * @return array - List booking yang aktif
 */
function getJadwalSibuk($koneksi, $lapangan_id, $tanggal) {
    $stmt = $koneksi->prepare("
        SELECT jam_mulai, jam_selesai, status_booking 
        FROM jadwal_booking 
        WHERE lapangan_id = ? 
        AND tanggal_main = ? 
        AND status_booking != 'batal'
        ORDER BY jam_mulai
    ");
    $stmt->execute([$lapangan_id, $tanggal]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>