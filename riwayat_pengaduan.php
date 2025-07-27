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

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $id_pengaduan = $_POST['id_pengaduan'];
    $rating = $_POST['rating'];
    $komentar = $_POST['komentar'];
    $tanggal = date('Y-m-d H:i:s');
    
    // Insert feedback ke database
    $feedback_sql = "INSERT INTO umpan_balik (id, rating, komentar, tanggal) VALUES (?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE rating = VALUES(rating), komentar = VALUES(komentar), tanggal = VALUES(tanggal)";
    $feedback_stmt = $conn->prepare($feedback_sql);
    $feedback_stmt->bind_param("iiss", $id_pengaduan, $rating, $komentar, $tanggal);
    
    if ($feedback_stmt->execute()) {
        $feedback_msg = "Umpan balik berhasil dikirim!";
        $feedback_msgType = "success";
    } else {
        $feedback_msg = "Gagal mengirim umpan balik: " . $conn->error;
        $feedback_msgType = "error";
    }
    
    $feedback_stmt->close();
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

// Menghitung jumlah pengaduan berdasarkan status
$count_sql = "SELECT 
    COUNT(CASE WHEN status = 'ditolak' THEN 1 END) as ditolak,
    COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
    COUNT(CASE WHEN status = 'diproses' THEN 1 END) as diproses,
    COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai
FROM status_pengaduan";

$count_result = $conn->query($count_sql);
$count_data = $count_result->fetch_assoc();

// Ambil data pengaduan berdasarkan database
$riwayat_sql = "SELECT 
    p.*,
    sp.status,
    sp.id_status,
    sp.keterangan,
    sp.bukti_selesai,
    sp.bukti_proses,
    sp.created_at as status_date,
    u.nama as nama_pelapor,
    u.nik as nik_pelapor,
    u.foto_profil as foto_pelapor,
    COALESCE(ub.komentar, '') as feedback_komentar,
    COALESCE(ub.rating, 0) as feedback_rating,
    COALESCE(ub.tanggal, '') as feedback_tanggal
FROM pengaduan p
LEFT JOIN status_pengaduan sp ON p.id_pengaduan = sp.id_pengaduan
LEFT JOIN users u ON p.nik = u.nik
LEFT JOIN umpan_balik ub ON p.id_pengaduan = ub.id
WHERE sp.status = 'selesai'
ORDER BY sp.created_at DESC";

$riwayat_stmt = $conn->prepare($riwayat_sql);
$riwayat_stmt->execute();
$riwayat_result = $riwayat_stmt->get_result();
$riwayat_pengaduan = [];

while ($row = $riwayat_result->fetch_assoc()) {
    $riwayat_pengaduan[] = $row;
}

$stmt->close();
$riwayat_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pengaduan - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
  <link rel="icon" type="image/png" href="gambar/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css">
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
      --primary-color: #3498db;    
      --primary-dark: #2980b9;
      --secondary-color: #2ecc71;
      --secondary-dark: #27ae60;
      --danger-color: #e74c3c;
      --danger-dark: #c0392b;
      --warning-color: #f39c12;
      --warning-dark: #d35400;
      --info-color: #9b59b6;
      --info-dark: #8e44ad;
      --light-color: #ecf0f1;
      --dark-color: #34495e;
      --text-color: #333;
      --text-muted: #7f8c8d;
      --border-color: #ddd;
      --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      --border-radius: 8px;
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
    .main-content {
      margin-left: var(--sidebar-w);
      padding: calc(var(--header-h) + 20px) 20px 80px;
      transition: var(--transition);
    }
    
    .main-content.full-width {
      margin-left: 0;
    }
    

.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background-color: #fff;
  border-radius: var(--border-radius);
  padding: 20px;
  box-shadow: var(--box-shadow);
  transition: var(--transition);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
}

.stat-card-1::before { background-color: var(--primary-color); }
.stat-card-2::before { background-color: var(--secondary-color); }
.stat-card-3::before { background-color: var(--warning-color); }
.stat-card-4::before { background-color: var(--danger-color); }

.stat-card-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 15px;
  font-size: 20px;
  color: #fff;
}

