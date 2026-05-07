<?php 
require_once '../config/koneksi.php';
require_once '../config/functions.php'; 
// Pengecekan login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=pemesanan');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['lapangan_id']) || !is_numeric($_GET['lapangan_id'])) {
    header('Location: ../index.php');
    exit;
}

$lapangan_id = $_GET['lapangan_id'];

// Ambil data lapangan
$stmt = $koneksi->prepare("SELECT * FROM lapangan WHERE id = ?");
$stmt->execute([$lapangan_id]);
$lapangan = $stmt->fetch();

if (!$lapangan) {
    header('Location: ../index.php');
    exit;
}

// Proses pemesanan
// Proses pemesanan - UPDATED DENGAN PENGECEKAN
if ($_POST) {
    $tanggal_main = $_POST['tanggal_main'];
    $jam_mulai = $_POST['jam_mulai'] . ':00';  // Tambah detik
    $jam_selesai = $_POST['jam_selesai'] . ':00';
    
    // VALIDASI 1: Tanggal masa depan
    if (strtotime($tanggal_main) < strtotime('tomorrow')) {
        $error = "Tanggal main harus besok atau setelahnya!";
    } else {
        // HITUNG DURASI
        $start = new DateTime($jam_mulai);
        $end = new DateTime($jam_selesai);
        $durasi = $start->diff($end)->h + ($start->diff($end)->i / 60);
        
        if ($durasi <= 0 || $durasi > 12) {
            $error = "Durasi minimal 1 jam dan maksimal 12 jam!";
        } else {
            // ✅ PENGECEKAN KETERSEDIAAN - FUNGSI BARU
            if (isLapanganAvailable($koneksi, $lapangan_id, $tanggal_main, $jam_mulai, $jam_selesai)) {
                // AMAN - Lanjutkan booking
                $total_harga = $lapangan['harga_per_jam'] * $durasi;
                
                $stmt = $koneksi->prepare("
                    INSERT INTO jadwal_booking (user_id, lapangan_id, tanggal_main, jam_mulai, jam_selesai, total_harga) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $lapangan_id, $tanggal_main, $jam_mulai, $jam_selesai, $total_harga]);
                
                $booking_id = $koneksi->lastInsertId();
                header("Location: pembayaran.php?booking_id=$booking_id");
                exit;
            } else {
                // ❌ BENTROK - Tampilkan error
                $error = "❌ Maaf, jadwal pada jam tersebut sudah terisi. Silakan pilih jam lain.";
            }
        }
    }
}
?>
<?php if ($_POST && isset($error) && strpos($error, 'terisi') !== false): ?>
<div class="jadwal-sibuk-warning">
    <h4><i class="fas fa-exclamation-triangle"></i> Jadwal Sibuk Hari Ini:</h4>
    <?php 
    $jadwal_sibuk = getJadwalSibuk($koneksi, $lapangan_id, $tanggal_main);
    if ($jadwal_sibuk): 
    ?>
    <div class="jadwal-list">
        <?php foreach ($jadwal_sibuk as $booking): ?>
        <div class="jadwal-item">
            <span><?php echo date('H:i', strtotime($booking['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($booking['jam_selesai'])); ?></span>
            <span class="status-<?php echo $booking['status_booking']; ?>"><?php echo ucfirst($booking['status_booking']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p>Belum ada booking lain hari ini.</p>
    <?php endif; ?>
</div>
<?php endif; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - <?php echo $lapangan['nama_lapangan']; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php"><i class="fas fa-futbol"></i> Malik Sport</a>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php" class="nav-link">Home</a></li>
                <li><a href="../index.php#lapangan" class="nav-link">Lapangan</a></li>
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="../logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div style="margin-top: 120px;">
            <a href="../detail_lapangan.php?id=<?php echo $lapangan_id; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="pemesanan-container">
            <div class="pemesanan-header">
                <div class="lapangan-preview">
                    <img src="../assets/images/<?php echo $lapangan['foto'] ?: 'default.jpg'; ?>" alt="">
                    <div class="lapangan-info">
                        <h2><?php echo $lapangan['nama_lapangan']; ?></h2>
                        <div class="kategori-badge"><?php echo strtoupper($lapangan['kategori']); ?></div>
                        <div class="harga">Rp <?php echo number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?> / jam</div>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="pemesanan-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal Main</label>
                        <input type="date" name="tanggal_main" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="jam_mulai" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="jam_selesai" required>
                    </div>
                </div>

                <div class="total-preview">
                    <div class="durasi-info">
                        <span id="durasi">Durasi: -- jam</span>
                    </div>
                    <div class="total-harga">
                        <span>Total: Rp <span id="total_harga">0</span></span>
                    </div>
                </div>

                <button type="submit" class="btn-pesan-large">
                    <i class="fas fa-lock"></i> Konfirmasi Pemesanan
                </button>
            </form>
        </div>
    </div>

    <script>
        const hargaPerJam = <?php echo $lapangan['harga_per_jam']; ?>;
        
        function hitungTotal() {
            const jamMulai = document.getElementById('jam_mulai').value;
            const jamSelesai = document.getElementById('jam_selesai').value;
            
            if (jamMulai && jamSelesai) {
                const start = new Date('2000-01-01T' + jamMulai);
                const end = new Date('2000-01-01T' + jamSelesai);
                const durasi = (end - start) / (1000 * 60 * 60);
                
                if (durasi > 0) {
                    const total = Math.round(hargaPerJam * durasi);
                    document.getElementById('durasi').textContent = `Durasi: ${durasi} jam`;
                    document.getElementById('total_harga').textContent = total.toLocaleString('id-ID');
                }
            }
        }
        
        document.getElementById('jam_mulai').addEventListener('change', hitungTotal);
        document.getElementById('jam_selesai').addEventListener('change', hitungTotal);
    </script>
</body>
</html>