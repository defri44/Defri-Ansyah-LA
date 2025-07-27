<?php
session_start();

// Pastikan pengguna sudah login dan memiliki role masyarakat
if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] != 'masyarakat') {
    header('Location: beranda.php'); // Redirect ke halaman login jika pengguna belum login atau bukan masyarakat
    exit();
}

/// Koneksi ke database
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

// Hitung jumlah pengaduan berdasarkan status untuk pengguna yang login
$nik = $user_data['nik']; // Ambil NIK dari data pengguna

// Hitung pengaduan dengan status 'Ditolak' (rejected)
$sql_rejected = "SELECT COUNT(*) as total FROM pengaduan WHERE nik = ? AND status_pengaduan = 'ditolak'";
$stmt_rejected = $conn->prepare($sql_rejected);
$stmt_rejected->bind_param("s", $nik);
$stmt_rejected->execute();
$result_rejected = $stmt_rejected->get_result();
$row_rejected = $result_rejected->fetch_assoc();
$rejected_count = $row_rejected['total'];

// Hitung pengaduan dengan status 'Disetujui' (approved)
$sql_approved = "SELECT COUNT(*) as total FROM pengaduan WHERE nik = ? AND status_pengaduan = 'disetujui'";
$stmt_approved = $conn->prepare($sql_approved);
$stmt_approved->bind_param("s", $nik);
$stmt_approved->execute();
$result_approved = $stmt_approved->get_result();
$row_approved = $result_approved->fetch_assoc();
$approved_count = $row_approved['total'];

// Hitung pengaduan dengan status 'Diproses' (in progress)
$sql_process = "SELECT COUNT(*) as total FROM pengaduan WHERE nik = ? AND status_pengaduan = 'diproses'";
$stmt_process = $conn->prepare($sql_process);
$stmt_process->bind_param("s", $nik);
$stmt_process->execute();
$result_process = $stmt_process->get_result();
$row_process = $result_process->fetch_assoc();
$process_count = $row_process['total'];

// Hitung pengaduan dengan status 'Selesai' (completed)
$sql_completed = "SELECT COUNT(*) as total FROM pengaduan WHERE nik = ? AND status_pengaduan = 'selesai'";
$stmt_completed = $conn->prepare($sql_completed);
$stmt_completed->bind_param("s", $nik);
$stmt_completed->execute();
$result_completed = $stmt_completed->get_result();
$row_completed = $result_completed->fetch_assoc();
$completed_count = $row_completed['total'];


