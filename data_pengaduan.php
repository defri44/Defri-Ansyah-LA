<?php
session_start();
// Pastikan pengguna sudah login sebelum menampilkan halaman
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role'])) {
    header('Location: beranda.php'); // Redirect ke halaman login jika belum login
    exit();
}

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['user_name']) || 
    ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'kepala_dinas')) {
    header('Location: beranda.php'); // Redirect ke halaman login jika belum login atau bukan admin/kepala_dinas
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
$user_id = $_SESSION['user_id']; // Pastikan Anda menyimpan user_id saat login
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

// Check if the status_pengaduan column exists in the pengaduan table
$checkColumnQuery = "SHOW COLUMNS FROM pengaduan LIKE 'status_pengaduan'";
$columnResult = mysqli_query($conn, $checkColumnQuery);

// If the column doesn't exist, create it
if (mysqli_num_rows($columnResult) == 0) {
    $alterTableQuery = "ALTER TABLE pengaduan ADD COLUMN status_pengaduan VARCHAR(50) NULL";
    if (mysqli_query($conn, $alterTableQuery)) {
        $_SESSION['success_message'] = "Database structure updated successfully.";
    } else {
        $_SESSION['error_message'] = "Error updating database structure: " . mysqli_error($conn);
    }
}

// Check if the status_pengaduan table exists
$checkTableQuery = "SHOW TABLES LIKE 'status_pengaduan'";
$tableResult = mysqli_query($conn, $checkTableQuery);

// If the table doesn't exist, create it
if (mysqli_num_rows($tableResult) == 0) {
    $createTableQuery = "CREATE TABLE status_pengaduan (
        id_status INT(11) AUTO_INCREMENT PRIMARY KEY,
        id_pengaduan INT(11) NOT NULL,
        nama_pengadu VARCHAR(100) NOT NULL,
        status ENUM('ditolak', 'disetujui', 'diproses', 'selesai') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        keterangan TEXT NULL,
        bukti_selesai VARCHAR(255) NULL
    )";
    
    if (mysqli_query($conn, $createTableQuery)) {
        $_SESSION['success_message'] = "Tabel status_pengaduan berhasil dibuat.";
    } else {
        $_SESSION['error_message'] = "Error creating table: " . mysqli_error($conn);
    }
}

// Proses validasi pengaduan
if (isset($_GET['approve']) && isset($_GET['id']) && isset($_GET['nama'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_GET['id']);
    $nama_pengadu = mysqli_real_escape_string($conn, $_GET['nama']);
    $status = "disetujui";
    $keterangan = "Pengaduan telah divalidasi dan disetujui";

    // Insert into status_pengaduan table
    $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, nama_pengadu, status, keterangan) 
                    VALUES ('$id_pengaduan', '$nama_pengadu', '$status', '$keterangan')";

    if (mysqli_query($conn, $insertQuery)) {
        $_SESSION['success_message'] = "Pengaduan berhasil disetujui.";
        
        // Update the status_pengaduan column in the pengaduan table
        $updateQuery = "UPDATE pengaduan SET status_pengaduan = 'disetujui' WHERE id_pengaduan = '$id_pengaduan'";
        mysqli_query($conn, $updateQuery);
        
    } else {
        $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
    }

    // Redirect to refresh the page
    header("Location: data_pengaduan.php");
    exit();
}

