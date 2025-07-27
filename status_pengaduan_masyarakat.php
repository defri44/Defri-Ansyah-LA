
<?php
session_start();
// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Koneksi ke database
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "pengaduan_db";

$conn = new mysqli($servername, $username, $db_password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Folder untuk foto profil
$upload_dir = 'assets/img/profile/';

// Buat direktori jika belum ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Ambil data pengguna dari database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    
    // Tetapkan path foto profil
    if (!empty($user_data['foto_profil'])) {
        $fotoprofil = $upload_dir . $user_data['foto_profil'];
        // Cek jika file foto tidak ada, gunakan default
        if (!file_exists($fotoprofil)) {
            $fotoprofil = 'gambar/default.png';
        }
    } else {
        $fotoprofil = 'gambar/default.png';
    }
} else {
    $msg = "Data pengguna tidak ditemukan.";
    $msgType = "error";
    $fotoprofil = 'gambar/default.png';
}

// Proses pengiriman umpan balik
if (isset($_POST['submit_feedback'])) {
    $id_pengaduan = $_POST['id_pengaduan'];
    $user_id = $_SESSION['user_id'];
    $komentar = $_POST['komentar'];
    $rating = $_POST['rating'];
    $tanggal = date('Y-m-d H:i:s');
    
    // Cek apakah user sudah memberikan umpan balik sebelumnya
    $check_sql = "SELECT * FROM umpan_balik WHERE user_id = ? AND id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $id_pengaduan);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update umpan balik yang sudah ada
        $update_sql = "UPDATE umpan_balik SET komentar = ?, rating = ?, tanggal = ? WHERE user_id = ? AND id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sisii", $komentar, $rating, $tanggal, $user_id, $id_pengaduan);
        
        if ($update_stmt->execute()) {
            $feedback_msg = "Umpan balik berhasil diperbarui.";
            $feedback_msgType = "success";
        } else {
            $feedback_msg = "Gagal memperbarui umpan balik: " . $conn->error;
            $feedback_msgType = "error";
        }
    } else {
        // Masukkan umpan balik baru
        $insert_sql = "INSERT INTO umpan_balik (id, user_id, komentar, rating, tanggal) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iisis", $id_pengaduan, $user_id, $komentar, $rating, $tanggal);
        
        if ($insert_stmt->execute()) {
            $feedback_msg = "Terima kasih atas umpan balik Anda.";
            $feedback_msgType = "success";
            
            // Setelah feedback berhasil disimpan, redirect ke halaman yang sama
            header("Location: " . $_SERVER['PHP_SELF'] . "?view=history");
            exit();
        } else {
            $feedback_msg = "Gagal menyimpan umpan balik: " . $conn->error;
            $feedback_msgType = "error";
        }
    }
}

// Set filter untuk tampilan riwayat
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'active';

// Ambil data pengaduan berdasarkan user yang login
$pengaduan_sql = "SELECT p.*, sp.status, sp.id_status, sp.keterangan, sp.bukti_selesai, sp.bukti_proses, sp.created_at as status_date,
                 (SELECT COUNT(*) FROM umpan_balik ub WHERE ub.id = p.id_pengaduan AND ub.user_id = ?) as has_feedback,
                 (SELECT ub.komentar FROM umpan_balik ub WHERE ub.id = p.id_pengaduan AND ub.user_id = ?) as feedback_komentar,
                 (SELECT ub.rating FROM umpan_balik ub WHERE ub.id = p.id_pengaduan AND ub.user_id = ?) as feedback_rating,
                 (SELECT ub.tanggal FROM umpan_balik ub WHERE ub.id = p.id_pengaduan AND ub.user_id = ?) as feedback_tanggal
                 FROM pengaduan p
                 LEFT JOIN status_pengaduan sp ON p.id_pengaduan = sp.id_pengaduan
                 WHERE p.nik = ?
                 ORDER BY p.created_at DESC";

// PERBAIKAN: Eksekusi query yang hilang
$pengaduan_stmt = $conn->prepare($pengaduan_sql);
$pengaduan_stmt->bind_param("iiiis", $user_id, $user_id, $user_id, $user_id, $user_data['nik']);
$pengaduan_stmt->execute();
$pengaduan_result = $pengaduan_stmt->get_result();

$active_pengaduan = [];
$history_pengaduan = [];