.stat-card-1 .stat-card-icon { background-color: var(--primary-color); }
.stat-card-2 .stat-card-icon { background-color: var(--secondary-color); }
.stat-card-3 .stat-card-icon { background-color: var(--warning-color); }
.stat-card-4 .stat-card-icon { background-color: var(--danger-color); }

.stat-card-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 10px;
}

.stat-card-value {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 5px;
}

.stat-card-desc {
  font-size: 13px;
  color: var(--text-muted);
}

/* ========== ALERTS ========== */
.alert {
  padding: 15px;
  border-radius: var(--border-radius);
  margin-bottom: 20px;
  border-left: 4px solid;
  background-color: #fff;
  box-shadow: var(--box-shadow);
}

.alert-success {
  border-color: var(--secondary-color);
  background-color: rgba(46, 204, 113, 0.1);
}

.alert-danger {
  border-color: var(--danger-color);
  background-color: rgba(231, 76, 60, 0.1);
}

.alert-warning {
  border-color: var(--warning-color);
  background-color: rgba(243, 156, 18, 0.1);
}

.alert-info {
  border-color: var(--primary-color);
  background-color: rgba(52, 152, 219, 0.1);
}

/* ========== FILTER CONTROLS ========== */
.filter-controls {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  gap: 15px;
}

.filter-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.search-box {
  position: relative;
  flex-grow: 1;
  min-width: 250px;
}

.search-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}

.search-input {
  width: 100%;
  padding: 12px 15px 12px 40px;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  font-size: 14px;
  transition: var(--transition);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.filter-select {
  padding: 11px 15px;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  background-color: #fff;
  cursor: pointer;
  min-width: 140px;
  font-size: 14px;
  transition: var(--transition);
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="6" viewBox="0 0 12 6"><path fill="%23555" d="M0 0l6 6 6-6z"/></svg>');
  background-repeat: no-repeat;
  background-position: right 15px center;
  padding-right: 30px;
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

/* ========== STATUS CARDS ========== */
.status-cards {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 30px;
}

.status-card {
  background-color: #fff;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  transition: var(--transition);
  overflow: hidden;
}

.status-card:hover {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.status-card-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  background-color: #f9fafb;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #fff;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.user-info div {
  display: flex;
  flex-direction: column;
}

.user-info strong {
  font-size: 15px;
  color: var(--dark-color);
}

.user-info small {
  font-size: 12px;
  color: var(--text-muted);
}

.status-badge {
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #fff;
}

.status-disetujui {
  background-color: var(--primary-color);
}

.status-diproses {
  background-color: var(--warning-color);
}

.status-selesai {
  background-color: var(--secondary-color);
}

.status-ditolak {
  background-color: var(--danger-color);
}

.status-card-body {
  padding: 20px;
  display: flex;
  gap: 30px;
}

@media (max-width: 768px) {
  .status-card-body {
    flex-direction: column;
    gap: 20px;
  }
}

.status-card-left {
  flex: 3;
}

.status-card-right {
  flex: 2;
  border-left: 1px solid var(--border-color);
  padding-left: 25px;
}

@media (max-width: 768px) {
  .status-card-right {
    border-left: none;
    padding-left: 0;
    border-top: 1px solid var(--border-color);
    padding-top: 20px;
  }
}

.status-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 10px;
  color: var(--dark-color);
}

.status-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 15px;
  font-size: 13px;
  color: var(--text-muted);
}

.status-date, .status-category, .status-id {
  display: flex;
  align-items: center;
  gap: 5px;
}

.status-info {
  margin-bottom: 20px;
}

.status-info:last-child {
  margin-bottom: 0;
}

.status-label {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: var(--dark-color);
}

.status-description {
  font-size: 14px;
  line-height: 1.7;
  color: var(--text-color);
}

.status-value {
  font-size: 14px;
  color: var(--text-color);
}

.attachment-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 8px 12px;
  border-radius: var(--border-radius);
  background-color: #f5f7fa;
  color: var(--primary-color);
  text-decoration: none;
  font-size: 13px;
  transition: var(--transition);
}

.attachment-link:hover {
  background-color: var(--primary-color);
  color: #fff;
}

.status-card-footer {
  padding: 12px 20px;
  border-top: 1px solid var(--border-color);
  color: var(--text-muted);
  font-size: 13px;
  background-color: #f9fafb;
  text-align: right;
}

