<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role masyarakat
if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] != 'masyarakat') {
    header('Location: beranda.php'); // Redirect ke halaman login jika pengguna belum login atau bukan masyarakat
    exit();
}

// Koneksi ke database
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "pengaduan_db";

$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_nik = isset($_SESSION['user_nik']) ? $_SESSION['user_nik'] : '';

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

// Proses pengaduan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $nik = $_POST['nik'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];
    $email = $_POST['email'];
    $judul = $_POST['judul'];
    $kategori = $_POST['kategori'];
    $deskripsi = $_POST['deskripsi'];
    $alamat_lokasi = $_POST['alamat_lokasi'];
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $maps_link = isset($_POST['maps_link']) ? $_POST['maps_link'] : null;
    $anonim = isset($_POST['anonim']) ? 1 : 0;

    // Proses upload file
    $foto = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $foto_tmp = $_FILES['foto']['tmp_name'];
        
        // Buat folder uploads jika belum ada
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        $foto_path = "uploads/" . $foto;
        move_uploaded_file($foto_tmp, $foto_path);
    }

    // Query untuk menyimpan pengaduan dengan data lokasi
    $stmt = $conn->prepare("INSERT INTO pengaduan (nama, nik, alamat, telepon, email, judul, kategori, deskripsi, foto, alamat_lokasi, latitude, longitude, maps_link, anonim) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssddsi", $nama, $nik, $alamat, $telepon, $email, $judul, $kategori, $deskripsi, $foto, $alamat_lokasi, $latitude, $longitude, $maps_link, $anonim);

    if ($stmt->execute()) {
        $msg = "Pengaduan berhasil diajukan!";
        $msgType = "success";
    } else {
        $msg = "Gagal mengajukan pengaduan: " . $stmt->error;
        $msgType = "error";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengaduan - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
  <link rel="icon" type="image/png" href="gambar/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
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
    
    /* Form Header */
    .form-header {
      background: linear-gradient(135deg, var(--primary), #E67E22);
      color: white;
      border-radius: var(--card-radius);
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
    
    .form-title {
      font-size: 26px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .form-subtitle {
      opacity: 0.9;
      max-width: 600px;
      line-height: 1.5;
    }
    
    .form-decoration {
      position: absolute;
      bottom: -50px;
      right: -20px;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }
    
    /* Form Container */
    .form-container {
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      padding: 30px;
      margin-bottom: 25px;
    }
    
    .form-section {
      margin-bottom: 30px;
    }
    
    .form-section:last-child {
      margin-bottom: 0;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
      display: flex;
      align-items: center;
    }
    
    .section-title i {
      margin-right: 10px;
      color: var(--primary);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin: 0 -10px;
    }
    
    .form-col {
      flex: 1;
      padding: 0 10px;
      min-width: 250px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--dark);
    }
    
    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      transition: var(--transition);
    }
    
    .form-control:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
      outline: none;
    }
    
    .form-text {
      font-size: 12px;
      color: #777;
      margin-top: 5px;
    }
    
    .form-check {
      display: flex;
      align-items: center;
      margin-top: 10px;
    }
    
    .form-check-input {
      margin-right: 10px;
    }
    
    /* File Upload */
    .file-upload {
      position: relative;
      display: inline-block;
      width: 100%;
    }
    
    .file-upload-label {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      padding: 25px;
      background-color: #f8f9fa;
      border: 2px dashed #ddd;
      border-radius: 5px;
      cursor: pointer;
      transition: var(--transition);
      text-align: center;
    }
    
    .file-upload-label:hover {
      background-color: #e9ecef;
      border-color: #ccc;
    }
    
    .file-upload-label i {
      font-size: 24px;
      margin-right: 10px;
      color: var(--primary);
    }
    
    .file-upload input[type="file"] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    /* Buttons */
    .form-buttons {
      display: flex;
      justify-content: space-between;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }
    
    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: 5px;
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
    
    /* Alerts */
    .alert {
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    
    .alert-success {
      background-color: rgba(46, 204, 113, 0.1);
      border-left: 4px solid var(--success);
      color: #27ae60;
    }
    
    .alert-error {
      background-color: rgba(231, 76, 60, 0.1);
      border-left: 4px solid var(--danger);
      color: #c0392b;
    }
    
    .alert i {
      font-size: 20px;
      margin-right: 10px;
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
      
      .form-col {
        flex: 0 0 100%;
        margin-bottom: 15px;
      }
    }
    
    @media (max-width: 576px) {
      .navbar {
        padding: 0 15px;
      }
      
      .navbar-title {
        font-size: 18px;
      }
      
      .form-container {
        padding: 20px 15px;
      }
      
      .form-buttons {
        flex-direction: column;
        gap: 10px;
      }
      
      .btn {
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
        <a href="dashboard_masyarakat.php" class="menu-link">
          <i class="fas fa-home menu-icon"></i>
          <span>Dashboard</span>
        </a>
      </div>
      
      <div class="menu-item">
        <a href="pengaduan.php" class="menu-link active">
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
      <div class="navbar-title">Formulir Pengaduan</div>
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
          <a href="data_akun.php"><i class="fas fa-user-circle"></i> Profil</a>
          <a href="#" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <div class="form-header">
      <h1 class="form-title">Formulir Pengaduan Online</h1>
      <p class="form-subtitle">Sampaikan pengaduan Anda mengenai masalah sosial yang terjadi di sekitar Anda.</p>
      <div class="form-decoration"></div>
    </div>
    
    <?php if(isset($msg)): ?>
    <div class="alert alert-<?php echo $msgType; ?>">
      <i class="fas fa-<?php echo $msgType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
      <?php echo $msg; ?>
    </div>
    <?php endif; ?>
    
    <div class="form-container">
      <form action="pengaduan.php" method="POST" enctype="multipart/form-data">
        <div class="form-section">
          <div class="section-title">
            <i class="fas fa-user"></i> Data Pelapor
          </div>
          
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
              </div>
            </div>
            
            <div class="form-col">
              <div class="form-group">
                <label for="nik" class="form-label">NIK</label>
                <input type="text" id="nik" name="nik" class="form-control" value="<?php echo htmlspecialchars($user_nik); ?>" required>
                <div class="form-text">Masukkan 16 digit Nomor Induk Kependudukan</div>
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="alamat" class="form-label">Alamat</label>
                <input type="text" id="alamat" name="alamat" class="form-control" required>
              </div>
            </div>
            
            <div class="form-col">
              <div class="form-group">
                <label for="telepon" class="form-label">Nomor Telepon</label>
                <input type="text" id="telepon" name="telepon" class="form-control" required>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
          </div>
          
          <div class="form-check">
            <input type="checkbox" id="anonim" name="anonim" class="form-check-input">
            <label for="anonim" class="form-check-label">Kirim sebagai pengaduan anonim (identitas pelapor tidak akan ditampilkan)</label>
          </div>
        </div>
        
        <div class="form-section">
          <div class="section-title">
            <i class="fas fa-clipboard-list"></i> Detail Pengaduan
          </div>
          
          <div class="form-group">
            <label for="judul" class="form-label">Judul Pengaduan</label>
            <input type="text" id="judul" name="judul" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="kategori" class="form-label">Kategori Pengaduan</label>
            <select id="kategori" name="kategori" class="form-control" required>
              <option value="" disabled selected>-- Pilih Kategori --</option>
              <option value="kemiskinan">Kemiskinan</option>
              <option value="anak_terlantar">Anak Terlantar</option>
              <option value="penyandang_disabilitas">Penyandang Disabilitas</option>
              <option value="lansia_terlantar">Lansia Terlantar</option>
              <option value="korban_bencana">Korban Bencana Alam</option>
              <option value="masalah_sosial_lain">Masalah Sosial Lainnya</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="deskripsi" class="form-label">Deskripsi Pengaduan</label>
            <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5" required></textarea>
            <div class="form-text">Jelaskan secara detail permasalahan yang Anda laporkan</div>
          </div>
          
          <div class="form-group">
            <label for="alamat_lokasi" class="form-label">Alamat Lokasi Kejadian</label>
            <input type="text" id="alamat_lokasi" name="alamat_lokasi" class="form-control" placeholder="Ketik alamat atau klik pada peta" required>
            <div class="form-text">Ketik alamat atau pilih lokasi pada peta di bawah</div>
          </div>

          <!-- Map Container -->
          <div class="form-group">
            <label class="form-label">Pilih Lokasi pada Peta</label>
            <div id="map" style="height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 8px;"></div>
            <div class="form-text">Klik pada peta untuk memilih lokasi kejadian</div>
          </div>

          <!-- Hidden input untuk menyimpan koordinat dan link maps -->
          <input type="hidden" id="latitude" name="latitude">
          <input type="hidden" id="longitude" name="longitude">
          <input type="hidden" id="maps_link" name="maps_link">
          
          <div class="form-group">
            <label class="form-label">Unggah Foto (opsional)</label>
            <div class="file-upload">
              <label for="foto" class="file-upload-label">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Klik atau seret file foto di sini</span>
              </label>
              <input type="file" id="foto" name="foto" accept="image/*">
            </div>
            <div class="form-text">Format yang diterima: JPG, JPEG, PNG. Maksimal 2MB</div>
          </div>
        </div>
        
        <div class="form-buttons">
          <button type="reset" class="btn btn-secondary">Reset</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Pengaduan</button>
        </div>
      </form>
    </div>
  </main>
  
  <!-- Footer -->
  <footer class="footer" id="footer">
    <div>© 2025 SILADU - Sistem Layanan Pengaduan Terpadu | Dinas Sosial Kota Palembang</div>
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
        <a href="logout.php" class="btn btn-primary">Ya, Keluar</a>
      </div>
    </div>
  </div>

  <!-- Load Leaflet JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

  <script>
    // Variabel global untuk peta
    let map;
    let marker;

    // Inisialisasi peta dengan OpenStreetMap
    function initMap() {
        console.log("Initializing OpenStreetMap...");
        
        // Koordinat default (Palembang)
        const defaultLocation = [-2.9760735, 104.7754307];
        
        // Inisialisasi peta menggunakan Leaflet
        map = L.map('map').setView(defaultLocation, 12);
        
        // Tambahkan tile layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Event listener untuk klik pada peta
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            console.log("Map clicked at:", lat, lng);
            
            // Update marker
            updateMarker(lat, lng);
            
            // Update input fields
            updateLocationFields(lat, lng);
            
            // Reverse geocoding menggunakan Nominatim API
            reverseGeocode(lat, lng);
        });
        
        // Coba dapatkan lokasi pengguna saat ini
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                // Cek apakah lokasi pengguna di area Palembang
                if (userLat > -3.1 && userLat < -2.8 && userLng > 104.5 && userLng < 105.0) {
                    map.setView([userLat, userLng], 14);
                    updateMarker(userLat, userLng);
                    updateLocationFields(userLat, userLng);
                    reverseGeocode(userLat, userLng);
                }
            }, function(error) {
                console.log("Geolocation error:", error);
            });
        }
        
        console.log("OpenStreetMap initialized successfully");
    }

    // Fungsi untuk update marker
    function updateMarker(lat, lng) {
        // Hapus marker lama jika ada
        if (marker) {
            map.removeLayer(marker);
        }
        
        // Buat marker baru
        marker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);
        
        // Event listener untuk drag marker
        marker.on('dragend', function(e) {
            const newLat = e.target.getLatLng().lat;
            const newLng = e.target.getLatLng().lng;
            
            updateLocationFields(newLat, newLng);
            reverseGeocode(newLat, newLng);
        });
    }

    // Fungsi untuk update field lokasi
    function updateLocationFields(lat, lng) {
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Buat link OpenStreetMap
        const mapsLink = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=16`;
        document.getElementById('maps_link').value = mapsLink;
        
        console.log("Location updated:", lat, lng);
    }

    // Fungsi untuk reverse geocoding menggunakan Nominatim API
    function reverseGeocode(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.display_name) {
                    document.getElementById('alamat_lokasi').value = data.display_name;
                    console.log("Address found:", data.display_name);
                } else {
                    console.log("No address found for coordinates");
                }
            })
            .catch(error => {
                console.error("Reverse geocoding error:", error);
            });
    }

    // Fungsi untuk geocoding (alamat ke koordinat)
    function geocodeAddress(address) {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    map.setView([lat, lng], 16);
                    updateMarker(lat, lng);
                    updateLocationFields(lat, lng);
                    
                    console.log("Geocoding successful:", lat, lng);
                } else {
                    console.log("Address not found");
                }
            })
            .catch(error => {
                console.error("Geocoding error:", error);
            });
    }

    // Event listener untuk input alamat
    document.getElementById('alamat_lokasi').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const address = this.value;
            if (address.trim() !== '') {
                geocodeAddress(address + ', Palembang');
            }
        }
    });

    // Inisialisasi peta saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });

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
    
    // Show/hide logout modal
    function showLogoutModal() {
      document.getElementById('logoutModal').style.display = 'flex';
    }
    
    function closeLogoutModal() {
      document.getElementById('logoutModal').style.display = 'none';
    }
    
    // File upload preview
    const fileInput = document.getElementById('foto');
    const fileLabel = document.querySelector('.file-upload-label span');
    
    fileInput.addEventListener('change', function() {
      if (this.files.length > 0) {
        const fileName = this.files[0].name;
        fileLabel.textContent = fileName;
      } else {
        fileLabel.textContent = 'Klik atau seret file foto di sini';
      }
    });
    
    // Form validation for NIK
    const nikInput = document.getElementById('nik');
    nikInput.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
      if (this.value.length > 16) {
        this.value = this.value.slice(0, 16);
      }
    });
    
    // Form validation for phone number
    const teleponInput = document.getElementById('telepon');
    teleponInput.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // Mobile responsive sidebar
    if (window.innerWidth <= 992) {
      sidebar.classList.add('collapsed');
      navbar.classList.add('full-width');
      mainContent.classList.add('full-width');
      footer.classList.add('full-width');
    }
  </script>
</body>
</html>