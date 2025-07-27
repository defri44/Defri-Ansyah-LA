<?php
session_start();
// Pastikan pengguna sudah login sebelum menampilkan halaman
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

// Hitung jumlah pengaduan berdasarkan status
$status_count = array(
    'diproses' => 0,
    'disetujui' => 0,
    'selesai' => 0,
    'ditolak' => 0
);

$sql_count = "SELECT status, COUNT(*) as jumlah FROM status_pengaduan GROUP BY status";
$result_count = $conn->query($sql_count);

if ($result_count && $result_count->num_rows > 0) {
    while ($row = $result_count->fetch_assoc()) {
        $status_count[$row['status']] = $row['jumlah'];
    }
}

// Hitung total pengaduan
$total_pengaduan = array_sum($status_count);

// Data untuk grafik per bulan (6 bulan terakhir)
$months = array();
$data_per_month = array();

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    
    $start_date = $month . "-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $sql_monthly = "SELECT status, COUNT(*) as jumlah FROM status_pengaduan 
                   WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59' 
                   GROUP BY status";
    $result_monthly = $conn->query($sql_monthly);
    
    $monthly_data = array(
        'diproses' => 0,
        'disetujui' => 0,
        'selesai' => 0,
        'ditolak' => 0
    );
    
    if ($result_monthly && $result_monthly->num_rows > 0) {
        while ($row = $result_monthly->fetch_assoc()) {
            $monthly_data[$row['status']] = (int)$row['jumlah'];
        }
    }
    
    $data_per_month[] = $monthly_data;
}

// Data untuk grafik pie chart
$pie_data = json_encode(array(
    array('status' => 'Dalam Proses', 'value' => $status_count['diproses']),
    array('status' => 'Ditanggapi', 'value' => $status_count['disetujui']),
    array('status' => 'Selesai', 'value' => $status_count['selesai']),
    array('status' => 'Ditolak', 'value' => $status_count['ditolak'])
));

// Data untuk grafik batang
$bar_data = json_encode(array(
    array(
        'bulan' => $months[0],
        'Diproses' => $data_per_month[0]['diproses'],
        'Disetujui' => $data_per_month[0]['disetujui'],
        'Selesai' => $data_per_month[0]['selesai'],
        'Ditolak' => $data_per_month[0]['ditolak']
    ),
    array(
        'bulan' => $months[1],
        'Diproses' => $data_per_month[1]['diproses'],
        'Disetujui' => $data_per_month[1]['disetujui'],
        'Selesai' => $data_per_month[1]['selesai'],
        'Ditolak' => $data_per_month[1]['ditolak']
    ),
    array(
        'bulan' => $months[2],
        'Diproses' => $data_per_month[2]['diproses'],
        'Disetujui' => $data_per_month[2]['disetujui'],
        'Selesai' => $data_per_month[2]['selesai'],
        'Ditolak' => $data_per_month[2]['ditolak']
    ),
    array(
        'bulan' => $months[3],
        'Diproses' => $data_per_month[3]['diproses'],
        'Disetujui' => $data_per_month[3]['disetujui'],
        'Selesai' => $data_per_month[3]['selesai'],
        'Ditolak' => $data_per_month[3]['ditolak']
    ),
    array(
        'bulan' => $months[4],
        'Diproses' => $data_per_month[4]['diproses'],
        'Disetujui' => $data_per_month[4]['disetujui'],
        'Selesai' => $data_per_month[4]['selesai'],
        'Ditolak' => $data_per_month[4]['ditolak']
    ),
    array(
        'bulan' => $months[5],
        'Diproses' => $data_per_month[5]['diproses'],
        'Disetujui' => $data_per_month[5]['disetujui'],
        'Selesai' => $data_per_month[5]['selesai'],
        'Ditolak' => $data_per_month[5]['ditolak']
    )
));
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
  <!-- Tambahkan Chart.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
    
    /* Dashboard Welcome */
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
    
    .welcome-title {
      font-size: 26px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .welcome-subtitle {
      opacity: 0.9;
      max-width: 600px;
      line-height: 1.5;
    }
    
    .welcome-decoration {
      position: absolute;
      bottom: -50px;
      right: -20px;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }
    
    /* Status Cards */
.status-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
  width: 100%;
}

