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

// Cek apakah ada aksi hapus
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Cek jika user yang dihapus bukan admin
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = '$id'");
    $user_data = mysqli_fetch_assoc($check_admin);
    
    if ($user_data['role'] == 'admin') {
        $_SESSION['error_message'] = "Anda tidak dapat menghapus pengguna dengan role admin!";
    } else {
        // Lakukan penghapusan data
        $delete_query = "DELETE FROM users WHERE id = '$id'";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success_message'] = "Data pengguna berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data pengguna: " . mysqli_error($conn);
        }
    }
    
    // Redirect kembali ke halaman data masyarakat
    header('Location: data_masyarakat.php');
    exit();
}

// Filter role untuk menampilkan hanya pengguna dengan role masyarakat
$filter_role = "WHERE role = 'masyarakat'";

// Jika ada parameter pencarian
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $filter_role = "WHERE role = 'masyarakat' AND (nama LIKE '%$search%' OR nik LIKE '%$search%' OR email LIKE '%$search%' OR telepon LIKE '%$search%')";
}

// Ambil data masyarakat dari database
$query = "SELECT * FROM users $filter_role ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Periksa apakah query berhasil
if (!$result) {
    $_SESSION['error_message'] = "Error mengambil data: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Masyarakat - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
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
    
    .btn-edit {
      background-color: var(--warning);
      color: white;
    }
    
    .btn-edit:hover {
      background-color: #d4ac0d;
    }
    
    .btn-delete {
      background-color: var(--danger);
      color: white;
    }
    
    .btn-delete:hover {
      background-color: #c0392b;
    }
    
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .status-active {
      background-color: #E9F7EF;
      color: #2ECC71;
    }
    
    .status-inactive {
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

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      transition: var(--transition);
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(243, 156, 18, 0.2);
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
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 4px;
      margin-top: 10px;
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

    .profile-img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
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

    <?php elseif($_SESSION['user_role'] == 'kepala_dinas'): ?>
      <!-- Menu Khusus Kepala Dinas -->
       <div class="menu-item">
        <a href="dashboard_admin.php" class="menu-link">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
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
      <div class="navbar-title">Data Masyarakat</div>
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
        <div class="table-title">Daftar Pengguna Masyarakat</div>
        <div class="table-filter">
          <form action="" method="GET">
            <div class="search-container">
              <i class="fas fa-search search-icon"></i>
              <input type="text" name="search" id="searchInput" class="search-input" placeholder="Cari pengguna..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
          </form>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Foto</th>
              <th>Nama</th>
              <th>NIK</th>
              <th>Email</th>
              <th>Telepon</th>
              <th>Status</th>
              <th>Tgl Daftar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            if ($result && mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                // Set path foto profil
                $user_foto = !empty($row['foto_profil']) ? 'gambar/' . $row['foto_profil'] : 'default.jpg';
            ?>
              <tr>
                <td><?php echo $no++; ?></td>
                <td>
                  <img src="<?php echo $user_foto; ?>" alt="Foto Profil" class="profile-img" onerror="this.src='gambar/default.png'">
                </td>
                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                <td><?php echo htmlspecialchars($row['nik']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['telepon']); ?></td>
                <td>
                  <?php if ($row['status'] == 'active'): ?>
                    <span class="status-badge status-active">Aktif</span>
                  <?php else: ?>
                    <span class="status-badge status-inactive">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                <td class="table-actions">
                  <button class="btn-table btn-view" onclick="viewDetail('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['nama']); ?>', '<?php echo htmlspecialchars($row['nik']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo htmlspecialchars($row['telepon']); ?>', '<?php echo htmlspecialchars($row['alamat'] ?? ''); ?>', '<?php echo htmlspecialchars($row['kecamatan'] ?? ''); ?>', '<?php echo htmlspecialchars($row['kelurahan'] ?? ''); ?>', '<?php echo $user_foto; ?>', '<?php echo htmlspecialchars($row['status']); ?>', '<?php echo date('d M Y', strtotime($row['created_at'])); ?>')">
                    <i class="fas fa-eye"></i> Detail
                  </button>
                  <button class="btn-table btn-edit" onclick="showEditModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['nama']); ?>', '<?php echo htmlspecialchars($row['nik']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo htmlspecialchars($row['telepon']); ?>', '<?php echo htmlspecialchars($row['alamat'] ?? ''); ?>', '<?php echo htmlspecialchars($row['kecamatan'] ?? ''); ?>', '<?php echo htmlspecialchars($row['kelurahan'] ?? ''); ?>', '<?php echo $row['status']; ?>')">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="btn-table btn-delete" onclick="confirmDelete('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['nama']); ?>')">
                    <i class="fas fa-trash-alt"></i> Hapus
                  </button>
                </td>
              </tr>
            <?php
              }
            } else {
            ?>
              <tr>
                <td colspan="9" style="text-align: center;">Tidak ada data masyarakat.</td>
              </tr>
            <?php
            }
            mysqli_close($conn);
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer" id="footer">
    <div>&copy; <?php echo date('Y'); ?> SILADU - Sistem Informasi Pengaduan Terpadu Dinas Sosial Kota Palembang</div>
  </footer>

  <!-- Detail Modal -->
  <div class="modal" id="detailModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Detail Pengguna</div>
        <button class="modal-close" onclick="hideDetailModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-row">
          <div class="detail-label">Foto Profil</div>
          <img id="detailFoto" src="" alt="Foto Profil" class="detail-image" onerror="this.src='gambar/default.png'">
        </div>
        <div class="detail-row">
          <div class="detail-label">Nama Lengkap</div>
          <div class="detail-value" id="detailNama"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">NIK</div>
          <div class="detail-value" id="detailNIK"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Email</div>
          <div class="detail-value" id="detailEmail"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Telepon</div>
          <div class="detail-value" id="detailTelepon"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Alamat</div>
          <div class="detail-value" id="detailAlamat"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Kecamatan</div>
          <div class="detail-value" id="detailKecamatan"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Kelurahan</div>
          <div class="detail-value" id="detailKelurahan"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Status</div>
          <div class="detail-value" id="detailStatus"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Tanggal Daftar</div>
          <div class="detail-value" id="detailTanggal"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-table btn-edit" onclick="hideDetailModal()">Tutup</button>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Edit Pengguna</div>
        <button class="modal-close" onclick="hideEditModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editForm" action="update_user.php" method="POST">
          <input type="hidden" id="editId" name="id">
          
          <div class="form-group">
            <label for="editNama" class="form-label">Nama Lengkap</label>
            <input type="text" id="editNama" name="nama" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="editNIK" class="form-label">NIK</label>
            <input type="text" id="editNIK" name="nik" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="editEmail" class="form-label">Email</label>
            <input type="email" id="editEmail" name="email" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="editTelepon" class="form-label">Telepon</label>
            <input type="text" id="editTelepon" name="telepon" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="editAlamat" class="form-label">Alamat</label>
            <textarea id="editAlamat" name="alamat" class="form-control" rows="3"></textarea>
          </div>
          
          <div class="form-group">
            <label for="editKecamatan" class="form-label">Kecamatan</label>
            <input type="text" id="editKecamatan" name="kecamatan" class="form-control">
          </div>
          
          <div class="form-group">
            <label for="editKelurahan" class="form-label">Kelurahan</label>
            <input type="text" id="editKelurahan" name="kelurahan" class="form-control">
          </div>
          
          <div class="form-group">
            <label for="editStatus" class="form-label">Status</label>
            <select id="editStatus" name="status" class="form-control">
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-table btn-view" onclick="hideEditModal()">Batal</button>
        <button class="btn-table btn-edit" onclick="document.getElementById('editForm').submit()">Simpan</button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Konfirmasi Hapus</div>
        <button class="modal-close" onclick="hideDeleteModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus data pengguna <strong id="deleteUserName"></strong>?</p>
        <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button class="btn-table btn-view" onclick="hideDeleteModal()">Batal</button>
        <a id="deleteUserLink" href="#" class="btn-table btn-delete">Hapus</a>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal" id="logoutModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Konfirmasi Keluar</div>
        <button class="modal-close" onclick="hideLogoutModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin keluar dari sistem?</p>
      </div>
      <div class="modal-footer">
        <button class="btn-table btn-view" onclick="hideLogoutModal()">Batal</button>
        <a href="logout.php" class="btn-table btn-delete">Keluar</a>
      </div>
    </div>
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
    
    // User Dropdown Toggle
    function toggleDropdown() {
      document.getElementById('userDropdown').classList.toggle('show');
    }
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
      if (!event.target.matches('.user-dropdown, .user-dropdown *')) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown.classList.contains('show')) {
          dropdown.classList.remove('show');
        }
      }
    });
    
    // Detail Modal Functions
    function viewDetail(id, nama, nik, email, telepon, alamat, kecamatan, kelurahan, foto, status, tanggal) {
      document.getElementById('detailNama').textContent = nama;
      document.getElementById('detailNIK').textContent = nik;
      document.getElementById('detailEmail').textContent = email;
      document.getElementById('detailTelepon').textContent = telepon;
      document.getElementById('detailAlamat').textContent = alamat || 'Tidak ada';
      document.getElementById('detailKecamatan').textContent = kecamatan || 'Tidak ada';
      document.getElementById('detailKelurahan').textContent = kelurahan || 'Tidak ada';
      document.getElementById('detailFoto').src = foto;
      document.getElementById('detailStatus').textContent = status === 'active' ? 'Aktif' : 'Nonaktif';
      document.getElementById('detailTanggal').textContent = tanggal;
      
      document.getElementById('detailModal').style.display = 'flex';
    }
    
    function hideDetailModal() {
      document.getElementById('detailModal').style.display = 'none';
    }
    
    // Edit Modal Functions
    function showEditModal(id, nama, nik, email, telepon, alamat, kecamatan, kelurahan, status) {
      document.getElementById('editId').value = id;
      document.getElementById('editNama').value = nama;
      document.getElementById('editNIK').value = nik;
      document.getElementById('editEmail').value = email;
      document.getElementById('editTelepon').value = telepon;
      document.getElementById('editAlamat').value = alamat || '';
      document.getElementById('editKecamatan').value = kecamatan || '';
      document.getElementById('editKelurahan').value = kelurahan || '';
      document.getElementById('editStatus').value = status;
      
      document.getElementById('editModal').style.display = 'flex';
    }
    
    function hideEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }
    
    // Delete Modal Functions
    function confirmDelete(id, nama) {
      document.getElementById('deleteUserName').textContent = nama;
      document.getElementById('deleteUserLink').href = 'data_masyarakat.php?action=delete&id=' + id;
      document.getElementById('deleteModal').style.display = 'flex';
    }
    
    function hideDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Logout Modal Functions
    function showLogoutModal() {
      document.getElementById('logoutModal').style.display = 'flex';
    }
    
    function hideLogoutModal() {
      document.getElementById('logoutModal').style.display = 'none';
    }
    
    // Close modals when clicking outside content
    window.addEventListener('click', function(event) {
      const modals = document.getElementsByClassName('modal');
      for (let i = 0; i < modals.length; i++) {
        if (event.target === modals[i]) {
          modals[i].style.display = 'none';
        }
      }
    });
    
    // Auto-submit form when search input changes
    document.getElementById('searchInput').addEventListener('input', function() {
      if (this.value.length >= 3 || this.value.length === 0) {
        this.form.submit();
      }
    });
    
    // Close alert messages after 5 seconds
    setTimeout(function() {
      const alerts = document.getElementsByClassName('alert');
      for (let i = 0; i < alerts.length; i++) {
        alerts[i].style.display = 'none';
      }
    }, 5000);
  </script>
</body>
</html>