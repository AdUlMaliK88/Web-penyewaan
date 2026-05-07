<?php 
require_once 'config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$lapangan_id = $_GET['id'];
$stmt = $koneksi->prepare("SELECT * FROM lapangan WHERE id = ?");
$stmt->execute([$lapangan_id]);
$lapangan = $stmt->fetch();

if (!$lapangan) {
    header('Location: index.php');
    exit;
}

// Ambil booking untuk cek ketersediaan (7 hari ke depan)
$tanggal_mulai = date('Y-m-d');
$stmt = $koneksi->prepare("
    SELECT tanggal_main, jam_mulai, jam_selesai, status_booking 
    FROM jadwal_booking 
    WHERE lapangan_id = ? AND tanggal_main >= ? 
    ORDER BY tanggal_main, jam_mulai
");
$stmt->execute([$lapangan_id, $tanggal_mulai]);
$bookings = $stmt->fetchAll();
$photo_path = !empty($lapangan['foto']) ? 'assets/images/lapangan/' . $lapangan['foto'] : 'assets/images/default.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lapangan['nama_lapangan']; ?> - Malik Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar (sama seperti index.php) -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><i class="fas fa-futbol"></i> Malik Sport</a>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="#lapangan" class="nav-link active">Lapangan</a></li>
                <li><a href="blog.php" class="nav-link">Blog</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="user/dashboard.php" class="nav-link"><?php echo $_SESSION['user_nama']; ?></a></li>
                    <li><a href="logout.php" class="nav-link">Keluar</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Detail Lapangan -->
        <div class="detail-header">
            <div class="detail-image">
                <img src="<?php echo $photo_path; ?>" alt="<?php echo $lapangan['nama_lapangan']; ?>">
            </div>
            <div class="detail-info">
                <div class="kategori-badge big"><?php echo strtoupper($lapangan['kategori']); ?></div>
                <h1><?php echo $lapangan['nama_lapangan']; ?></h1>
                <div class="harga-big">
                    <span>Rp <?php echo number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?></span>
                    <small>/ jam</small>
                </div>
                <p class="deskripsi-full"><?php echo nl2br($lapangan['deskripsi']); ?></p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user/pemesanan.php?lapangan_id=<?php echo $lapangan['id']; ?>" 
                       class="btn-pesan btn-primary">
                        <i class="fas fa-calendar-plus"></i> Pesan Sekarang
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-pesan btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Login untuk Pesan
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cek Ketersediaan -->
        <div class="availability-section">
            <h2><i class="fas fa-calendar-check"></i> Cek Ketersediaan</h2>
            <p>Lihat jadwal booking untuk 7 hari ke depan</p>
            
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Seluruh jam tersedia!</h3>
                    <p>Tidak ada booking untuk periode ini</p>
                </div>
            <?php else: ?>
                <div class="jadwal-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($booking['tanggal_main'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['jam_mulai'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['jam_selesai'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $booking['status_booking']; ?>">
                                        <?php echo ucfirst($booking['status_booking']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>