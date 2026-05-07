<?php 
require_once 'config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: blog.php');
    exit;
}

$id = $_GET['id'];
$stmt = $koneksi->prepare("SELECT * FROM blog WHERE id = ?");
$stmt->execute([$id]);
$artikel = $stmt->fetch();

if (!$artikel) {
    header('Location: blog.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artikel['judul']); ?> - Malik Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
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
        </div>
    </nav>

    <article class="detail-blog">
        <div class="container">
            <div class="blog-header">
                <img src="assets/images/blog/<?php echo $artikel['gambar']; ?>" 
                     alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                <div class="blog-meta">
                    <span class="date"><?php echo date('d M Y H:i', strtotime($artikel['tanggal_publish'])); ?></span>
                </div>
                <h1><?php echo htmlspecialchars($artikel['judul']); ?></h1>
            </div>
            
            <div class="blog-content-full">
                <?php echo nl2br($artikel['konten']); ?>
            </div>
            
            <div class="blog-footer">
                <a href="blog.php" class="back-to-blog">
                    <i class="fas fa-arrow-left"></i> Kembali ke Blog
                </a>
            </div>
        </div>
    </article>

    <script src="assets/js/main.js"></script>
</body>
</html>