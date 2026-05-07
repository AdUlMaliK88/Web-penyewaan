<?php require_once 'config/koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malik Sport - Sewa Lapangan Futsal, Basket, Badminton</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><i class="fas fa-futbol"></i> Malik Sport</a>
            </div>
            <ul class="nav-menu">
            <li><a href="index.php" class="nav-link active">Home</a></li>
            <li><a href="#lapangan" class="nav-link">Lapangan</a></li>
            <li><a href="blog.php" class="nav-link">Blog</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <!-- ADMIN MENU -->
                    <li><a href="admin/dashboard.php" class="nav-link">
                        <i class="fas fa-crown"></i> Admin Panel
                    </a></li>
                    <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                <?php else: ?>
                    <!-- USER MENU -->
                    <li><a href="user/dashboard.php" class="nav-link">
                        <i class="fas fa-user"></i> <?php echo substr($_SESSION['user_nama'], 0, 12); ?>...
                    </a></li>
                    <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                <?php endif; ?>
            <?php else: ?>
                <!-- GUEST MENU -->
                <li><a href="login.php" class="nav-link">Login</a></li>
                <li><a href="register.php" class="nav-link">Daftar</a></li>
            <?php endif; ?>
        </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Selamat Datang di <span class="highlight">Malik Sport</span></h1>
            <p>Sewa lapangan futsal, basket, badminton, dan minisoccer terbaik dengan harga terjangkau. Booking mudah, bayar aman!</p>
            <a href="#lapangan" class="cta-button">Lihat Lapangan <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="hero-image">
            <i class="fas fa-futbol"></i>
        </div>
    </section>

    <!-- Lapangan Section -->
    <section id="lapangan" class="lapangan">
    <div class="container">
        <h2 class="section-title">Pilih Lapangan Favoritmu</h2>
        <p class="section-subtitle">Lapangan berkualitas dengan fasilitas lengkap</p>
        
        <div class="lapangan-grid">
            <?php
            $stmt = $koneksi->query("SELECT * FROM lapangan ORDER BY id DESC");
            while ($lapangan = $stmt->fetch()):
                // FIX PATH FOTO LAPANGAN
                $photo_path = !empty($lapangan['foto']) ? 'assets/images/lapangan/' . $lapangan['foto'] : 'assets/images/default.jpg';
            ?>
            <div class="lapangan-card">
                <div class="card-image">
                    <img src="<?php echo $photo_path; ?>" alt="<?php echo htmlspecialchars($lapangan['nama_lapangan']); ?>" loading="lazy">
                    <div class="kategori-badge"><?php echo strtoupper($lapangan['kategori']); ?></div>
                </div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($lapangan['nama_lapangan']); ?></h3>
                    <p class="deskripsi"><?php echo substr($lapangan['deskripsi'], 0, 100); ?>...</p>
                    <div class="harga">
                        <span class="harga-text">Rp <?php echo number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?></span>
                        <span class="harga-sub">/ jam</span>
                    </div>
                    <a href="detail_lapangan.php?id=<?php echo $lapangan['id']; ?>" class="btn-detail">
                        Lihat Detail <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Malik Sport. Semua hak dilindungi. <i class="fas fa-heart"></i></p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>