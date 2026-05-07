<?php require_once 'config/koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - Malik Sport</title>
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
            <div class="hamburger"><span></span><span></span><span></span></div>
        </div>
    </nav>

    <!-- Hero Blog -->
    <section class="blog-hero">
        <div class="container">
            <h1>Blog Olahraga</h1>
            <p>Tips, berita, dan update terbaru dunia olahraga</p>
        </div>
    </section>

    <!-- Blog Grid -->
    <section class="blog-section">
        <div class="container">
            <div class="blog-grid">
                <?php
                $stmt = $koneksi->query("SELECT * FROM blog ORDER BY tanggal_publish DESC LIMIT 12");
                while ($artikel = $stmt->fetch()):
                ?>
                <article class="blog-card">
                    <div class="blog-image">
                        <img src="assets/images/blog/<?php echo $artikel['gambar']; ?>" 
                             alt="<?php echo htmlspecialchars($artikel['judul']); ?>" loading="lazy">
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="date"><?php echo date('d M Y', strtotime($artikel['tanggal_publish'])); ?></span>
                        </div>
                        <h2><?php echo htmlspecialchars($artikel['judul']); ?></h2>
                        <p><?php echo substr(strip_tags($artikel['konten']), 0, 150); ?>...</p>
                        <a href="detail_blog.php?id=<?php echo $artikel['id']; ?>" class="read-more">
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <script src="assets/js/main.js"></script>
</body>
</html>