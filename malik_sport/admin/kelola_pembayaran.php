<?php 
require_once '../config/koneksi.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_POST && isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    
    if ($_POST['action'] == 'validasi') {
        $koneksi->prepare("UPDATE jadwal_booking SET status_booking = 'selesai' WHERE id = ?")->execute([$booking_id]);
        $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'valid' WHERE booking_id = ?")->execute([$booking_id]);
    } elseif ($_POST['action'] == 'tolak') {
        $koneksi->prepare("UPDATE jadwal_booking SET status_booking = 'batal' WHERE id = ?")->execute([$booking_id]);
        $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'ditolak' WHERE booking_id = ?")->execute([$booking_id]);
    }
    header('Location: kelola_pembayaran.php?success=1');
    exit;
}


$pembayarans = $koneksi->query("
    SELECT jb.id, jb.tanggal_main, jb.jam_mulai, jb.jam_selesai, jb.total_harga,
           l.nama_lapangan, u.nama as user_nama, p.bukti_transfer, jb.status_booking
    FROM jadwal_booking jb
    JOIN lapangan l ON jb.lapangan_id = l.id
    JOIN users u ON jb.user_id = u.id
    LEFT JOIN pembayaran p ON jb.id = p.booking_id
    WHERE jb.status_booking IN ('pending', 'dibayar')
    ORDER BY jb.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Admin Malik Sport</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar sama seperti dashboard.php -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-futbol"></i> Malik Sport</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_pembayaran.php" class="nav-item active"><i class="fas fa-receipt"></i> Kelola Pembayaran</a>
                <a href="kelola_blog.php" class="nav-item"><i class="fas fa-blog"></i> Kelola Blog</a>
                <a href="../index.php" class="nav-item" target="_blank"><i class="fas fa-globe"></i> Website</a>
                <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="toggle-sidebar"><i class="fas fa-bars"></i></div>
                <div class="user-info">
                    <span>Admin <?php echo $_SESSION['user_nama']; ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Pembayaran berhasil diproses! <button class="close-btn">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-receipt"></i> Kelola Pembayaran (<?php echo count($pembayarans); ?> Pending)</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Lapangan</th>
                                    <th>Tanggal & Jam</th>
                                    <th>Total</th>
                                    <th>Bukti Transfer</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pembayarans as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['user_nama']); ?></td>
                                    <td><?php echo htmlspecialchars($item['nama_lapangan']); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($item['tanggal_main'])); ?><br>
                                        <small><?php echo date('H:i', strtotime($item['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($item['jam_selesai'])); ?></small>
                                    </td>
                                    <td><strong>Rp <?php echo number_format($item['total_harga'], 0, ',', '.'); ?></strong></td>
                                    <td>
                                        <?php if ($item['bukti_transfer']): ?>
                                            <a href="../assets/images/bukti/<?php echo $item['bukti_transfer']; ?>" target="_blank" class="bukti-link">
                                                <i class="fas fa-image"></i> Lihat Bukti
                                            </a>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $item['status_booking']; ?>">
                                            <?php echo ucfirst($item['status_booking']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="action" value="validasi">
                                            <button type="submit" class="btn-action btn-validasi" onclick="return confirm('Validasi pembayaran?')">
                                                <i class="fas fa-check"></i> Validasi
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="action" value="tolak">
                                            <button type="submit" class="btn-action btn-tolak" onclick="return confirm('Tolak pembayaran?')">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </form>
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
