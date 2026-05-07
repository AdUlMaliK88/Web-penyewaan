<?php 
require_once '../config/koneksi.php';
require_once '../config/functions.php'; // Jika sudah ada

// KEAMANAN: Pengecekan session user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php?redirect=pembayaran');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header('Location: dashboard.php');
    exit;
}

$booking_id = $_GET['booking_id'];

// Ambil data booking
$stmt = $koneksi->prepare("
    SELECT jb.*, l.nama_lapangan, l.kategori, l.harga_per_jam, u.nama as user_nama
    FROM jadwal_booking jb
    JOIN lapangan l ON jb.lapangan_id = l.id
    JOIN users u ON jb.user_id = u.id
    WHERE jb.id = ? AND jb.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: dashboard.php');
    exit;
}

// PROSES UPLOAD - FIXED ✅
$upload_success = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['bukti_transfer'])) {
    $upload_dir = '../assets/images/bukti/';
    
    // ✅ BUAT FOLDER jika belum ada
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['bukti_transfer'];
    
    // ✅ VALIDASI FILE LENGKAP
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file_ext, $allowed_types) && $file['size'] <= $max_size) {
            // ✅ UNIQUE FILENAME
            $file_name = 'bukti_' . $booking_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            // ✅ UPLOAD FILE
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // ✅ INSERT DATABASE
                $stmt = $koneksi->prepare("
                    INSERT INTO pembayaran (booking_id, bukti_transfer, tanggal_bayar, status_pembayaran) 
                    VALUES (?, ?, NOW(), 'pending')
                ");
                if ($stmt->execute([$booking_id, $file_name])) {
                    // ✅ UPDATE STATUS BOOKING
                    $stmt = $koneksi->prepare("UPDATE jadwal_booking SET status_booking = 'dibayar' WHERE id = ?");
                    $stmt->execute([$booking_id]);
                    
                    $upload_success = true;
                } else {
                    $error = "Gagal simpan data pembayaran!";
                    unlink($file_path); // Hapus file jika DB gagal
                }
            } else {
                $error = "Gagal upload file! Cek permission folder bukti.";
            }
        } else {
            $error = "File harus JPG, PNG, PDF (max 5MB)!";
        }
    } else {
        $error = "Error upload: " . $file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran #<?php echo $booking_id; ?> - Malik Sport</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php"><i class="fas fa-futbol"></i> Malik Sport</a>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php" class="nav-link">Home</a></li>
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="../logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="padding-top: 120px;">
        <a href="dashboard.php" class="back-btn" style="margin-bottom: 30px;">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="pembayaran-container" style="max-width: 700px; margin: 0 auto;">
            <h1 style="text-align: center; padding: 30px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 20px 20px 0 0;">
                <i class="fas fa-receipt"></i> Booking #<?php echo $booking_id; ?>
            </h1>

            <?php if ($upload_success): ?>
                <!-- ✅ SUCCESS - NOTIF & REDIRECT -->
                <div class="alert alert-success" style="text-align: center; font-size: 1.2em;">
                    <i class="fas fa-check-circle" style="font-size: 3em; color: #28a745; margin-bottom: 15px;"></i>
                    <h2>✅ Bukti Pembayaran Berhasil Diupload!</h2>
                    <p>Menunggu validasi admin (biasanya 1-2 jam).</p>
                    <p><strong>Status Booking: <span style="color: #17a2b8;">DIBAYAR</span></strong></p>
                    <a href="dashboard.php" class="btn-bayar" style="display: inline-block; padding: 15px 40px; margin-top: 20px; font-size: 1.1em;">
                        <i class="fas fa-home"></i> Lihat Dashboard
                    </a>
                </div>
            <?php else: ?>
                <!-- ERROR -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- DETAIL BOOKING -->
                <div class="booking-detail" style="background: #f8f9fa; padding: 30px; border-radius: 15px; margin-bottom: 30px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 1.1em;">
                        <div><strong>Lapangan:</strong><br><?php echo htmlspecialchars($booking['nama_lapangan']); ?></div>
                        <div><strong>Tanggal:</strong><br><?php echo date('d/m/Y', strtotime($booking['tanggal_main'])); ?></div>
                        <div><strong>Jam:</strong><br><?php echo date('H:i', strtotime($booking['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($booking['jam_selesai'])); ?></div>
                        <div><strong>Total:</strong><br><span style="color: #ff6b6b; font-size: 1.3em;">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></span></div>
                    </div>
                </div>

                <!-- FORM UPLOAD -->
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-upload" style="color: #28a745;"></i> 
                            Upload Bukti Transfer
                        </label>
                        <input type="file" name="bukti_transfer" accept="image/*,.pdf" required 
                               style="width: 100%; padding: 15px; border: 2px dashed #667eea; border-radius: 12px; background: #f8f9fa;">
                        <small style="color: #666; margin-top: 8px; display: block;">
                            📱 JPG, PNG, PDF | Max 5MB | Screenshot transfer BCA a/n Malik Sport
                        </small>
                    </div>
                    
                    <div class="bank-info" style="background: #e8f5e8; padding: 20px; border-radius: 12px; border-left: 5px solid #28a745; margin-bottom: 25px;">
                        <strong style="color: #155724;">💳 Rekening Tujuan:</strong><br>
                        <strong>Bank BCA 1234-5678-90</strong><br>
                        a/n **MALIK SPORT**<br>
                        <small>📝 Cantumkan ID Booking #<?php echo $booking_id; ?> di berita transfer</small>
                    </div>
                    
                    <button type="submit" class="btn-upload" style="width: 100%; padding: 18px; font-size: 1.1em;">
                        <i class="fas fa-paper-plane"></i> Kirim Bukti Pembayaran
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto redirect ke dashboard setelah 5 detik (success)
        <?php if ($upload_success): ?>
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>