// Ambil data pengaduan terbaru untuk pengguna yang login
$sql_recent = "SELECT * FROM pengaduan WHERE nik = ? ORDER BY created_at DESC LIMIT 3";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("s", $nik);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
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
            --radius: 10px;
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
            padding: calc(var(--header-h) + 20px) 30px 80px;
            transition: var(--transition);
            min-height: 100vh;
        }
        
        .main-content.full-width {
            margin-left: 0;
        }
        
        /* Welcome Banner */
        .dashboard-welcome {
      background: linear-gradient(135deg, var(--primary), #E67E22);
      color: white;
      border-radius: var(--card-radius);
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        .welcome-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            opacity: 0.9;
            max-width: 80%;
            line-height: 1.6;
        }
        
        .welcome-decoration {
            position: absolute;
            bottom: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
        }
        
        .welcome-decoration:before {
            content: '';
            position: absolute;
            top: -30px;
            left: -30px;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-left: 10px;
            border-left: 4px solid var(--accent);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px 20px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background-color: rgba(76, 110, 245, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--secondary);
        }
        
        .action-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .action-desc {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        /* Status Overview */
        .status-overview {
            margin-bottom: 30px;
        }
        
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .status-card {
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .status-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-right: 20px;
            color: white;
        }
        
        .status-icon.pending {
            background-color: var(--warning);
        }
        
        .status-icon.process {
            background-color: var(--info);
        }
        
        .status-icon.completed {
            background-color: var(--success);
        }
        
        .status-info {
            flex-grow: 1;
        }
        
        .status-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .status-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* Recent Reports */
        .recent-reports {
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .reports-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .view-all {
            color: var(--secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .view-all:hover {
            color: var(--primary);
        }
        
        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .reports-table th, .reports-table td {
            padding: 15px 10px;
            text-align: left;
        }
        
        .reports-table th {
            font-weight: 500;
            color: #6c757d;
            font-size: 14px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .reports-table tr {
            border-bottom: 1px solid #f1f3f5;
        }
        
        .reports-table tr:last-child {
            border-bottom: none;
        }
        
        .report-title {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 3px;
        }
        
        .report-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        
        .status-process {
            background-color: rgba(23, 162, 184, 0.1);
            color: #138496;
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: #218838;
        }
        
        .status-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #c82333;
        }
        
        .action-btn {
            color: var(--secondary);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            padding: 5px 10px;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            color: var(--primary);
        }
        
        /* Help Section */
        .help-section {
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .help-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .help-text {
            flex: 1;
        }
        
        .help-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .help-desc {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .help-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .help-btn:hover {
            background-color: #c0982e;
            transform: translateY(-2px);
        }
        
        .help-image {
            flex: 0 0 150px;
            text-align: center;
        }
        
        .help-image img {
            max-width: 100%;
            height: auto;
        }
        
        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: var(--sidebar-w);
            right: 0;
            background-color: var(--accent);
            color: white;
            text-align: center;
            padding: 15px 20px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .footer.full-width {
            left: 0;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius);
            width: 90%;
            max-width: 400px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-icon {
            font-size: 50px;
            color: var(--warning);
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .modal-text {
            color: #6c757d;
            margin-bottom: 25px;
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .btn-confirm {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-confirm:hover {
            background-color: #bd2130;
        }
        
        .btn-cancel {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .btn-cancel:hover {
            background-color: #dde2e6;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .header, .main-content, .footer {
                left: 0;
            }
            
            .status-cards, .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .help-content {
                flex-direction: column;
                text-align: center;
            }
            
            .help-image {
                margin-top: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .status-cards, .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-subtitle {
                max-width: 100%;
            }
            
            .reports-table th:nth-child(3), 
            .reports-table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>

<body>
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
        <a href="dashboard_admin.php" class="menu-link active">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
        </a>
      </div>
      
      <div class="menu-item">
        <a href="pengaduan.php" class="menu-link ">
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
      <div class="navbar-title">Pengaduan</div>
    </div>
    
    <div class="navbar-right">
      <div class="user-dropdown" onclick="toggleDropdown()">
        <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" class="user-dropdown-img" onerror="this.src='default.png'">
      </div>
      
      <div class="dropdown-content" id="userDropdown">
        <div class="dropdown-panel">
          <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" onerror="this.src='default.png'">
          <h4><?php echo $_SESSION['user_name']; ?></h4>
          <small><?php echo $_SESSION['user_role']; ?></small>
        </div>
        
        <div class="dropdown-menu">
          <a href="data_akun.php"><i class="fas fa-user-circle"></i> Profil</a>
          <a href="#" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>
    </div>
  </nav>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Welcome Banner -->
        <div class="dashboard-welcome">
      <h1 class="welcome-title">Selamat Datang, <?php echo $_SESSION['user_name']; ?>!</h1>
      <p class="welcome-subtitle">Pantau dan kelola laporan pengaduan masyarakat melalui sistem SILADU.</p>
      <div class="welcome-decoration"></div>
    </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title">Aksi Cepat</h2>
            <div class="actions-grid">
             <div class="action-card" onclick="window.location.href='pengaduan.php'">
                    <div class="action-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="action-title">Buat Pengaduan</h3>
                    <p class="action-desc">Laporkan masalah atau keluhan yang Anda temui</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='status_pengaduan_masyarakat.php'">
                    <div class="action-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="action-title">Cek Status</h3>
                    <p class="action-desc">Lihat status pengaduan yang telah Anda ajukan</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='data_akun.php'">
                    <div class="action-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3 class="action-title">Kelola Akun</h3>
                    <p class="action-desc">Perbarui informasi profil dan akun Anda</p>
                </div>
            </div>
        </div>
        
        <!-- Status Overview -->
        <div class="status-overview">
            <h2 class="section-title">Ringkasan Pengaduan Anda</h2>
            <div class="status-cards">
                <div class="status-card">
                    <div class="status-icon pending">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="status-info">
                        <div class="status-value"><?php echo $rejected_count; ?></div>
                        <div class="status-label">Pengaduan Ditolak</div>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-icon process" style="background-color: #3498DB;">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="status-info">
                        <div class="status-value"><?php echo $process_count; ?></div>
                        <div class="status-label">Sedang Diproses</div>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-icon completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="status-info">
                        <div class="status-value"><?php echo $completed_count; ?></div>
                        <div class="status-label">Pengaduan Selesai</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Reports -->
        <div class="recent-reports">
            <div class="reports-header">
                <h2 class="reports-title">Pengaduan Terbaru Anda</h2>
                <a href="status_pengaduan_masyarakat.php" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Judul Pengaduan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_recent->num_rows > 0) {
                        while ($row = $result_recent->fetch_assoc()) {
                            // Format tanggal
                            $tanggal = date('d M Y', strtotime($row['created_at']));
                            
                            // Tentukan kelas untuk status
                            $status_class = '';
switch ($row['status_pengaduan']) {
    case 'ditolak':
        $status_class = 'status-rejected';
        break;
    case 'disetujui':
        $status_class = 'status-approved'; // New status for 'disetujui'
        break;
    case 'diproses':
        $status_class = 'status-process';
        break;
    case 'selesai':
        $status_class = 'status-completed';
        break;
    default:
        $status_class = 'status-pending'; // Fallback for any unexpected value
}
                            
                            echo "<tr>";
            echo "<td>";
            echo "<div class='report-title'>" . htmlspecialchars($row['judul']) . "</div>";
            echo "<div class='report-date'>" . $tanggal . "</div>";
            echo "</td>";
            echo "<td>" . $tanggal . "</td>";
            echo "<td><span class='status-badge " . $status_class . "'>" . ucfirst($row['status_pengaduan']) . "</span></td>";
            echo "<td><a href='detail_pengaduan.php?id=" . $row['id_pengaduan'] . "' class='action-btn'><i class='fas fa-eye'></i> Detail</a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4' style='text-align:center;'>Belum ada pengaduan yang dibuat</td></tr>";
    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer" id="footer">
        <p>&copy; <?php echo date('Y'); ?> SILADU - Sistem Layanan Pengaduan Terpadu | Dinas Sosial Kota Palembang</p>
    </footer>
    
    <!-- Logout Modal -->
    <div class="modal" id="logoutModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 class="modal-title">Konfirmasi Keluar</h3>
            <p class="modal-text">Apakah Anda yakin ingin keluar dari sistem?</p>
            <div class="modal-actions">
                <a href="logout.php" class="btn btn-confirm">Ya, Keluar</a>
                <button class="btn btn-cancel" onclick="closeLogoutModal()">Batal</button>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
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
        
        // Toggle dropdown
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.user-dropdown-img')) {
                const dropdowns = document.getElementsByClassName('dropdown-content');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
        
        // Logout modal
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }
        
        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target === modal) {
                closeLogoutModal();
            }
        });
    </script>
</body>
</html>   