.status-card {
  background-color: white;
  border-radius: var(--card-radius);
  box-shadow: var(--shadow);
  padding: 20px;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.status-card:hover {
  transform: translateY(-5px);
}

.status-card.in-progress {
  border-top: 3px solid var(--warning);
}

.status-card.pending {
  border-top: 3px solid var(--accent);
}

.status-card.completed {
  border-top: 3px solid var(--success);
}

.status-card.rejected {
  border-top: 3px solid var(--danger);
}

.status-icon {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: white;
  font-size: 18px;
}

.status-icon.in-progress {
  background-color: var(--warning);
}

.status-icon.pending {
  background-color: var(--accent);
}

.status-icon.completed {
  background-color: var(--success);
}

.status-icon.rejected {
  background-color: var(--danger);
}

.status-value {
  font-size: 40px;
  font-weight: 700;
  color: var(--dark);
  margin: 10px 0;
}

.status-label {
  color: #777;
  font-size: 15px;
  margin-bottom: auto;
}

.status-info {
  display: inline-block;
  padding: 6px 12px;
  background-color: #f8f9fa;
  border-radius: 20px;
  font-size: 12px;
  margin-top: 12px;
  cursor: pointer;
  transition: var(--transition);
  align-self: flex-start;
}

.status-info:hover {
  background-color: #e9ecef;
}

/* Media queries untuk responsivitas yang lebih baik */
@media (max-width: 1200px) {
  .status-cards {
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  }
}

@media (max-width: 768px) {
  .status-cards {
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  }
}

@media (max-width: 576px) {
  .status-cards {
    grid-template-columns: 1fr;
  }
}
    
    /* Charts Section */
    .charts-section {
      margin-bottom: 25px;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .card {
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .card-header {
      padding-bottom: 15px;
      margin-bottom: 15px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .chart-container {
      height: 300px;
      position: relative;
    }
    
    /* Charts Grid */
    .charts-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    @media (max-width: 992px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }
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
    
    /* Modal */
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
      max-width: 400px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .modal-icon {
      font-size: 48px;
      color: var(--warning);
      margin-bottom: 15px;
    }
    
    .modal-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
    }
    
    .modal-message {
      color: #777;
      margin-bottom: 20px;
    }
    
    .modal-actions {
      display: flex;
      justify-content: center;
      gap: 15px;
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
    }
    
    .btn-secondary:hover {
      background-color: #dee2e6;
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
          <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
          <a href="#" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <div class="dashboard-welcome">
      <h1 class="welcome-title">Selamat Datang, <?php echo $_SESSION['user_name']; ?>!</h1>
      <p class="welcome-subtitle">Pantau dan kelola laporan pengaduan masyarakat melalui sistem SILADU.</p>
      <div class="welcome-decoration"></div>
    </div>
    
    <div class="status-cards">
      <div class="status-card in-progress">
        <div class="status-icon in-progress">
          <i class="fas fa-spinner"></i>
        </div>
        <div class="status-value"><?php echo $status_count['diproses']; ?></div>
        <div class="status-label">Dalam Proses</div>
        <a href="status_pengaduan.php?status=diproses" class="status-info">Lihat Detail <i class="fas fa-arrow-right"></i></a>
      </div>
      
      <div class="status-card pending">
        <div class="status-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="status-value"><?php echo $status_count['disetujui']; ?></div>
        <div class="status-label">Pengaduan Ditanggapi</div>
        <a href="status_pengaduan.php?status=disetujui" class="status-info">Lihat Detail <i class="fas fa-arrow-right"></i></a>
      </div>
      
      <div class="status-card completed">
        <div class="status-icon completed">
          <i class="fas fa-check"></i>
        </div>
        <div class="status-value"><?php echo $status_count['selesai']; ?></div>
        <div class="status-label">Pengaduan Selesai</div>
        <a href="status_pengaduan.php?status=selesai" class="status-info">Lihat Detail <i class="fas fa-arrow-right"></i></a>
      </div>
      
      <div class="status-card rejected">
        <div class="status-icon rejected">
          <i class="fas fa-times"></i>
        </div>
        <div class="status-value"><?php echo $status_count['ditolak']; ?></div>
        <div class="status-label">Pengaduan Ditolak</div>
        <a href="status_pengaduan.php?status=ditolak" class="status-info">Lihat Detail <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <div class="charts-section">
      <div class="section-header">
        <h2 class="section-title">Statistik Pengaduan</h2>
      </div>
      
      <div class="charts-grid">
        <!-- Bar Chart - Pengaduan Per Bulan -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Pengaduan Per Bulan (6 Bulan Terakhir)</h3>
          </div>
          <div class="chart-container">
            <canvas id="barChart"></canvas>
          </div>
        </div>
        
        <!-- Pie Chart - Status Pengaduan -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Status Pengaduan</h3>
          </div>
          <div class="chart-container">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </main>
  
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
      <p class="modal-message">Apakah Anda yakin ingin keluar dari sistem?</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeLogoutModal()">Batal</button>
        <a href="logout.php" class="btn btn-primary">Keluar</a>
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
    
    // User dropdown
    function toggleDropdown() {
      document.getElementById('userDropdown').classList.toggle('show');
    }
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
      if (!event.target.matches('.user-dropdown') && !event.target.matches('.user-dropdown-img')) {
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
    
    // Charts
    window.onload = function() {
      // Bar Chart - Pengaduan Per Bulan
      const barChartCanvas = document.getElementById('barChart').getContext('2d');
      const barChartData = <?php echo $bar_data; ?>;
      
      new Chart(barChartCanvas, {
        type: 'bar',
        data: {
          labels: barChartData.map(d => d.bulan),
          datasets: [
            {
              label: 'Dalam Proses',
              data: barChartData.map(d => d.Diproses),
              backgroundColor: '#F1C40F',
              borderWidth: 0
            },
            {
              label: 'Ditanggapi',
              data: barChartData.map(d => d.Disetujui),
              backgroundColor: '#3498DB',
              borderWidth: 0
            },
            {
              label: 'Selesai',
              data: barChartData.map(d => d.Selesai),
              backgroundColor: '#2ECC71',
              borderWidth: 0
            },
            {
              label: 'Ditolak',
              data: barChartData.map(d => d.Ditolak),
              backgroundColor: '#E74C3C',
              borderWidth: 0
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              grid: {
                display: false
              }
            },
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          },
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
      
      // Pie Chart - Status Pengaduan
      const pieChartCanvas = document.getElementById('pieChart').getContext('2d');
      const pieChartData = <?php echo $pie_data; ?>;
      
      new Chart(pieChartCanvas, {
        type: 'pie',
        data: {
          labels: pieChartData.map(d => d.status),
          datasets: [{
            data: pieChartData.map(d => d.value),
            backgroundColor: ['#F1C40F', '#3498DB', '#2ECC71', '#E74C3C'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    };
  </script>
</body>
</html>