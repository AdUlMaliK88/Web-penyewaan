<?php 
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_POST) {
    $judul = trim($_POST['judul']);
    $konten = $_POST['konten'];
    
    $upload_dir = '../assets/images/blog/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $file = $_FILES['gambar'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed) && $file['size'] <= 2000000) {
            $file_name = 'blog_' . time() . '_' . rand(1000,9999) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $gambar = $file_name;
            }
        }
    }
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
      
        if (isset($gambar)) {
            $stmt = $koneksi->prepare("UPDATE blog SET judul=?, konten=?, gambar=?, tanggal_publish=NOW() WHERE id=?");
            $stmt->execute([$judul, $konten, $gambar, $_POST['edit_id']]);
        } else {
            $stmt = $koneksi->prepare("UPDATE blog SET judul=?, konten=?, tanggal_publish=NOW() WHERE id=?");
            $stmt->execute([$judul, $konten, $_POST['edit_id']]);
        }
        $message = "Artikel berhasil diupdate!";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO blog (judul, konten, gambar) VALUES (?, ?, ?)");
        $stmt->execute([$judul, $konten, $gambar]);
        $message = "Artikel berhasil ditambahkan!";
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $koneksi->prepare("SELECT gambar FROM blog WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    $artikel = $stmt->fetch();
    
    if ($artikel && $artikel['gambar']) {
        unlink('../assets/images/blog/' . $artikel['gambar']);
    }
    
    $koneksi->prepare("DELETE FROM blog WHERE id=?")->execute([$_GET['delete']]);
    header('Location: kelola_blog.php?deleted=1');
    exit;
}

$edit_artikel = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $koneksi->prepare("SELECT * FROM blog WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_artikel = $stmt->fetch();
}

$articles = $koneksi->query("SELECT * FROM blog ORDER BY tanggal_publish DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Blog - Admin Malik Sport</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-futbol"></i> Malik Sport</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_blog.php" class="nav-item active"><i class="fas fa-blog"></i> Kelola Blog</a>
                <a href="lapangan.php" class="nav-item"><i class="fas fa-table-tennis"></i> Lapangan</a>
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
                <?php if (isset($message)): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?> 
                        <button class="close-btn">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Form Tambah/Edit -->
                <div class="form-container">
                    <h2><i class="fas fa-plus"></i> <?php echo $edit_artikel ? 'Edit Artikel' : 'Tambah Artikel Baru'; ?></h2>
                    <form method="POST" enctype="multipart/form-data" class="blog-form">
                        <?php if ($edit_artikel): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_artikel['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Judul Artikel</label>
                            <input type="text" name="judul" required 
                                   value="<?php echo $edit_artikel ? htmlspecialchars($edit_artikel['judul']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Gambar Artikel</label>
                            <input type="file" name="gambar" accept="image/*">
                            <?php if ($edit_artikel && $edit_artikel['gambar']): ?>
                                <img src="../assets/images/blog/<?php echo $edit_artikel['gambar']; ?>" 
                                     alt="Current" class="current-image" style="max-width: 200px; margin-top: 10px;">
                                <small>Gambar baru akan menggantikan yang lama</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Konten Artikel</label>
                            <textarea name="konten" rows="12" required><?php echo $edit_artikel ? $edit_artikel['konten'] : ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <a href="kelola_blog.php" class="btn-cancel">Batal</a>
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> <?php echo $edit_artikel ? 'Update' : 'Publish'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Artikel -->
                <div class="table-container" style="margin-top: 40px;">
                    <div class="table-header">
                        <h2><i class="fas fa-list"></i> Daftar Artikel (<?php echo count($articles); ?>)</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Judul</th>
                                    <th>Tanggal Publish</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $artikel): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/images/blog/<?php echo $artikel['gambar']; ?>" 
                                             alt="<?php echo htmlspecialchars($artikel['judul']); ?>" 
                                             class="table-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($artikel['judul']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($artikel['tanggal_publish'])); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $artikel['id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $artikel['id']; ?>" 
                                           class="btn-action btn-delete"
                                           onclick="return confirm('Yakin hapus artikel ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
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
    <style>
        .blog-form textarea { font-family: inherit; resize: vertical; }
        .current-image { border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .table-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 10px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .btn-save { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 10px; cursor: pointer; }
        .btn-cancel { color: #6c757d; text-decoration: none; padding: 12px 30px; border: 2px solid #e9ecef; border-radius: 10px; }
        .btn-edit { background: #007bff; color: white; }
        .btn-delete { background: #dc3545; color: white; }
    </style>
</body>
</html>