/* ========== PROCESS TIMELINE ========== */
.process-timeline {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  position: relative;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
  flex: 1;
}

.timeline-step:not(:last-child)::after {
  content: '';
  height: 2px;
  background-color: var(--border-color);
  width: 100%;
  position: absolute;
  top: 18px;
  left: 50%;
  z-index: 1;
}

.timeline-step.completed:not(:last-child)::after {
  background-color: var(--secondary-color);
}

.timeline-icon {
  width: 36px;
  height: 36px;
  background-color: #fff;
  border: 2px solid var(--border-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
  color: var(--text-muted);
  font-size: 14px;
  transition: var(--transition);
}

.timeline-icon.active {
  background-color: var(--secondary-color);
  border-color: var(--secondary-color);
  color: #fff;
}

.timeline-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
}

.timeline-step.completed .timeline-label {
  color: var(--secondary-color);
}

/* ========== FEEDBACK STYLES ========== */
.feedback-display {
  background-color: #f9fafb;
  border-radius: var(--border-radius);
  padding: 15px;
}

.feedback-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.feedback-stars {
  color: var(--warning-color);
  font-size: 16px;
}

.feedback-date {
  font-size: 12px;
  color: var(--text-muted);
}

.feedback-text {
  font-size: 14px;
  line-height: 1.6;
}

.feedback-rating {
  margin-bottom: 15px;
}

.rating-stars {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
}

.rating-stars input[type="radio"] {
  display: none;
}

.rating-stars label {
  cursor: pointer;
  font-size: 24px;
  color: #ccc;
  margin-right: 5px;
  transition: var(--transition);
}

.rating-stars label:hover,
.rating-stars label:hover ~ label,
.rating-stars input[type="radio"]:checked ~ label {
  color: var(--warning-color);
}

textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  font-family: inherit;
  font-size: 14px;
  resize: vertical;
  min-height: 100px;
  margin-bottom: 15px;
  transition: var(--transition);
}

textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

/* ========== BUTTONS ========== */
.btn {
  display: inline-block;
  padding: 10px 20px;
  border-radius: var(--border-radius);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  text-align: center;
  text-decoration: none;
  border: none;
}

.btn-primary {
  background-color: var(--primary-color);
  color: #fff;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-secondary {
  background-color: #f5f7fa;
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

.btn-secondary:hover {
  background-color: var(--light-color);
}

/* ========== PAGINATION ========== */
.pagination {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 30px;
}

.page-btn {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  background-color: #fff;
  color: var(--text-color);
  border: 1px solid var(--border-color);
  cursor: pointer;
  transition: var(--transition);
}

.page-btn:hover {
  background-color: var(--light-color);
}

.page-btn.active {
  background-color: var(--primary-color);
  color: #fff;
  border-color: var(--primary-color);
}

/* ========== EMPTY STATE ========== */
.empty-state {
  text-align: center;
  padding: 60px 30px;
  background-color: #fff;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
}

.empty-state i {
  font-size: 60px;
  color: var(--text-muted);
  margin-bottom: 20px;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 15px;
  color: var(--dark-color);
}

.empty-state p {
  font-size: 15px;
  color: var(--text-muted);
  max-width: 500px;
  margin: 0 auto;
}

/* ========== MODALS ========== */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  align-items: center;
  justify-content: center;
}

.modal.show {
  display: flex;
}

.modal-content {
  background-color: #fff;
  border-radius: var(--border-radius);
  max-width: 400px;
  width: 90%;
  padding: 30px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
  text-align: center;
  animation: modalFadeIn 0.3s;
}

.image-modal-content {
  max-width: 800px;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background-color: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 28px;
  color: var(--primary-color);
}

.modal-title {
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 15px;
  color: var(--dark-color);
}

.modal-message {
  font-size: 15px;
  color: var(--text-muted);
  margin-bottom: 25px;
}

.modal-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 15px;
  margin-bottom: 15px;
  border-bottom: 1px solid var(--border-color);
}

.modal-header .close {
  background-color: transparent;
  border: none;
  font-size: 22px;
  cursor: pointer;
  color: var(--text-muted);
  transition: var(--transition);
}