// Sekarang $pengaduan_result sudah terdefinisi dan dapat digunakan
while($row = $pengaduan_result->fetch_assoc()) {
    // Pengaduan masuk ke riwayat jika:
    // 1. Status selesai DAN sudah diberi feedback
    // 2. Status ditolak DAN sudah diberi feedback
    if(($row['status'] == 'selesai' || $row['status'] == 'ditolak') && $row['has_feedback'] > 0) {
        $history_pengaduan[] = $row;
    } else {
        // Pengaduan masuk ke aktif jika:
        // 1. Status menunggu, diproses, disetujui
        // 2. Status selesai/ditolak tapi belum diberi feedback
        $active_pengaduan[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Pengaduan - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
  <link rel="icon" type="image/png" href="gambar/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <style>
    :root {
      --primary: #F39C12;
      --secondary: #34495E;
      --accent: #3498DB;
      --light: #ECF0F1;
      --dark: #2C3E50;
      --success: #2ECC71;
      --danger: #E74C3C;
      --warning: #F1C40F;
      --sidebar-w: 250px;
      --header-h: 70px;
      --card-radius: 8px;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7fa;
      color: #333;
      position: relative;
      min-height: 100vh;
    }
    
    /* Sidebar */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: var(--sidebar-w);
      height: 100%;
      background-color: var(--secondary);
      z-index: 1000;
      box-shadow: var(--shadow);
      transition: var(--transition);
      overflow-y: auto;
    }
    
    .sidebar.collapsed {
      transform: translateX(-100%);
    }
    
    .sidebar-header {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-logo {
      max-width: 150px;
      margin-bottom: 15px;
    }
    
    .sidebar-user {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 15px 0;
    }
    
    .sidebar-user-img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--light);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar-user-info {
      text-align: center;
      margin-top: 10px;
    }
    
    .sidebar-user-name {
      color: white;
      font-weight: 600;
      font-size: 16px;
      margin-bottom: 3px;
    }
    
    .sidebar-user-role {
      color: rgba(255, 255, 255, 0.7);
      font-size: 13px;
    }
    
    .sidebar-divider {
      margin: 10px 20px;
      height: 1px;
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-menu {
      padding: 10px 0;
    }
    
    .menu-label {
      color: rgba(255, 255, 255, 0.5);
      font-size: 12px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 12px 20px 5px;
    }
    
    .menu-item {
      position: relative;
    }
    
    .menu-link {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .menu-link:hover, .menu-link.active {
      color: white;
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    .menu-link.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background-color: var(--primary);
    }
    
    .menu-icon {
      width: 20px;
      margin-right: 10px;
      text-align: center;
    }
    
    /* Navbar */
    .navbar {
      position: fixed;
      top: 0;
      left: var(--sidebar-w);
      right: 0;
      height: var(--header-h);
      background-color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 25px;
      box-shadow: var(--shadow);
      transition: var(--transition);
      z-index: 999;
    }
    
    .navbar.full-width {
      left: 0;
    }
    
    .navbar-left {
      display: flex;
      align-items: center;
    }
    
    .toggle-sidebar {
      background: none;
      border: none;
      color: white;
      font-size: 22px;
      cursor: pointer;
      padding: 5px;
      margin-right: 15px;
    }
    
    .navbar-title {
      font-size: 20px;
      font-weight: 600;
      color: white;
    }
    
    .navbar-right {
      display: flex;
      align-items: center;
    }
    
    .user-dropdown {
      position: relative;
      padding: 8px;
      cursor: pointer;
    }
    
    .user-dropdown-img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
    }
    
    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 60px;
      min-width: 200px;
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      z-index: 1000;
    }
    
    .dropdown-content.show {
      display: block;
    }
    
    .dropdown-panel {
      padding: 15px;
      text-align: center;
      border-bottom: 1px solid #eee;
    }
    
    .dropdown-panel img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 10px;
    }
    
    .dropdown-panel h4 {
      color: var(--dark);
      font-size: 16px;
      margin-bottom: 3px;
    }
    
    .dropdown-panel small {
      color: #777;
    }
    
    .dropdown-menu a {
      display: block;
      padding: 12px 15px;
      color: var(--dark);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .dropdown-menu a:hover {
      background-color: #f8f9fa;
    }
    
    .dropdown-menu a i {
      margin-right: 8px;
      opacity: 0.7;
    }
    
    /* Main Content */
    .main-content {
      margin-left: var(--sidebar-w);
      padding: calc(var(--header-h) + 20px) 20px 80px;
      transition: var(--transition);
    }
    
    .main-content.full-width {
      margin-left: 0;
    }
    
    /* Toggle View Buttons */
    .view-toggle {
      display: flex;
      margin-bottom: 20px;
      border-radius: var(--card-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      background-color: white;
    }
    
    .view-btn {
      flex: 1;
      padding: 15px;
      border: none;
      background-color: white;
      color: #555;
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      font-size: 14px;
      text-align: center;
    }
    
    .view-btn.active {
      background-color: var(--accent);
      color: white;
    }
    
    .view-btn:hover:not(.active) {
      background-color: #f5f5f5;
    }
    
    /* Modified Process Timeline */
    .process-timeline {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding: 15px;
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      overflow-x: auto;
    }
    
    .timeline-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      min-width: 80px;
      z-index: 1;
    }
    
    .timeline-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: #adb5bd;
      margin-bottom: 8px;
      position: relative;
      z-index: 2;
      transition: var(--transition);
    }
    
    .timeline-icon.active {
      background-color: var(--primary);
      color: white;
    }
    
    .timeline-label {
      font-size: 12px;
      font-weight: 500;
      color: #6c757d;
      text-align: center;
    }
    
    .timeline-date {
      font-size: 10px;
      color: #adb5bd;
      margin-top: 3px;
    }
    
    .timeline-step:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 20px;
      right: -50%;
      width: 100%;
      height: 2px;
      background-color: #e9ecef;
      z-index: 0;
    }
    
    .timeline-step.completed:not(:last-child)::after {
      background-color: var(--primary);
    }
    
    /* Status Cards - Horizontal Layout */
    .content-section {
      display: none;
    }
    
    .content-section.active {
      display: block;
    }
    
    .status-cards {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-bottom: 25px;
    }
    
    .status-card {
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      transition: var(--transition);
      position: relative;
      display: flex;
      flex-direction: row;
    }
    
    .status-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .status-card-left {
      flex: 0 0 250px;
      border-right: 1px solid #eee;
      padding: 20px;
    }
    
    .status-card-right {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .status-header {
      padding: 15px 20px;
      border-bottom: 1px solid #eee;
    }
    
    .status-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .status-date {
      color: #777;
      font-size: 13px;
    }
    
    .status-content {
      padding: 15px 20px;
      flex: 1;
    }
    
    .status-info {
      margin-bottom: 10px;
    }
    
    .status-label {
      font-size: 14px;
      color: #777;
      margin-bottom: 3px;
    }
    
    .status-value {
      font-size: 16px;
      color: var(--dark);
    }
    
    .status-description {
      margin-top: 10px;
      color: #555;
      font-size: 14px;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
      color: white;
      margin-bottom: 10px;
    }
    
    .status-menunggu {
      background-color: #6c757d;
    }
    
    .status-diproses {
      background-color: var(--warning);
    }
    
    .status-disetujui {
      background-color: var(--accent);
    }
    
    .status-selesai {
      background-color: var(--success);
    }
    
    .status-ditolak {
      background-color: var(--danger);
    }
    
    .status-footer {
      padding: 12px 20px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    .status-action {
      padding: 8px 12px;
      background-color: var(--accent);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 13px;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .status-action:hover {
      background-color: #2980b9;
    }
    
    .status-action.disabled {
      background-color: #ccc;
      cursor: not-allowed;
    }
    
    .status-attachment {
      margin-top: 15px;
    }
    
    /* Detail Button */
    .detail-btn {
      display: inline-block;
      padding: 8px 16px;
      background-color: var(--accent);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 13px;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      text-align: center;
    }
    
    .detail-btn:hover {
      background-color: #2980b9;
    }
    
    .attachment-link {
      display: inline-flex;
      align-items: center;
      color: var(--accent);
      text-decoration: none;
      font-size: 14px;
    }
    
    .attachment-link i {
      margin-right: 5px;
    }
    
    /* Footer */
    .footer {
      position: absolute;
      bottom: 0;
      left: var(--sidebar-w);
      right: 0;
      padding: 15px 20px;
      background-color: white;
      border-top: 1px solid #eee;
      text-align: center;
      font-size: 14px;
      color: #777;
      transition: var(--transition);
    }
    
    .footer.full-width {
      left: 0;
    }
    
    /* Detail Modal */
    .detail-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      align-items: center;
      justify-content: center;
    }
    
    .detail-modal-content {
      background-color: white;
      border-radius: var(--card-radius);
      width: 90%;
      max-width: 700px;
      max-height: 90vh;
      overflow-y: auto;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .detail-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
    }
    
    .detail-modal-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .close-detail-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #777;
    }
    
    /* Additional responsive styles */
    @media (max-width: 991px) {
      .status-card {
        flex-direction: column;
      }
      
      .status-card-left {
        flex: 0 0 auto;
        border-right: none;
        border-bottom: 1px solid #eee;
      }
    }
    
    @media (max-width: 768px) {
      .timeline-step:not(:last-child)::after {
        width: 70px;
      }
    }
    
    @media (max-width: 576px) {
      .timeline-step:not(:last-child)::after {
        width: 50px;
      }
    }
    
    /* Feedback Modal - Improved */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      align-items: center;
      justify-content: center;
    }
    
    .modal-content {
      background-color: white;
      border-radius: var(--card-radius);
      width: 90%;
      max-width: 500px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
    }
    
    .modal-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .close-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #777;
    }
    
    .modal-body {
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: var(--dark);
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--accent);
    }
    
    .rating-container {
      display: flex;
      flex-direction: row-reverse;
      justify-content: flex-end;
    }
    
    .rating-container input {
      display: none;
    }
    
    .rating-container label {
      font-size: 25px;
      color: #ddd;
      cursor: pointer;
      margin: 0 2px;
    }
    
    .rating-container label:hover,
    .rating-container label:hover ~ label,
    .rating-container input:checked ~ label {
      color: var(--warning);
    }
    
    .modal-footer {
      display: flex;
      justify-content: flex-end;
    }
    
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .btn-primary {
      background-color: var(--primary);
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #E67E22;
    }
    
    .btn-secondary {
      background-color: #e9ecef;
      color: var(--dark);
      margin-right: 10px;
    }
    
    .btn-secondary:hover {
      background-color: #dee2e6;
    }
    
    /* Feedback Display */
    .feedback-display {
      margin-top: 15px;
      padding: 12px 15px;
      background-color: #f8f9fa;
      border-radius: 4px;
      border-left: 3px solid var(--accent);
    }
    
    .feedback-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
    }
    
    .feedback-stars {
      color: var(--warning);
    }
    
    .feedback-text {
      font-size: 14px;
      color: #555;
    }
    
    /* Alert Message */
    .alert {
      padding: 12px 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .alert-success {
      background-color: rgba(46, 204, 113, 0.1);
      border-left: 3px solid var(--success);
      color: #2ecc71;
    }
    
    .alert-error {
      background-color: rgba(231, 76, 60, 0.1);
      border-left: 3px solid var(--danger);
      color: #e74c3c;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #777;
    }
    
    .empty-state i {
      font-size: 60px;
      margin-bottom: 20px;
      opacity: 0.3;
    }
    
    .empty-state h3 {
      font-size: 18px;
      margin-bottom: 10px;
      color: var(--dark);
    }
    .empty-state h3 {
      font-size: 18px;
      margin-bottom: 10px;
      color: var(--dark);
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <img src="gambar/dinsos.png" alt="Logo Dinas Sosial Kota Palembang" class="sidebar-logo">
    </div>
    
    <div class="sidebar-user">
      <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" class="sidebar-user-img" onerror="this.src='gambar/default.png'">
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?php echo $_SESSION['user_name']; ?></div>
        <div class="sidebar-user-role"><?php echo $_SESSION['user_role']; ?></div>
      </div>
    </div>
    
    <div class="sidebar-divider"></div>
    
    <div class="sidebar-menu">
      <div class="menu-label">Main Navigation</div>
      
      <div class="menu-item">
        <a href="dashboard_masyarakat.php" class="menu-link">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
        </a>
      </div>
      
      <div class="menu-item">
        <a href="pengaduan.php" class="menu-link">
          <i class="fas fa-bullhorn menu-icon"></i>
          <span>Buat Pengaduan</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="status_pengaduan_masyarakat.php" class="menu-link active">
          <i class="fas fa-file-alt menu-icon"></i>
          <span>Status Pengaduan</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="data_akun.php" class="menu-link">
          <i class="fas fa-user-cog menu-icon"></i>
          <span>Akun</span>
        </a>
      </div>
      
      <div class="sidebar-divider"></div>
      
      <div class="menu-item">
        <a href="#" class="menu-link" onclick="showLogoutModal()">
          <i class="fas fa-sign-out-alt menu-icon"></i>
          <span>Keluar</span>
        </a>
      </div>
    </div>
  </aside>
  
  <!-- Navbar -->
  <div class="navbar" id="navbar">
    <div class="navbar-left">
      <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h2 class="navbar-title">Status Pengaduan</h2>
    </div>
    <div class="navbar-right">
      <div class="user-dropdown" id="userDropdown">
        <img src="<?php echo $fotoprofil; ?>" alt="User Profile" class="user-dropdown-img">
        <div class="dropdown-content" id="dropdownContent">
          <div class="dropdown-panel">
            <img src="<?php echo $fotoprofil; ?>" alt="User Profile">
            <h4><?php echo $user_data['nama']; ?></h4>
            <small><?php echo $user_data['nik']; ?></small>
          </div>
          <div class="dropdown-menu">
            <a href="data_akun.php"><i class="fas fa-user"></i> Profil Saya</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <?php if(isset($feedback_msg)): ?>
      <div class="alert <?php echo $feedback_msgType == 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $feedback_msg; ?>
      </div>
    <?php endif; ?>
    
    <div class="view-toggle">
      <button class="view-btn <?php echo $view_mode == 'active' ? 'active' : ''; ?>" data-view="active">Pengaduan Aktif</button>
      <button class="view-btn <?php echo $view_mode == 'history' ? 'active' : ''; ?>" data-view="history">Riwayat Pengaduan</button>
    </div>
    
    <!-- Active Complaints Section -->
<div class="content-section <?php echo $view_mode == 'active' ? 'active' : ''; ?>" id="activeSection">
  <div class="status-cards">
    <?php if(count($active_pengaduan) > 0): ?>
      <?php foreach($active_pengaduan as $pengaduan): ?>
        <div class="status-card">
          <div class="status-card-left">
            <h3 class="status-title"><?php echo $pengaduan['judul']; ?></h3>
            <p class="status-date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y', strtotime($pengaduan['created_at'])); ?></p>
            
            <?php
            $status_class = 'status-menunggu';
            if($pengaduan['status'] == 'diproses') {
              $status_class = 'status-diproses';
            } elseif($pengaduan['status'] == 'disetujui') {
              $status_class = 'status-disetujui';
            } elseif($pengaduan['status'] == 'selesai') {
              $status_class = 'status-selesai';
            } elseif($pengaduan['status'] == 'ditolak') {
              $status_class = 'status-ditolak';
            }
            ?>
            
            <span class="status-badge <?php echo $status_class; ?>">
              <?php echo ucfirst($pengaduan['status']); ?>
            </span>
            
            <div class="process-timeline">
              <?php if($pengaduan['status'] == 'ditolak'): ?>
                <!-- Timeline untuk status ditolak - hanya 2 step -->
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <p class="timeline-label">Diajukan</p>
                  <small class="timeline-date"><?php echo date('d/m/y', strtotime($pengaduan['created_at'])); ?></small>
                </div>
                
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-times-circle"></i>
                  </div>
                  <p class="timeline-label">Ditolak</p>
                  <small class="timeline-date">
                    <?php echo !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?>
                  </small>
                </div>
                
              <?php else: ?>
                <!-- Timeline normal untuk status lainnya -->
                <?php 
                $diajukan_active = true;
                $disetujui_active = in_array($pengaduan['status'], ['disetujui', 'diproses', 'selesai']);
                $diproses_active = in_array($pengaduan['status'], ['diproses', 'selesai']);
                $selesai_active = $pengaduan['status'] == 'selesai';
                ?>
                
                <div class="timeline-step <?php echo $diajukan_active ? 'completed' : ''; ?>">
                  <div class="timeline-icon <?php echo $diajukan_active ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <p class="timeline-label">Diajukan</p>
                  <small class="timeline-date"><?php echo date('d/m/y', strtotime($pengaduan['created_at'])); ?></small>
                </div>
                
                <div class="timeline-step <?php echo $disetujui_active ? 'completed' : ''; ?>">
                  <div class="timeline-icon <?php echo $disetujui_active ? 'active' : ''; ?>">
                    <i class="fas fa-check"></i>
                  </div>
                  <p class="timeline-label">Disetujui</p>
                  <small class="timeline-date">
                    <?php echo $disetujui_active && !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?>
                  </small>
                </div>
                
                <div class="timeline-step <?php echo $diproses_active ? 'completed' : ''; ?>">
                  <div class="timeline-icon <?php echo $diproses_active ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i>
                  </div>
                  <p class="timeline-label">Diproses</p>
                  <small class="timeline-date">
                    <?php echo $diproses_active && !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?>
                  </small>
                </div>
                
                <div class="timeline-step <?php echo $selesai_active ? 'completed' : ''; ?>">
                  <div class="timeline-icon <?php echo $selesai_active ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <p class="timeline-label">Selesai</p>
                  <small class="timeline-date">
                    <?php echo $selesai_active && !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?>
                  </small>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="status-card-right">
            <div class="status-header">
              <h3>Informasi Pengaduan</h3>
            </div>
            
            <div class="status-content">
              <div class="status-info">
                <p class="status-label">ID Pengaduan:</p>
                <p class="status-value"><?php echo $pengaduan['id_pengaduan']; ?></p>
              </div>
              
              <div class="status-info">
                <p class="status-label">Kategori:</p>
                <p class="status-value"><?php echo $pengaduan['kategori']; ?></p>
              </div>
              
              <div class="status-info">
                <p class="status-label">Status Terakhir:</p>
                <p class="status-value"><?php echo ucfirst($pengaduan['status']); ?></p>
              </div>
              
              <?php if(!empty($pengaduan['keterangan'])): ?>
              <div class="status-info">
                <p class="status-label">Keterangan:</p>
                <p class="status-description"><?php echo $pengaduan['keterangan']; ?></p>
              </div>
              <?php endif; ?>

              <?php if(!empty($pengaduan['bukti_proses'])): ?>
                <div class="status-attachment">
                  <p class="status-label">Bukti Proses:</p>
                  <a href="uploads/bukti_proses/<?php echo htmlspecialchars($pengaduan['bukti_proses']); ?>" class="attachment-link" target="_blank">
                    <i class="fas fa-paperclip"></i> Lihat Bukti Proses
                  </a>
                </div>
                <?php endif; ?>
              
              <?php if(!empty($pengaduan['bukti_selesai'])): ?>
              <div class="status-attachment">
                <p class="status-label">Bukti Pengerjaan:</p>
                <a href="uploads/bukti/<?php echo $pengaduan['bukti_selesai']; ?>" class="attachment-link" target="_blank">
                  <i class="fas fa-paperclip"></i> Lihat Bukti
                </a>
              </div>
              <?php endif; ?>
            </div>
            
            <div class="status-footer">
              <?php if($pengaduan['status'] == 'selesai' && $pengaduan['has_feedback'] == 0): ?>
              <button class="status-action" onclick="openFeedbackModal(<?php echo $pengaduan['id_pengaduan']; ?>)">
                <i class="fas fa-star"></i> Beri Penilaian
              </button>
              <?php elseif($pengaduan['status'] == 'ditolak' && $pengaduan['has_feedback'] == 0): ?>
              <button class="status-action" onclick="openFeedbackModal(<?php echo $pengaduan['id_pengaduan']; ?>)">
                <i class="fas fa-star"></i> Beri Penilaian
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Tidak ada pengaduan aktif</h3>
        <p>Anda belum memiliki pengaduan aktif saat ini.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
    
 <!-- History Section -->
<div class="content-section <?php echo $view_mode == 'history' ? 'active' : ''; ?>" id="historySection">
  <div class="status-cards">
    <?php if(count($history_pengaduan) > 0): ?>
      <?php foreach($history_pengaduan as $pengaduan): ?>
        <div class="status-card">
          <div class="status-card-left">
            <h3 class="status-title"><?php echo htmlspecialchars($pengaduan['judul']); ?></h3>
            <p class="status-date"><i class="far fa-calendar-alt"></i> <?php echo date('d F Y', strtotime($pengaduan['created_at'])); ?></p>
            
            <!-- Status badge dihilangkan untuk riwayat -->
            
            <div class="process-timeline">
              <?php if($pengaduan['status'] == 'ditolak'): ?>
                <!-- Timeline untuk status ditolak - hanya 2 step -->
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <p class="timeline-label">Diajukan</p>
                  <small class="timeline-date"><?php echo date('d/m/y', strtotime($pengaduan['created_at'])); ?></small>
                </div>
                
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-times-circle"></i>
                  </div>
                  <p class="timeline-label">Ditolak</p>
                  <small class="timeline-date"><?php echo !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?></small>
                </div>
                
              <?php else: ?>
                <!-- Timeline normal untuk status selesai -->
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <p class="timeline-label">Diajukan</p>
                  <small class="timeline-date"><?php echo date('d/m/y', strtotime($pengaduan['created_at'])); ?></small>
                </div>
                
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-check"></i>
                  </div>
                  <p class="timeline-label">Disetujui</p>
                  <small class="timeline-date"><?php echo !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?></small>
                </div>
                
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-cogs"></i>
                  </div>
                  <p class="timeline-label">Diproses</p>
                  <small class="timeline-date"><?php echo !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?></small>
                </div>
                
                <div class="timeline-step completed">
                  <div class="timeline-icon active">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <p class="timeline-label">Selesai</p>
                  <small class="timeline-date"><?php echo !empty($pengaduan['status_date']) ? date('d/m/y', strtotime($pengaduan['status_date'])) : '-'; ?></small>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- Feedback Display untuk semua pengaduan di riwayat (termasuk yang ditolak) -->
            <?php if($pengaduan['has_feedback'] > 0): ?>
            <div class="feedback-display">
              <div class="feedback-header">
                <div class="feedback-stars">
                  <?php 
                  $rating = (int)$pengaduan['feedback_rating'];
                  for($i = 1; $i <= 5; $i++) {
                    if($i <= $rating) {
                      echo '<i class="fas fa-star"></i>';
                    } else {
                      echo '<i class="far fa-star"></i>';
                    }
                  }
                  ?>
                </div>
                <div class="feedback-date">
                  <?php echo !empty($pengaduan['feedback_tanggal']) ? date('d F Y', strtotime($pengaduan['feedback_tanggal'])) : '-'; ?>
                </div>
              </div>
              <?php if(!empty($pengaduan['feedback_komentar'])): ?>
              <p class="feedback-text"><?php echo htmlspecialchars($pengaduan['feedback_komentar']); ?></p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="status-card-right">
            <div class="status-header">
              <h3>Informasi Pengaduan</h3>
            </div>
            
            <div class="status-content">
              <div class="status-info">
                <p class="status-label">ID Pengaduan:</p>
                <p class="status-value"><?php echo $pengaduan['id_pengaduan']; ?></p>
              </div>
              
              <div class="status-info">
                <p class="status-label">Kategori:</p>
                <p class="status-value"><?php echo htmlspecialchars($pengaduan['kategori']); ?></p>
              </div>
              
              <!-- Status dihilangkan dari bagian informasi -->
              
              <?php if(!empty($pengaduan['keterangan'])): ?>
              <div class="status-info">
                <p class="status-label">Keterangan:</p>
                <p class="status-description"><?php echo htmlspecialchars($pengaduan['keterangan']); ?></p>
              </div>
              <?php endif; ?>

              <?php if(!empty($pengaduan['bukti_proses'])): ?>
<div class="status-attachment">
  <p class="status-label">Bukti Proses:</p>
  <a href="uploads/bukti_proses/<?php echo htmlspecialchars($pengaduan['bukti_proses']); ?>" class="attachment-link" target="_blank">
    <i class="fas fa-paperclip"></i> Lihat Bukti Proses
  </a>
</div>
<?php endif; ?>
              
              <?php if(!empty($pengaduan['bukti_selesai'])): ?>
              <div class="status-attachment">
                <p class="status-label">Bukti Pengerjaan:</p>
                <a href="uploads/bukti/<?php echo htmlspecialchars($pengaduan['bukti_selesai']); ?>" class="attachment-link" target="_blank">
                  <i class="fas fa-paperclip"></i> Lihat Bukti
                </a>
              </div>
              <?php endif; ?>
              
              <!-- Tambahkan informasi hasil/alasan jika ada -->
              <?php if($pengaduan['status'] == 'ditolak' && !empty($pengaduan['alasan_penolakan'])): ?>
              <div class="status-info">
                <p class="status-label">Alasan Penolakan:</p>
                <p class="status-description"><?php echo htmlspecialchars($pengaduan['alasan_penolakan']); ?></p>
              </div>
              <?php endif; ?>
            </div>
            
            <div class="status-footer">
              <?php if($pengaduan['has_feedback'] > 0): ?>
              <button class="status-action" onclick="openEditFeedbackModal(<?php echo $pengaduan['id_pengaduan']; ?>, '<?php echo htmlspecialchars($pengaduan['feedback_komentar'], ENT_QUOTES); ?>', <?php echo $pengaduan['feedback_rating']; ?>)">
                <i class="fas fa-edit"></i> Edit Penilaian
              </button>
              <?php else: ?>
              <!-- Tombol beri penilaian untuk pengaduan yang belum diberi feedback -->
              <button class="status-action" onclick="openFeedbackModal(<?php echo $pengaduan['id_pengaduan']; ?>)">
                <i class="fas fa-star"></i> Beri Penilaian
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-history"></i>
        <h3>Belum ada riwayat pengaduan</h3>
        <p>Riwayat pengaduan akan muncul setelah pengaduan Anda selesai diproses atau ditolak.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
  </div>
  
  <!-- Feedback Modal -->
  <div class="modal" id="feedbackModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Beri Penilaian</h3>
        <button type="button" class="close-modal" onclick="closeFeedbackModal()">&times;</button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pengaduan" id="feedback_id_pengaduan">
          
          <div class="form-group">
            <label class="form-label">Rating:</label>
            <div class="rating-container">
              <input type="radio" id="star5" name="rating" value="5">
              <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="star4" name="rating" value="4">
              <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="star3" name="rating" value="3">
              <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="star2" name="rating" value="2">
              <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="star1" name="rating" value="1">
              <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="komentar">Komentar:</label>
            <textarea class="form-control" id="komentar" name="komentar" rows="4" placeholder="Tuliskan komentar Anda tentang pengaduan ini..."></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">Batal</button>
          <button type="submit" name="submit_feedback" class="btn btn-primary">Kirim Penilaian</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Edit Feedback Modal -->
  <div class="modal" id="editFeedbackModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Penilaian</h3>
        <button type="button" class="close-modal" onclick="closeEditFeedbackModal()">&times;</button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id_pengaduan" id="edit_feedback_id_pengaduan">
          
          <div class="form-group">
            <label class="form-label">Rating:</label>
            <div class="rating-container">
              <input type="radio" id="edit_star5" name="rating" value="5">
              <label for="edit_star5" title="5 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="edit_star4" name="rating" value="4">
              <label for="edit_star4" title="4 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="edit_star3" name="rating" value="3">
              <label for="edit_star3" title="3 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="edit_star2" name="rating" value="2">
              <label for="edit_star2" title="2 stars"><i class="fas fa-star"></i></label>
              
              <input type="radio" id="edit_star1" name="rating" value="1">
              <label for="edit_star1" title="1 star"><i class="fas fa-star"></i></label>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="edit_komentar">Komentar:</label>
            <textarea class="form-control" id="edit_komentar" name="komentar" rows="4" placeholder="Tuliskan komentar Anda tentang pengaduan ini..."></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeEditFeedbackModal()">Batal</button>
          <button type="submit" name="submit_feedback" class="btn btn-primary">Perbarui Penilaian</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Footer -->
  <div class="footer" id="footer">
    <p>&copy; <?php echo date('Y'); ?> SILADU - Sistem Layanan Terpadu. All rights reserved.</p>
  </div>
  
  <script>
    // Toggle Sidebar
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const navbar = document.getElementById('navbar');
    const mainContent = document.getElementById('mainContent');
    const footer = document.getElementById('footer');
    
    toggleSidebar.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      navbar.classList.toggle('full-width');
      mainContent.classList.toggle('full-width');
      footer.classList.toggle('full-width');
    });
    
    // User Dropdown
    const userDropdown = document.getElementById('userDropdown');
    const dropdownContent = document.getElementById('dropdownContent');
    
    userDropdown.addEventListener('click', function() {
      dropdownContent.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
      if (!event.target.matches('.user-dropdown') && !event.target.matches('.user-dropdown-img')) {
        if (dropdownContent.classList.contains('show')) {
          dropdownContent.classList.remove('show');
        }
      }
    });
    
    // Toggle View
    const viewButtons = document.querySelectorAll('.view-btn');
    const contentSections = document.querySelectorAll('.content-section');
    
    viewButtons.forEach(button => {
      button.addEventListener('click', function() {
        const view = this.getAttribute('data-view');
        
        viewButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        contentSections.forEach(section => section.classList.remove('active'));
        document.getElementById(view + 'Section').classList.add('active');
      });
    });
    
    // Feedback Modal Functions
    const feedbackModal = document.getElementById('feedbackModal');
    const editFeedbackModal = document.getElementById('editFeedbackModal');
    
    function openFeedbackModal(id) {
      document.getElementById('feedback_id_pengaduan').value = id;
      feedbackModal.style.display = 'flex';
    }
    
    function closeFeedbackModal() {
      feedbackModal.style.display = 'none';
    }
    
    function openEditFeedbackModal(id, komentar, rating) {
      document.getElementById('edit_feedback_id_pengaduan').value = id;
      document.getElementById('edit_komentar').value = komentar;
      document.getElementById('edit_star' + rating).checked = true;
      editFeedbackModal.style.display = 'flex';
    }
    
    function closeEditFeedbackModal() {
      editFeedbackModal.style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target == feedbackModal) {
        closeFeedbackModal();
      }
      if (event.target == editFeedbackModal) {
        closeEditFeedbackModal();
      }
    });
  </script>
</body>
</html>