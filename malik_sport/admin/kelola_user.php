<?php 
require_once '../config/koneksi.php';

// KEAMANAN: Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Proses tambah user baru
if (isset($_POST['tambah_user'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    try {
        $stmt = $koneksi->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $password, $role]);
        $success = "User baru berhasil ditambahkan!";
    } catch (PDOException $e) {
        $error = "Email sudah terdaftar atau error: " . $e->getMessage();
    }
}

// Proses ubah role
if (isset($_GET['toggle_role']) && is_numeric($_GET['toggle_role'])) {
    $user_id = $_GET['toggle_role'];
    $stmt = $koneksi->prepare("SELECT role FROM users WHERE id = ? AND id != ?");
    $stmt->execute([$user_id, $_SESSION['user_id']]); // Tidak boleh ubah diri sendiri
    $current_user = $stmt->fetch();
    
    if ($current_user) {
        $new_role = $current_user['role'] == 'admin' ? 'user' : 'admin';
        $koneksi->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $user_id]);
        $success = "Role user berhasil diubah!";
    }
}

// Proses hapus user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    // Tidak boleh hapus diri sendiri
    if ($delete_id != $_SESSION['user_id']) {
        $koneksi->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);
        $success = "User berhasil dihapus!";
    }
}

// Ambil semua user (kecuali admin sendiri untuk aksi)
$stmt = $koneksi->query("SELECT * FROM users ORDER BY role DESC, created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Malik Sport</title>
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
                <a href="kelola_lapangan.php" class="nav-item"><i class="fas fa-table-tennis"></i> Lapangan</a>
                <a href="kelola_user.php" class="nav-item active"><i class="fas fa-users"></i> Kelola User</a>
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
                <!-- Message -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?> <button class="close-btn">&times;</button></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?> <button class="close-btn">&times;</button></div>
                <?php endif; ?>

                <!-- Form Tambah User -->
                <div class="form-container">
                    <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
                    <form method="POST" class="user-form">
                        <input type="hidden" name="tambah_user" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nama Lengkap</label>
                                <input type="text" name="nama" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Password</label>
                                <input type="password" name="password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user-tag"></i> Role</label>
                                <select name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus"></i> Tambah User
                        </button>
                    </form>
                </div>

                <!-- Tabel User -->
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-list"></i> Daftar User (<?php echo count($users); ?>)</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user_data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user_data['nama']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user_data['role']; ?>">
                                            <i class="fas fa-<?php echo $user_data['role']=='admin' ? 'crown' : 'user'; ?>"></i>
                                            <?php echo ucfirst($user_data['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user_data['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                                            <a href="?toggle_role=<?php echo $user_data['id']; ?>" 
                                               class="btn-action btn-toggle" 
                                               onclick="return confirm('Ubah role <?php echo $user_data['nama']; ?> menjadi <?php echo $user_data['role']=='admin' ? 'User' : 'Admin'; ?>?')">
                                                <i class="fas fa-exchange-alt"></i> 
                                                <?php echo $user_data['role']=='admin' ? 'Jadikan User' : 'Jadikan Admin'; ?>
                                            </a>
                                            <a href="?delete=<?php echo $user_data['id']; ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirm('Yakin hapus user <?php echo $user_data['nama']; ?>? Data booking tidak akan terhapus!')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
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
    <style>
        /* Kelola User Styles */
        .user-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .user-form select { padding: 12px; border: 2px solid #e9ecef; border-radius: 10px; background: white; }
        .role-badge { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            padding: 8px 16px; 
            border-radius: 25px; 
            font-size: 0.9em; 
            font-weight: 600; 
        }
        .role-admin { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .role-user { background: #e9ecef; color: #666; }
        .btn-toggle { 
            background: #17a2b8; 
            color: white; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            margin-right: 8px; 
        }
        .btn-toggle:hover { background: #138496; }
        .btn-delete { 
            background: #dc3545; 
            color: white; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
        }
        .btn-delete:hover { background: #c82333; }
        .text-muted { color: #999; font-style: italic; }
        @media (max-width: 768px) {
            .user-form .form-row { grid-template-columns: 1fr; }
        }
    </style>
</body>
</html>