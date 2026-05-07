<?php 
require_once '../config/koneksi.php';

// KEAMANAN: Pengecekan session user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php?redirect=dashboard');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $koneksi->prepare("SELECT nama, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil riwayat booking user
$stmt = $koneksi->prepare("
    SELECT jb.id, jb.tanggal_main, jb.jam_mulai, jb.jam_selesai, jb.total_harga, jb.status_booking,
           l.nama_lapangan, l.kategori
    FROM jadwal_booking jb
    JOIN lapangan l ON jb.lapangan_id = l.id
    WHERE jb.user_id = ?
    ORDER BY jb.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $user['nama']; ?> | Malik Sport</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* User Dashboard Styles */
        .user-dashboard { min-height: 100vh; padding-top: 90px; background: #f8f9fa; }
        .user-profile { 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        .user-avatar { 
            width: 100px; height: 100px; 
            background: linear-gradient(45deg, #667eea, #764ba2); 
            border-radius: 50%; 
            margin: 0 auto 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 2.5em; 
            color: white; 
        }
        .user-name { font-size: 2em; font-weight: 700; margin-bottom: 5px; color: #333; }
        .user-email { color: #666; font-size: 1.1em; }
        .stats-row { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 20px; 
            margin: 30px 0; 
        }
        .stat-item { 
            background: white; 
            padding: 20px; 
            border-radius: 15px; 
            text-align: center; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08); 
        }
        .stat-number { font-size: 2em; font-weight: 700; color: #667eea; }
        .stat-label { color: #666; font-size: 0.9em; }
        
        .booking-section { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .booking-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 25px 30px; 
        }
        .booking-table { width: 100%; border-collapse: collapse; }
        .booking-table th, .booking-table td { padding: 18px 20px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .booking-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .status-badge { 
            padding: 8px 16px; 
            border-radius: 25px; 
            font-size: 0.85em; 
            font-weight: 600; 
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-dibayar { background: #d1ecf1; color: #0c5460; }
        .status-selesai { background: #d4edda; color: #155724; }
        .status-batal { background: #f8d7da; color: #721c24; }
        .btn-bayar { 
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4); 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 25px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: all 0.3s; 
        }
        .btn-bayar:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,107,107,0.4); }
        .empty-state { 
            text-align: center; 
            padding: 60px 20px; 
            color: #666; 
        }
        .empty-state i { font-size: 4em; opacity: 0.5; margin-bottom: 20px; }
        @media (max-width: 768px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .booking-table { font-size: 0.9em; }
            .booking-table th, .booking-table td { padding: 12px 10px; }
        }
    </style>
</head>
<body class="user-dashboard">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php"><i class="fas fa-futbol"></i> Malik Sport</a>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php" class="nav-link">Home</a></li>
                <li><a href="../index.php#lapangan" class="nav-link">Lapangan</a></li>
                <li><a href="../blog.php" class="nav-link">Blog</a></li>
                <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
            </ul>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </div>
    </nav>

    <div class="container">
        <!-- Profil User -->
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="user-name"><?php echo htmlspecialchars($user['nama']); ?></h1>
            <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
            
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($bookings); ?></div>
                    <div class="stat-label">Total Booking</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $pending = array_filter($bookings, fn($b) => $b['status_booking'] == 'pending');
                        echo count($pending);
                        ?>
                    </div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $selesai = array_filter($bookings, fn($b) => $b['status_booking'] == 'selesai');
                        echo count($selesai);
                        ?>
                    </div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
            
            <a href="../index.php#lapangan" class="btn-primary" style="display:inline-block;padding:15px 30px;margin-top:20px;">
                <i class="fas fa-plus"></i> Booking Baru
            </a>
        </div>

        <!-- Riwayat Booking -->
        <div class="booking-section">
            <div class="booking-header">
                <h2><i class="fas fa-history"></i> Riwayat Booking</h2>
                <p>Riwayat pemesanan lapangan Anda</p>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Belum ada booking</h3>
                    <p>Buka halaman lapangan dan lakukan pemesanan pertama Anda!</p>
                    <a href="../index.php#lapangan" class="btn-bayar">Mulai Booking</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lapangan</th>
                                <th>Tanggal Main</th>
                                <th>Waktu</th>
                                <th>Total Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $index => $booking): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['nama_lapangan']); ?></strong>
                                    <br><small><?php echo strtoupper($booking['kategori']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($booking['tanggal_main'])); ?></td>
                                <td>
                                    <?php echo date('H:i', strtotime($booking['jam_mulai'])); ?> - 
                                    <?php echo date('H:i', strtotime($booking['jam_selesai'])); ?>
                                </td>
                                <td><strong>Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status_booking']; ?>">
                                        <?php echo ucfirst($booking['status_booking']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status_booking'] == 'pending'): ?>
                                        <a href="pembayaran.php?booking_id=<?php echo $booking['id']; ?>" 
                                           class="btn-bayar">
                                            <i class="fas fa-credit-card"></i> Bayar Sekarang
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#28a745;font-weight:600;">✓ Diproses</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>