<?php 
require_once '../config/koneksi.php';

// Pengecekan login admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Statistik
$total_user = $koneksi->query("SELECT COUNT(*) as total FROM users WHERE role='user'")->fetch()['total'];
$total_pending = $koneksi->query("SELECT COUNT(*) as total FROM jadwal_booking WHERE status_booking='pending'")->fetch()['total'];
$pendapatan_bulan = $koneksi->query("
    SELECT COALESCE(SUM(total_harga), 0) as total 
    FROM jadwal_booking 
    WHERE status_booking='selesai' 
    AND MONTH(created_at) = MONTH(CURDATE()) 
    AND YEAR(created_at) = YEAR(CURDATE())
")->fetch()['total'];

// Proses aksi validasi/tolak
if ($_POST && isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    if ($action == 'validasi') {
        // Update booking dan pembayaran
        $koneksi->prepare("UPDATE jadwal_booking SET status_booking = 'selesai' WHERE id = ?")->execute([$booking_id]);
        $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'valid' WHERE booking_id = ?")->execute([$booking_id]);
        $message = "Pesanan berhasil divalidasi!";
        $message_type = "success";
    } elseif ($action == 'tolak') {
        $koneksi->prepare("UPDATE jadwal_booking SET status_booking = 'batal' WHERE id = ?")->execute([$booking_id]);
        $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'ditolak' WHERE booking_id = ?")->execute([$booking_id]);
        $message = "Pesanan berhasil ditolak!";
        $message_type = "warning";
    }
    
    // Refresh data setelah aksi
    $total_pending = $koneksi->query("SELECT COUNT(*) as total FROM jadwal_booking WHERE status_booking='pending'")->fetch()['total'];
}

// Ambil data pembayaran untuk tabel
$stmt = $koneksi->query("
    SELECT jb.id, jb.tanggal_main, jb.jam_mulai, jb.jam_selesai, jb.total_harga,
           l.nama_lapangan, u.nama as user_nama, p.bukti_transfer, jb.status_booking, p.status_pembayaran
    FROM jadwal_booking jb
    JOIN lapangan l ON jb.lapangan_id = l.id
    JOIN users u ON jb.user_id = u.id
    LEFT JOIN pembayaran p ON jb.id = p.booking_id
    WHERE jb.status_booking IN ('pending', 'dibayar')
    ORDER BY jb.created_at DESC
");
$pembayarans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Malik Sport</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-futbol"></i> Malik Sport</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item active">
        <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
    </a>
    <a href="kelola_pembayaran.php" class="nav-item">
    <i class="fas fa-receipt"></i> <span>Kelola Pembayaran</span>
</a>
    </a>
    <a href="kelola_blog.php" class="nav-item">
        <i class="fas fa-blog"></i> <span>Kelola Blog</span>
    </a>
    <a href="kelola_lapangan.php" class="nav-item">
        <i class="fas fa-table-tennis"></i> <span>Kelola Lapangan</span>
    </a>
    <a href="kelola_user.php" class="nav-item">
        <i class="fas fa-users"></i> <span>Kelola User</span>
    </a>
    <a href="../index.php" class="nav-item" target="_blank">
        <i class="fas fa-globe"></i> <span>Website</span>
    </a>
    <hr style="margin: 20px 25px; opacity: 0.3;">
    <a href="../logout.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-info">
                    <span>Halo, Admin <?php echo $_SESSION['user_nama']; ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <div class="content">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                        <button class="close-btn">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_user); ?></h3>
                            <p>Total User</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_pending); ?></h3>
                            <p>Pesanan Pending</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Rp <?php echo number_format($pendapatan_bulan, 0, ',', '.'); ?></h3>
                            <p>Pendapatan Bulan Ini</p>
                        </div>
                    </div>
                </div>

                <!-- Pembayaran Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-receipt"></i> Kelola Pembayaran</h2>
                        <div class="table-actions">
                            <input type="text" id="search" placeholder="Cari pesanan...">
                            <button class="refresh-btn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>User</th>
                                    <th>Lapangan</th>
                                    <th>Tanggal & Jam</th>
                                    <th>Total</th>
                                    <th>Bukti</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pembayarans as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['user_nama']); ?></td>
                                    <td><?php echo htmlspecialchars($item['nama_lapangan']); ?></td>
                                    <td>
                                        <div><?php echo date('d/m/Y', strtotime($item['tanggal_main'])); ?></div>
                                        <div><?php echo date('H:i', strtotime($item['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($item['jam_selesai'])); ?></div>
                                    </td>
                                    <td>Rp <?php echo number_format($item['total_harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($item['bukti_transfer']): ?>
                                            <a href="../assets/images/bukti/<?php echo $item['bukti_transfer']; ?>" 
                                               target="_blank" class="bukti-link">
                                                <i class="fas fa-image"></i> Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="no-bukti">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $item['status_booking']; ?>">
                                            <?php echo ucfirst($item['status_booking']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['status_booking'] == 'pending' || $item['status_booking'] == 'dibayar'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin <?php echo $item['status_booking']=='pending' ? 'validasi' : 'selesai'; ?> pesanan ini?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="action" value="<?php echo $item['status_booking']=='pending' ? 'validasi' : 'selesai'; ?>">
                                                <button type="submit" class="btn-action <?php echo $item['status_booking']=='pending' ? 'btn-validasi' : 'btn-selesai'; ?>">
                                                    <?php echo $item['status_booking']=='pending' ? '<i class="fas fa-check"></i> Validasi' : '<i class="fas fa-check-double"></i> Selesai'; ?>
                                                </button>
                                            </form>
                                            <button onclick="tolakPesanan(<?php echo $item['id']; ?>)" class="btn-action btn-tolak">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        <?php else: ?>
                                            <span class="completed">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>