.modal-header .close:hover {
  color: var(--danger-color);
}

/* ========== RESPONSIVE DESIGN ========== */
@media (max-width: 992px) {
  .main-content {
    padding: 20px;
  }
  
  .dashboard-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .search-box {
    width: 100%;
  }
  
  .filter-select {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .dashboard-stats {
    grid-template-columns: 1fr;
  }
  
  .status-meta {
    flex-direction: column;
    gap: 8px;
  }
  
  .stat-card-value {
    font-size: 24px;
  }
}

/* ========== ANIMATIONS ========== */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.fade-in {
  animation: fadeIn 0.5s ease;
}

/* ========== UTILITIES ========== */
.text-center {
  text-align: center;
}

.img-fluid {
  max-width: 100%;
  height: auto;
}

/* ========== FANCYBOX CUSTOMIZATION ========== */
.fancybox-bg {
  background-color: rgba(30, 30, 30, 0.9);
}

.fancybox-is-open .fancybox-bg {
  opacity: 1;
}

.fancybox-caption {
  font-family: inherit;
  font-size: 14px;
  padding: 15px;
}
   
    /* Mobile responsive */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .navbar, .main-content, .footer {
        left: 0;
      }
      
      .status-cards {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      }
    }
    
    @media (max-width: 576px) {
      .status-cards {
        grid-template-columns: 1fr;
      }
      
      .navbar {
        padding: 0 15px;
      }
      
      .navbar-title {
        font-size: 18px;
      }
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
    <img src="<?php echo htmlspecialchars($fotoprofil); ?>" alt="Foto Profil" class="sidebar-user-img" onerror="this.src='gambar/default.png'">
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
      <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
    </div>
  </div>
  
  <div class="sidebar-divider"></div>
  
  <div class="sidebar-menu">
    <div class="menu-label">Main Navigation</div>

    <?php if($_SESSION['user_role'] == 'admin'): ?>
      <!-- Menu Khusus Admin -->
      <div class="menu-item">
        <a href="dashboard_admin.php" class="menu-link">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="data_pengaduan.php" class="menu-link">
          <i class="fas fa-clipboard-list menu-icon"></i>
          <span>Data Pengaduan</span>
        </a>
      </div>
      
      <div class="menu-item">
        <a href="status_pengaduan.php" class="menu-link">
          <i class="fas fa-tasks menu-icon"></i>
          <span>Status Pengaduan</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="riwayat_pengaduan.php" class="menu-link active">
          <i class="fas fa-history menu-icon"></i>
          <span>Riwayat Pengaduan</span>
        </a>
      </div>
      
      <div class="menu-item">
        <a href="data_masyarakat.php" class="menu-link">
          <i class="fas fa-users menu-icon"></i>
          <span>Data Masyarakat</span>
        </a>
      </div>

    <?php elseif($_SESSION['user_role'] == 'kepala_dinas'): ?>
      <!-- Menu Khusus Kepala Dinas -->
       <div class="menu-item">
        <a href="dashboard_admin.php" class="menu-link">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="riwayat_pengaduan.php" class="menu-link active">
          <i class="fas fa-history menu-icon"></i>
          <span>Riwayat Pengaduan</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="data_masyarakat.php" class="menu-link">
          <i class="fas fa-users menu-icon"></i>
          <span>Data Masyarakat</span>
        </a>
      </div>

    <?php else: ?>
      <!-- Menu Khusus Masyarakat -->
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
        <a href="status_pengaduan_masyarakat.php" class="menu-link">
          <i class="fas fa-file-alt menu-icon"></i>
          <span>Status Pengaduan</span>
        </a>
      </div>
    <?php endif; ?>

    <div class="menu-item">
      <a href="data_akun.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'data_akun.php' ? 'active' : ''; ?>">
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
  <nav class="navbar" id="navbar">
    <div class="navbar-left">
      <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="navbar-title">Riwayat Pengaduan</div>
    </div>
    
    <div class="navbar-right">
      <div class="user-dropdown" onclick="toggleDropdown()">
        <img src="<?php echo htmlspecialchars($fotoprofil); ?>" alt="Foto Profil" class="user-dropdown-img" onerror="this.src='gambar/default.png'">
      </div>
      
      <div class="dropdown-content" id="userDropdown">
        <div class="dropdown-panel">
          <img src="<?php echo htmlspecialchars($fotoprofil); ?>" alt="Foto Profil" onerror="this.src='gambar/default.png'">
          <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
          <small><?php echo htmlspecialchars($_SESSION['user_role']); ?></small>
        </div>
        
        <div class="dropdown-menu">
          <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
          <a href="#" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>
    </div>
  </nav>
  
  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    
    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
      <div class="stat-card stat-card-1">
        <div class="stat-card-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-card-title">Pengaduan Baru</div>
        <div class="stat-card-value"><?php echo $count_data['disetujui'] ?? 0; ?></div>
        <div class="stat-card-desc">Menunggu diproses</div>
      </div>
      
      <div class="stat-card stat-card-2">
        <div class="stat-card-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-card-title">Pengaduan Selesai</div>
        <div class="stat-card-value"><?php echo $count_data['selesai'] ?? 0; ?></div>
        <div class="stat-card-desc">Berhasil diselesaikan</div>
      </div>
      
      <div class="stat-card stat-card-3">
        <div class="stat-card-icon">
          <i class="fas fa-spinner"></i>
        </div>
        <div class="stat-card-title">Sedang Diproses</div>
        <div class="stat-card-value"><?php echo $count_data['diproses'] ?? 0; ?></div>
        <div class="stat-card-desc">Dalam penanganan</div>
      </div>
      
      <div class="stat-card stat-card-4">
        <div class="stat-card-icon">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-card-title">Pengaduan Ditolak</div>
        <div class="stat-card-value"><?php echo $count_data['ditolak'] ?? 0; ?></div>
        <div class="stat-card-desc">Tidak dapat ditindaklanjuti</div>
      </div>
    </div>
    
    <?php if (isset($feedback_msg)): ?>
    <div class="alert alert-<?php echo $feedback_msgType; ?>" role="alert">
      <?php echo htmlspecialchars($feedback_msg); ?>
    </div>
    <?php endif; ?>
    
    <!-- Filter Controls -->
    <div class="filter-controls">
      <div class="filter-group">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="search-input" id="searchInput" placeholder="Cari pengaduan...">
        </div>
        
        <select class="filter-select" id="categoryFilter">
          <option value="">Semua Kategori</option>
          <option value="Bantuan Sosial">Bantuan Sosial</option>
          <option value="Infrastruktur">Infrastruktur</option>
          <option value="Kesehatan">Kesehatan</option>
          <option value="Pendidikan">Pendidikan</option>
          <option value="Layanan Publik">Layanan Publik</option>
          <option value="Lainnya">Lainnya</option>
        </select>
        
        <select class="filter-select" id="ratingFilter">
          <option value="">Semua Rating</option>
          <option value="5">5 Bintang</option>
          <option value="4">4 Bintang</option>
          <option value="3">3 Bintang</option>
          <option value="2">2 Bintang</option>
          <option value="1">1 Bintang</option>
          <option value="0">Belum Ada Rating</option>
        </select>
      </div>
      
      <div class="filter-group">
        <select class="filter-select" id="sortBy">
          <option value="newest">Terbaru</option>
          <option value="oldest">Terlama</option>
          <option value="highest">Rating Tertinggi</option>
          <option value="lowest">Rating Terendah</option>
        </select>
      </div>
    </div>
    
    <!-- Riwayat Pengaduan List -->
    <div class="status-cards">
      <?php if(count($riwayat_pengaduan) > 0): ?>
        <?php foreach($riwayat_pengaduan as $pengaduan): ?>
          <div class="status-card" data-category="<?php echo htmlspecialchars($pengaduan['kategori']); ?>" data-rating="<?php echo $pengaduan['feedback_rating']; ?>">
            <div class="status-card-header">
              <div class="user-info">
                <img src="assets/img/profile/<?php echo !empty($pengaduan['foto_pelapor']) ? htmlspecialchars($pengaduan['foto_pelapor']) : 'default.png'; ?>" alt="User" class="user-avatar" onerror="this.src='assets/img/profile/default.png'">
                <div>
                  <strong><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></strong>
                  <small>NIK: <?php echo htmlspecialchars($pengaduan['nik_pelapor']); ?></small>
                </div>
              </div>
              <span class="status-badge status-<?php echo $pengaduan['status']; ?>">
                <?php echo ucfirst($pengaduan['status']); ?>
              </span>
            </div>
            
            <div class="status-card-body">
              <div class="status-card-left">
                <h4 class="status-title"><?php echo htmlspecialchars($pengaduan['judul']); ?></h4>
                
                <div class="status-meta">
                  <div class="status-date">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('d M Y', strtotime($pengaduan['created_at'])); ?>
                  </div>
                  <div class="status-category">
                    <i class="fas fa-tag"></i>
                    <?php echo htmlspecialchars($pengaduan['kategori']); ?>
                  </div>
                  <div class="status-id">
                    <i class="fas fa-hashtag"></i>
                    ID: <?php echo htmlspecialchars($pengaduan['id_pengaduan']); ?>
                  </div>
                </div>
                
                <div class="status-info">
                  <div class="status-label">Deskripsi Pengaduan:</div>
                  <div class="status-description"><?php echo nl2br(htmlspecialchars($pengaduan['deskripsi'])); ?></div>
                </div>
                
                <?php if(!empty($pengaduan['foto'])): ?>
                <div class="status-info">
                  <div class="status-label">Foto Lampiran:</div>
                  <a href="assets/img/pengaduan/<?php echo htmlspecialchars($pengaduan['foto']); ?>" data-fancybox="gallery-<?php echo $pengaduan['id_pengaduan']; ?>" class="attachment-link" data-caption="Foto Lampiran Pengaduan">
                    <i class="fas fa-image"></i> Lihat Foto
                  </a>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($pengaduan['alamat_lokasi'])): ?>
                <div class="status-info">
                  <div class="status-label">Lokasi:</div>
                  <div class="status-value"><?php echo htmlspecialchars($pengaduan['alamat_lokasi']); ?></div>
                </div>
                <?php endif; ?>
              </div>
              
              <div class="status-card-right">
                <div class="status-info">
                  <div class="status-label">Status Pengaduan:</div>
                  <div class="process-timeline">
                    <div class="timeline-step <?php echo in_array($pengaduan['status'], ['disetujui', 'diproses', 'selesai']) ? 'completed' : ''; ?>">
                      <div class="timeline-icon <?php echo in_array($pengaduan['status'], ['disetujui', 'diproses', 'selesai']) ? 'active' : ''; ?>">
                        <i class="fas fa-check"></i>
                      </div>
                      <div class="timeline-label">Disetujui</div>
                    </div>
                    <div class="timeline-step <?php echo in_array($pengaduan['status'], ['diproses', 'selesai']) ? 'completed' : ''; ?>">
                      <div class="timeline-icon <?php echo in_array($pengaduan['status'], ['diproses', 'selesai']) ? 'active' : ''; ?>">
                        <i class="fas fa-spinner"></i>
                      </div>
                      <div class="timeline-label">Diproses</div>
                    </div>
                    <div class="timeline-step <?php echo $pengaduan['status'] == 'selesai' ? 'completed' : ''; ?>">
                      <div class="timeline-icon <?php echo $pengaduan['status'] == 'selesai' ? 'active' : ''; ?>">
                        <i class="fas fa-flag-checkered"></i>
                      </div>
                      <div class="timeline-label">Selesai</div>
                    </div>
                  </div>
                </div>
                
                <div class="status-info">
                  <div class="status-label">Keterangan Penyelesaian:</div>
                  <div class="status-description"><?php echo nl2br(htmlspecialchars($pengaduan['keterangan'])); ?></div>
                </div>
                
                <?php if(!empty($pengaduan['bukti_proses'])): ?>
                <div class="status-info">
                  <div class="status-label">Bukti Proses:</div>
                  <?php 
                  // Jika bukti_proses berisi multiple file (dipisah koma)
                  $bukti_proses_files = explode(',', $pengaduan['bukti_proses']);
                  foreach($bukti_proses_files as $index => $bukti_file): 
                    $bukti_file = trim($bukti_file);
                    if(!empty($bukti_file)):
                  ?>
                    <a href="uploads/bukti_proses/<?php echo htmlspecialchars($bukti_file); ?>" 
                       data-fancybox="gallery-proses-<?php echo $pengaduan['id_pengaduan']; ?>" 
                       class="attachment-link"
                       data-caption="Bukti Proses <?php echo count($bukti_proses_files) > 1 ? ($index + 1) : ''; ?>">
                      <i class="fas fa-file-image"></i> Lihat Bukti Proses <?php echo count($bukti_proses_files) > 1 ? ($index + 1) : ''; ?>
                    </a>
                    <?php if($index < count($bukti_proses_files) - 1): ?>
                      <br>
                    <?php endif; ?>
                  <?php 
                    endif;
                  endforeach; 
                  ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($pengaduan['bukti_selesai'])): ?>
                <div class="status-info">
                  <div class="status-label">Bukti Penyelesaian:</div>
                  <a href="uploads/bukti/<?php echo htmlspecialchars($pengaduan['bukti_selesai']); ?>" 
                     data-fancybox="gallery-selesai-<?php echo $pengaduan['id_pengaduan']; ?>" 
                     class="attachment-link"
                     data-caption="Bukti Penyelesaian">
                    <i class="fas fa-file-image"></i> Lihat Bukti Penyelesaian
                  </a>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($pengaduan['feedback_komentar']) || $pengaduan['feedback_rating'] > 0): ?>
                <div class="status-info">
                  <div class="status-label">Umpan Balik:</div>
                  <div class="feedback-display">
                    <div class="feedback-header">
                      <div class="feedback-stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                          <i class="<?php echo $i <= $pengaduan['feedback_rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                      </div>
                      <div class="feedback-date">
                        <?php echo !empty($pengaduan['feedback_tanggal']) ? date('d M Y', strtotime($pengaduan['feedback_tanggal'])) : ''; ?>
                      </div>
                    </div>
                    <div class="feedback-text"><?php echo nl2br(htmlspecialchars($pengaduan['feedback_komentar'])); ?></div>
                  </div>
                </div>
                <?php else: ?>
                <div class="status-info">
                  <div class="status-label">Berikan Umpan Balik:</div>
                  <form action="" method="post">
                    <input type="hidden" name="id_pengaduan" value="<?php echo $pengaduan['id_pengaduan']; ?>">
                    <div class="feedback-rating">
                      <div class="rating-stars">
                        <input type="radio" name="rating" id="rating-5-<?php echo $pengaduan['id_pengaduan']; ?>" value="5" required>
                        <label for="rating-5-<?php echo $pengaduan['id_pengaduan']; ?>"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" id="rating-4-<?php echo $pengaduan['id_pengaduan']; ?>" value="4">
                        <label for="rating-4-<?php echo $pengaduan['id_pengaduan']; ?>"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" id="rating-3-<?php echo $pengaduan['id_pengaduan']; ?>" value="3">
                        <label for="rating-3-<?php echo $pengaduan['id_pengaduan']; ?>"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" id="rating-2-<?php echo $pengaduan['id_pengaduan']; ?>" value="2">
                        <label for="rating-2-<?php echo $pengaduan['id_pengaduan']; ?>"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" id="rating-1-<?php echo $pengaduan['id_pengaduan']; ?>" value="1">
                        <label for="rating-1-<?php echo $pengaduan['id_pengaduan']; ?>"><i class="fas fa-star"></i></label>
                      </div>
                    </div>
                    <textarea name="komentar" placeholder="Tulis komentar Anda..." required></textarea>
                    <button type="submit" name="submit_feedback" class="btn btn-primary">Kirim Umpan Balik</button>
                  </form>
                </div>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="status-card-footer">
              <span>Terakhir diperbarui: <?php echo date('d M Y H:i', strtotime($pengaduan['status_date'])); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-history"></i>
          <h3>Belum Ada Riwayat Pengaduan</h3>
          <p>Belum ada pengaduan yang telah selesai ditangani. Pengaduan yang telah selesai akan muncul di halaman ini.</p>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <div class="pagination">
      <button class="page-btn active">1</button>
      <button class="page-btn">2</button>
      <button class="page-btn">3</button>
      <button class="page-btn">4</button>
      <button class="page-btn">5</button>
    </div>
  </div>
  
  <!-- Logout Modal -->
  <div class="modal" id="logoutModal">
    <div class="modal-content">
      <div class="modal-icon">
        <i class="fas fa-sign-out-alt"></i>
      </div>
      <h3 class="modal-title">Konfirmasi Keluar</h3>
      <p class="modal-message">Apakah Anda yakin ingin keluar dari sistem?</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeLogoutModal()">Batal</button>
        <a href="logout.php" class="btn btn-primary">Keluar</a>
        <div class="modal-actions">
          <button class="btn btn-secondary" onclick="closeLogoutModal()">Batal</button>
          <a href="logout.php" class="btn btn-primary">Keluar</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
  <script>
    // Toggle sidebar
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const navbar = document.getElementById('navbar');
      const mainContent = document.getElementById('mainContent');
      
      sidebar.classList.toggle('sidebar-collapsed');
      navbar.classList.toggle('navbar-expanded');
      mainContent.classList.toggle('content-expanded');
    }

    document.getElementById('toggleSidebar').addEventListener('click', toggleSidebar);

    // User dropdown
    function toggleDropdown() {
      const dropdown = document.getElementById('userDropdown');
      dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
      if (!event.target.matches('.user-dropdown-img')) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown.classList.contains('show')) {
          dropdown.classList.remove('show');
        }
      }
    });

    // Show logout modal
    function showLogoutModal() {
      document.getElementById('logoutModal').style.display = 'flex';
    }

    // Close logout modal
    function closeLogoutModal() {
      document.getElementById('logoutModal').style.display = 'none';
    }

    // Search and filter functionality
    document.getElementById('searchInput').addEventListener('input', filterCards);
    document.getElementById('categoryFilter').addEventListener('change', filterCards);
    document.getElementById('ratingFilter').addEventListener('change', filterCards);
    document.getElementById('sortBy').addEventListener('change', sortCards);

    function filterCards() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const categoryFilter = document.getElementById('categoryFilter').value;
      const ratingFilter = document.getElementById('ratingFilter').value;
      const cards = document.querySelectorAll('.status-card');

      cards.forEach(card => {
        const title = card.querySelector('.status-title').textContent.toLowerCase();
        const description = card.querySelector('.status-description').textContent.toLowerCase();
        const category = card.dataset.category;
        const rating = card.dataset.rating;

        const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        const matchesRating = !ratingFilter || rating === ratingFilter;

        if (matchesSearch && matchesCategory && matchesRating) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }

    function sortCards() {
      const sortBy = document.getElementById('sortBy').value;
      const container = document.querySelector('.status-cards');
      const cards = Array.from(container.querySelectorAll('.status-card'));

      cards.sort((a, b) => {
        switch(sortBy) {
          case 'newest':
            return new Date(b.querySelector('.status-card-footer span').textContent.split(': ')[1]) - 
                   new Date(a.querySelector('.status-card-footer span').textContent.split(': ')[1]);
          case 'oldest':
            return new Date(a.querySelector('.status-card-footer span').textContent.split(': ')[1]) - 
                   new Date(b.querySelector('.status-card-footer span').textContent.split(': ')[1]);
          case 'highest':
            return parseInt(b.dataset.rating) - parseInt(a.dataset.rating);
          case 'lowest':
            return parseInt(a.dataset.rating) - parseInt(b.dataset.rating);
          default:
            return 0;
        }
      });

      cards.forEach(card => container.appendChild(card));
    }

    // Rating stars functionality
    document.querySelectorAll('.rating-stars input[type="radio"]').forEach(input => {
      input.addEventListener('change', function() {
        const stars = this.closest('.rating-stars').querySelectorAll('label');
        const rating = parseInt(this.value);
        
        stars.forEach((star, index) => {
          if (index < rating) {
            star.style.color = '#ffc107';
          } else {
            star.style.color = '#e4e5e9';
          }
        });
      });
    });

    // Auto-hide alerts
    setTimeout(function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
      });
    }, 5000);

    // Initialize Fancybox
    $(document).ready(function() {
      $('[data-fancybox]').fancybox({
        buttons: [
          "zoom",
          "slideShow",
          "fullScreen",
          "download",
          "close"
        ],
        loop: true,
        protect: true
      });
    });
  </script>
</body>
</html>