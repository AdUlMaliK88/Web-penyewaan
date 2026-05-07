<?php 
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$upload_dir = '../assets/images/lapangan/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
if ($_POST) {
    $nama_lapangan = trim($_POST['nama_lapangan']);
    $kategori = $_POST['kategori'];
    $harga_per_jam = (float)$_POST['harga_per_jam'];
    $deskripsi = $_POST['deskripsi'];
    
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $file = $_FILES['foto'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed) && $file['size'] <= 3000000) { // 3MB
            $foto = 'lapangan_' . time() . '_' . rand(1000,9999) . '.' . $file_ext;
            $file_path = $upload_dir . $foto;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                if (isset($_POST['edit_id'])) {
                    $old_foto = $_POST['old_foto'];
                    if ($old_foto && file_exists($upload_dir . $old_foto)) {
                        unlink($upload_dir . $old_foto);
                    }
                }
            }
        }
    }
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        if ($foto) {
            $stmt = $koneksi->prepare("UPDATE lapangan SET nama_lapangan=?, kategori=?, harga_per_jam=?, foto=?, deskripsi=? WHERE id=?");
            $stmt->execute([$nama_lapangan, $kategori, $harga_per_jam, $foto, $deskripsi, $_POST['edit_id']]);
        } else {
            $stmt = $koneksi->prepare("UPDATE lapangan SET nama_lapangan=?, kategori=?, harga_per_jam=?, deskripsi=? WHERE id=?");
            $stmt->execute([$nama_lapangan, $kategori, $harga_per_jam, $deskripsi, $_POST['edit_id']]);
        }
        $message = "Lapangan berhasil diupdate!";
        $message_type = "success";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO lapangan (nama_lapangan, kategori, harga_per_jam, foto, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama_lapangan, $kategori, $harga_per_jam, $foto, $deskripsi]);
        $message = "Lapangan baru berhasil ditambahkan!";
        $message_type = "success";
    }
}


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    
    $stmt = $koneksi->prepare("SELECT foto FROM lapangan WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    $lapangan = $stmt->fetch();
    
    if ($lapangan && $lapangan['foto']) {
        unlink($upload_dir . $lapangan['foto']);
    }
    
 
    $koneksi->prepare("DELETE FROM lapangan WHERE id=?")->execute([$_GET['delete']]);
    $message = "Lapangan berhasil dihapus!";
    $message_type = "warning";
}


$edit_lapangan = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $koneksi->prepare("SELECT * FROM lapangan WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_lapangan = $stmt->fetch();
}


$lapangans = $koneksi->query("SELECT * FROM lapangan ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin Malik Sport</title>
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
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_pembayaran.php" class="nav-item"><i class="fas fa-receipt"></i> Pembayaran</a>
                <a href="kelola_blog.php" class="nav-item"><i class="fas fa-blog"></i> Blog</a>
                <a href="kelola_lapangan.php" class="nav-item active"><i class="fas fa-table-tennis"></i> Lapangan</a>
                <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
                <a href="../index.php" class="nav-item" target="_blank"><i class="fas fa-globe"></i> Website</a>
                <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="toggle-sidebar"><i class="fas fa-bars"></i></div>
                <div class="user-info">
                    <span>Admin <?php echo $_SESSION['user_nama']; ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <div class="content">
                <!-- Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo isset($message_type) ? $message_type : 'success'; ?>">
                        <?php echo $message; ?>
                        <button class="close-btn">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Form Tambah/Edit -->
                <div class="form-container">
                    <h2>
                        <i class="fas fa-<?php echo $edit_lapangan ? 'edit' : 'plus'; ?>"></i> 
                        <?php echo $edit_lapangan ? 'Edit Lapangan' : 'Tambah Lapangan Baru'; ?>
                    </h2>
                    
                    <form method="POST" enctype="multipart/form-data" class="lapangan-form">
                        <?php if ($edit_lapangan): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_lapangan['id']; ?>">
                            <input type="hidden" name="old_foto" value="<?php echo $edit_lapangan['foto']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> Nama Lapangan</label>
                                <input type="text" name="nama_lapangan" required 
                                       value="<?php echo $edit_lapangan ? htmlspecialchars($edit_lapangan['nama_lapangan']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Kategori</label>
                                <select name="kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="futsal" <?php echo $edit_lapangan && $edit_lapangan['kategori']=='futsal' ? 'selected' : ''; ?>>Futsal</option>
                                    <option value="basket" <?php echo $edit_lapangan && $edit_lapangan['kategori']=='basket' ? 'selected' : ''; ?>>Basket</option>
                                    <option value="badminton" <?php echo $edit_lapangan && $edit_lapangan['kategori']=='badminton' ? 'selected' : ''; ?>>Badminton</option>
                                    <option value="minisoccer" <?php echo $edit_lapangan && $edit_lapangan['kategori']=='minisoccer' ? 'selected' : ''; ?>>Minisoccer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill"></i> Harga per Jam</label>
                                <input type="number" name="harga_per_jam" step="1000" min="0" required 
                                       value="<?php echo $edit_lapangan ? $edit_lapangan['harga_per_jam'] : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-camera"></i> Foto Lapangan</label>
                                <input type="file" name="foto" accept="image/*">
                                <?php if ($edit_lapangan && $edit_lapangan['foto']): ?>
                                    <img src="../assets/images/lapangan/<?php echo $edit_lapangan['foto']; ?>" 
                                         alt="Foto saat ini" class="current-photo">
                                    <small>Foto baru akan menggantikan foto lama</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Deskripsi</label>
                            <textarea name="deskripsi" rows="5" placeholder="Deskripsi lengkap lapangan..."><?php echo $edit_lapangan ? $edit_lapangan['deskripsi'] : ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <a href="kelola_lapangan.php" class="btn-secondary">Batal</a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $edit_lapangan ? 'Update Lapangan' : 'Tambah Lapangan'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Lapangan -->
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-list"></i> Daftar Lapangan (<?php echo count($lapangans); ?>)</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Foto</th>
                                    <th>Nama Lapangan</th>
                                    <th>Kategori</th>
                                    <th>Harga/Jam</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lapangans as $index => $lapangan): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <img src="../assets/images/lapangan/<?php echo $lapangan['foto']; ?>" 
                                             alt="<?php echo htmlspecialchars($lapangan['nama_lapangan']); ?>" 
                                             class="table-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($lapangan['nama_lapangan']); ?></td>
                                    <td>
                                        <span class="kategori-badge"><?php echo strtoupper($lapangan['kategori']); ?></span>
                                    </td>
                                    <td>Rp <?php echo number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $lapangan['id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $lapangan['id']; ?>" 
                                           class="btn-action btn-delete"
                                           onclick="return confirm('Yakin hapus lapangan "<?php echo addslashes($lapangan['nama_lapangan']); ?>"? Semua booking terkait akan hilang!')">
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
        .lapangan-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .lapangan-form select { padding: 12px; border: 2px solid #e9ecef; border-radius: 10px; }
        .current-photo { 
            max-width: 150px; 
            max-height: 100px; 
            border-radius: 10px; 
            margin-top: 10px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        }
        .kategori-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .btn-primary { 
            background: linear-gradient(45deg, #28a745, #20c997); 
            color: white; 
            padding: 14px 30px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
        }
        .btn-secondary { 
            color: #6c757d; 
            text-decoration: none; 
            padding: 14px 30px; 
            border: 2px solid #e9ecef; 
            border-radius: 10px; 
        }
        .btn-edit { background: #007bff; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; }
        .btn-delete { background: #dc3545; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; margin-left: 5px; }
    </style>
</body>
</html>