// Proses penolakan pengaduan - DIPERBAIKI
if (isset($_POST['id_pengaduan']) && isset($_POST['keterangan'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
    $nama_pengadu = mysqli_real_escape_string($conn, $_POST['nama_pengadu']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $status = "ditolak";
    
    // Insert into status_pengaduan table
    $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, nama_pengadu, status, keterangan)
                    VALUES ('$id_pengaduan', '$nama_pengadu', '$status', '$keterangan')";
    
    if (mysqli_query($conn, $insertQuery)) {
        $_SESSION['success_message'] = "Pengaduan berhasil ditolak.";

        // Update the status_pengaduan column in the pengaduan table
        $updateQuery = "UPDATE pengaduan SET status_pengaduan = 'ditolak' WHERE id_pengaduan = '$id_pengaduan'";
        mysqli_query($conn, $updateQuery);

    } else {
        $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
    }

    // Redirect to refresh the page
    header("Location: data_pengaduan.php");
    exit();
}

// Ambil data pengaduan yang belum divalidasi dari database
try {
    // Modified query to show only unvalidated complaints
    $query = "SELECT * FROM pengaduan WHERE status_pengaduan IS NULL OR status_pengaduan = '' ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Pengaduan - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
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
    
    /* Table Styles */
    .data-table-container {
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      margin-bottom: 25px;
    }
    
    .table-header {
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #eee;
    }
    
    .table-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .table-filter {
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .search-container {
      position: relative;
    }
    
    .search-input {
      padding: 10px 15px 10px 40px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      width: 250px;
      transition: var(--transition);
    }
    
    .search-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(243, 156, 18, 0.2);
    }
    
    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
    }
    
    .data-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .data-table th, .data-table td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    .data-table th {
      font-weight: 600;
      background-color: #f8f9fa;
      color: var(--dark);
    }
    
    .data-table tbody tr:hover {
      background-color: #f8f9fa;
    }
    
    .table-actions {
      display: flex;
      gap: 10px;
    }
    
    .btn-table {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .btn-view {
      background-color: var(--accent);
      color: white;
    }
    
    .btn-view:hover {
      background-color: #2980b9;
    }
    
    .btn-accept {
      background-color: var(--success);
      color: white;
    }
    
    .btn-accept:hover {
      background-color: #27ae60;
    }
    
    .btn-reject {
      background-color: var(--danger);
      color: white;
    }
    
    .btn-reject:hover {
      background-color: #c0392b;
    }
    
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .status-pending {
      background-color: #FEF9E7;
      color: #F1C40F;
    }
    
    .status-process {
      background-color: #EBF5FB;
      color: #3498DB;
    }
    
    .status-completed {
      background-color: #E9F7EF;
      color: #2ECC71;
    }
    
    .status-rejected {
      background-color: #FDEDEC;
      color: #E74C3C;
    }
    
    .pagination {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
    }
    
    .pagination button {
      background-color: white;
      border: 1px solid #ddd;
      padding: 8px 15px;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .pagination button:first-child {
      border-top-left-radius: 4px;
      border-bottom-left-radius: 4px;
    }
    
    .pagination button:last-child {
      border-top-right-radius: 4px;
      border-bottom-right-radius: 4px;
    }
    
    .pagination button:hover {
      background-color: #f8f9fa;
    }
    
    .pagination button.active {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    /* Modal Styles */
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
      max-width: 600px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
      padding: 15px 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .modal-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .modal-close {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: #777;
    }
    
    .modal-body {
      padding: 20px;
      max-height: 70vh;
      overflow-y: auto;
    }
    
    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    .detail-row {
      margin-bottom: 15px;
    }
    
    .detail-label {
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 5px;
    }
    
    .detail-value {
      color: #555;
    }
    
    .detail-image {
      width: 100%;
      max-height: 300px;
      object-fit: contain;
      border-radius: 4px;
      margin-top: 10px;
    }
    
    .detail-address {
      padding: 10px;
      background-color: #f8f9fa;
      border-radius: 4px;
      margin-top: 5px;
    }
    
    /* Alert Messages */
    .alert {
      padding: 15px;
      border-radius: var(--card-radius);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .alert-success {
      background-color: #E9F7EF;
      color: #2ECC71;
      border-left: 4px solid #2ECC71;
    }
    
    .alert-error {
      background-color: #FDEDEC;
      color: #E74C3C;
      border-left: 4px solid #E74C3C;
    }
    
    .alert-close {
      background: none;
      border: none;
      color: inherit;
      font-size: 18px;
      cursor: pointer;
      opacity: 0.7;
    }
    
    .alert-close:hover {
      opacity: 1;
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
      
      .search-input {
        width: 180px;
      }
      
      .table-responsive {
        overflow-x: auto;
      }
    }
    
    @media (max-width: 576px) {
      .navbar {
        padding: 0 15px;
      }
      
      .navbar-title {
        font-size: 18px;
      }
      
      .table-filter {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .search-input {
        width: 100%;
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
        <a href="dashboard_admin.php" class="menu-link">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
        </a>
      </div>
      
      <div class="menu-item">
        <a href="data_pengaduan.php" class="menu-link active" >
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
        <a href="riwayat_pengaduan.php" class="menu-link">
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
  <nav class="navbar" id="navbar">
    <div class="navbar-left">
      <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="navbar-title">Data Pengaduan</div>
    </div>
    
    <div class="navbar-right">
      <div class="user-dropdown" onclick="toggleDropdown()">
        <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" class="user-dropdown-img" onerror="this.src='gambar/default.png'">
      </div>
      
      <div class="dropdown-content" id="userDropdown">
        <div class="dropdown-panel">
          <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" onerror="this.src='gambar/default.png'">
          <h4><?php echo $_SESSION['user_name']; ?></h4>
          <small><?php echo $_SESSION['user_role']; ?></small>
        </div>
        
        <div class="dropdown-menu">
          <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
          <a href="#" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <div><?php echo $_SESSION['success_message']; ?></div>
        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
  
<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-error">
    <div><?php echo $_SESSION['error_message']; ?></div>
    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
  </div>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="data-table-container">
  <div class="table-header">
    <div class="table-title">Data Pengaduan Belum Divalidasi</div>
    <div class="table-filter">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" id="searchInput" placeholder="Cari data...">
      </div>
    </div>
  </div>
  
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>No.</th>
          <th>Tanggal</th>
          <th>NIK</th>
          <th>Nama</th>
          <th>Judul</th>
          <th>Kategori</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
          $no = 1;
          while ($row = mysqli_fetch_assoc($result)) {
            $timestamp = strtotime($row['created_at']);
            $tanggal = date('d/m/Y', $timestamp);
            ?>
            <tr>
              <td><?php echo $no++; ?></td>
              <td><?php echo $tanggal; ?></td>
              <td><?php echo $row['nik']; ?></td>
              <td><?php echo $row['nama']; ?></td>
              <td><?php echo $row['judul']; ?></td>
              <td><?php echo $row['kategori']; ?></td>
              <td class="table-actions">
                <button class="btn-table btn-view" onclick="showDetail(<?php echo $row['id_pengaduan']; ?>, '<?php echo $row['nama']; ?>', '<?php echo $row['nik']; ?>', '<?php echo addslashes($row['judul']); ?>', '<?php echo addslashes($row['deskripsi']); ?>', '<?php echo $row['alamat']; ?>', '<?php echo $row['alamat_lokasi']; ?>', '<?php echo $row['telepon']; ?>', '<?php echo $row['email']; ?>', '<?php echo $row['kategori']; ?>', '<?php echo $row['foto']; ?>', '<?php echo $row['created_at']; ?>')">
                  <i class="fas fa-eye"></i> Detail
                </button>
                <a href="data_pengaduan.php?approve=1&id=<?php echo $row['id_pengaduan']; ?>&nik=<?php echo $row['nik']; ?>&nama=<?php echo urlencode($row['nama']); ?>" class="btn-table btn-accept">
                  <i class="fas fa-check"></i> Terima
                </a>
                <button class="btn-table btn-reject" onclick="showRejectModal(<?php echo $row['id_pengaduan']; ?>, '<?php echo addslashes($row['nama']); ?>')">
                  <i class="fas fa-times"></i> Tolak
                </button>
              </td>
            </tr>
            <?php
          }
        } else {
          ?>
          <tr>
            <td colspan="7" style="text-align: center;">Tidak ada data pengaduan yang belum divalidasi</td>
          </tr>
          <?php
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal" id="detailModal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Detail Pengaduan</div>
      <button class="modal-close" onclick="closeModal('detailModal')">&times;</button>
    </div>
    
    <div class="modal-body">
      <div class="detail-row">
        <div class="detail-label">Judul Pengaduan</div>
        <div class="detail-value" id="detailJudul"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">NIK</div>
        <div class="detail-value" id="detailNIK"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Nama</div>
        <div class="detail-value" id="detailNama"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Telepon</div>
        <div class="detail-value" id="detailTelepon"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Email</div>
        <div class="detail-value" id="detailEmail"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Kategori</div>
        <div class="detail-value" id="detailKategori"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Alamat Pelapor</div>
        <div class="detail-value detail-address" id="detailAlamat"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Alamat Lokasi Kejadian</div>
        <div class="detail-value detail-address" id="detailLokasiKejadian"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Tanggal Pengaduan</div>
        <div class="detail-value" id="detailTanggal"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Deskripsi</div>
        <div class="detail-value" id="detailDeskripsi"></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Bukti Foto</div>
        <img src="" alt="Bukti Foto" class="detail-image" id="detailFoto">
      </div>
    </div>
    
    <div class="modal-footer">
      <button class="btn-table btn-view" onclick="closeModal('detailModal')">Tutup</button>
    </div>
  </div>
</div>

<!-- Reject Modal - DIPERBAIKI -->
<div class="modal" id="rejectModal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Tolak Pengaduan</div>
      <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
    </div>
    
    <div class="modal-body">
      <form id="rejectForm" method="post" action="">
        <input type="hidden" id="rejectId" name="id_pengaduan">
        <input type="hidden" id="rejectNama" name="nama_pengadu">
        
        <div class="detail-row">
          <div class="detail-label">Nama Pengadu</div>
          <div class="detail-value" id="rejectNamaDisplay"></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Alasan Penolakan</div>
          <textarea name="keterangan" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Masukkan alasan penolakan..." required></textarea>
        </div>
      </form>
    </div>
    
    <div class="modal-footer">
      <button class="btn-table btn-view" onclick="closeModal('rejectModal')">Batal</button>
      <button class="btn-table btn-reject" onclick="submitRejectForm()">Konfirmasi Tolak</button>
    </div>
  </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal" id="logoutModal">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <div class="modal-title">Konfirmasi Keluar</div>
      <button class="modal-close" onclick="closeModal('logoutModal')">&times;</button>
    </div>
    
    <div class="modal-body">
      <p>Apakah Anda yakin ingin keluar dari sistem?</p>
    </div>
    
    <div class="modal-footer">
      <button class="btn-table btn-view" onclick="closeModal('logoutModal')">Batal</button>
      <a href="logout.php" class="btn-table btn-reject" style="text-decoration: none;">Ya, Keluar</a>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="footer" id="footer">
  &copy; <?php echo date('Y'); ?> SILADU - Sistem Layanan Pengaduan Terpadu | Dinas Sosial Kota Palembang
</footer>

<script>
  // Toggle sidebar function
  document.getElementById('toggleSidebar').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    const navbar = document.getElementById('navbar');
    const mainContent = document.getElementById('mainContent');
    const footer = document.getElementById('footer');
    
    sidebar.classList.toggle('collapsed');
    navbar.classList.toggle('full-width');
    mainContent.classList.toggle('full-width');
    footer.classList.toggle('full-width');
  });
  
  // Toggle user dropdown
  function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
  }
  
  // Close dropdown when clicking outside
  window.onclick = function(event) {
    if (!event.target.matches('.user-dropdown') && !event.target.matches('.user-dropdown-img')) {
      const dropdown = document.getElementById('userDropdown');
      if (dropdown.classList.contains('show')) {
        dropdown.classList.remove('show');
      }
    }
  };
  
  // Search functionality
  document.getElementById('searchInput').addEventListener('keyup', function() {
    const input = this.value.toLowerCase();
    const table = document.getElementById('tableBody');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
      const cells = rows[i].getElementsByTagName('td');
      let found = false;
      
      for (let j = 0; j < cells.length; j++) {
        const cellText = cells[j].textContent || cells[j].innerText;
        
        if (cellText.toLowerCase().indexOf(input) > -1) {
          found = true;
          break;
        }
      }
      
      rows[i].style.display = found ? '' : 'none';
    }
  });
  
  // Show detail modal
  function showDetail(id, nama, nik, judul, deskripsi, alamat, lokasiKejadian, telepon, email, kategori, foto, tanggal) {
    document.getElementById('detailJudul').textContent = judul;
    document.getElementById('detailNIK').textContent = nik;
    document.getElementById('detailNama').textContent = nama;
    document.getElementById('detailTelepon').textContent = telepon;
    document.getElementById('detailEmail').textContent = email;
    document.getElementById('detailKategori').textContent = kategori;
    document.getElementById('detailAlamat').textContent = alamat;
    document.getElementById('detailLokasiKejadian').textContent = lokasiKejadian;
    
    // Format tanggal
    const date = new Date(tanggal);
    const formattedDate = date.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    document.getElementById('detailTanggal').textContent = formattedDate;
    
    document.getElementById('detailDeskripsi').textContent = deskripsi;
    
    // Check if foto exists
    if (foto && foto !== '') {
      document.getElementById('detailFoto').src = 'uploads/' + foto;
      document.getElementById('detailFoto').style.display = 'block';
    } else {
      document.getElementById('detailFoto').style.display = 'none';
    }
    
    document.getElementById('detailModal').style.display = 'flex';
  }
  
  // Show reject modal - DIPERBAIKI
  function showRejectModal(id, nama) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectNama').value = nama;
    document.getElementById('rejectNamaDisplay').textContent = nama;
    document.getElementById('rejectModal').style.display = 'flex';
  }
  
  // Submit reject form - DIPERBAIKI
  function submitRejectForm() {
    const form = document.getElementById('rejectForm');
    const keterangan = form.querySelector('textarea[name="keterangan"]').value;
    
    if (keterangan.trim() === '') {
      alert('Alasan penolakan harus diisi!');
      return;
    }
    
    if (confirm('Apakah Anda yakin ingin menolak pengaduan ini?')) {
      form.submit();
    }
  }
  
  // Show logout modal
  function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
  }
  
  // Close modal
  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
      event.target.style.display = 'none';
    }
  };
</script>
</body>
</html>