<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role masyarakat
if (
    !isset($_SESSION['user_name']) || 
    ($_SESSION['user_role'] != 'masyarakat' && 
     $_SESSION['user_role'] != 'admin' && 
     $_SESSION['user_role'] != 'kepala_dinas')
) {
    header('Location: beranda.php'); // Redirect ke halaman login jika pengguna belum login atau bukan masyarakat/admin/kepala_dinas
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

// Proses update data akun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    $kecamatan = $_POST['kecamatan'];
    $kelurahan = $_POST['kelurahan'];

    // Update password jika diisi
    $password_update = "";
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['password'] === $_POST['confirm_password']) {
            // Hash password
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ", password = ?";
        } else {
            $msg = "Konfirmasi password tidak cocok!";
            $msgType = "error";
        }
    }

    $foto_update = "";
    $new_filename = null;
    
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png');
        $filename = $_FILES['foto_profil']['name']; // Nama file asli
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Generate nama file unik untuk menghindari konflik
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
            
            // Move uploaded file to the destination folder
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_dir . $new_filename)) {
                // Hapus foto lama jika ada dan bukan foto default
                if (!empty($user_data['foto_profil']) && $user_data['foto_profil'] != 'default.jpg' && file_exists($upload_dir . $user_data['foto_profil'])) {
                    unlink($upload_dir . $user_data['foto_profil']); // Delete old file
                }

                // Flag foto untuk update
                $foto_update = ", foto_profil = ?"; 
            } else {
                $msg = "Gagal mengupload foto!";
                $msgType = "error";
            }
        } else {
            $msg = "Format file tidak didukung. Gunakan JPG, JPEG, atau PNG.";
            $msgType = "error";
        }
    }

    // Siapkan query untuk update
    $sql = "UPDATE users SET nama = ?, email = ?, telepon = ?, alamat = ?, kecamatan = ?, kelurahan = ?";
    $types = "ssssss"; // string, string, string, string, string, string
    $params = array($nama, $email, $telepon, $alamat, $kecamatan, $kelurahan);
    
    // Tambahkan password ke query jika diupdate
    if (!empty($password_update)) {
        $sql .= $password_update;
        $types .= "s";
        $params[] = $hashed_password;
    }
    
    // Tambahkan foto ke query jika diupdate
    if (!empty($foto_update)) {
        $sql .= $foto_update;
        $types .= "s";
        $params[] = $new_filename;
    }
    
    // Tambahkan where clause
    $sql .= " WHERE id = ?";
    $types .= "i";
    $params[] = $user_id;
    
    // Persiapkan dan eksekusi query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['user_name'] = $nama;
        
        // Update foto profil di session jika ada upload foto baru
        if (!empty($new_filename)) {
            $_SESSION['user_foto_profil'] = $new_filename;
            $fotoprofil = $upload_dir . $new_filename;
        }
        
        $msg = "Data akun berhasil diperbarui!";
        $msgType = "success";
        
        // Refresh data pengguna
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
    } else {
        $msg = "Gagal memperbarui data akun: " . $stmt->error;
        $msgType = "error";
    }
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akun - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
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
    
    /* Account Header */
    .account-header {
      background: linear-gradient(135deg, var(--primary), #E67E22);
      color: white;
      border-radius: var(--card-radius);
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
    
    .account-title {
      font-size: 26px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .account-subtitle {
      opacity: 0.9;
      max-width: 600px;
      line-height: 1.5;
    }
    
    .account-decoration {
      position: absolute;
      bottom: -50px;
      right: -20px;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }
    
    /* Account Container */
    .account-container {
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      margin-bottom: 25px;
      overflow: hidden;
    }
    
    /* Profile Photo Section */
    .profile-photo-section {
      text-align: center;
      padding: 30px;
      background-color: #f8f9fa;
      border-bottom: 1px solid #eee;
    }
    
    .profile-photo {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid white;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 15px;
    }
    
    .photo-upload-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
    }
    
    .photo-upload-btn:hover {
      background-color: #E67E22;
    }
    
    .photo-upload-btn i {
      margin-right: 8px;
    }
    
    /* Form Section */
    .form-section {
      padding: 30px;
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
      justify-content: space-between;
    }
    
    .section-title i {
      margin-right: 10px;
      color: var(--primary);
    }

    .edit-button {
      background-color: var(--accent);
      color: white;
      border: none;
      padding: 5px 12px;
      border-radius: 4px;
      font-size: 13px;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
    }

    .edit-button i {
      margin-right: 5px;
      color: white;
    }

    .edit-button:hover {
      background-color: #2980b9;
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
      background-color: white;
    }
    
    .form-control:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
      outline: none;
    }

    .form-control:disabled {
      background-color: #f8f9fa;
      cursor: not-allowed;
    }
    
    .form-text {
      font-size: 12px;
      color: #777;
      margin-top: 5px;
    }
    
    /* Buttons */
    .form-buttons {
      display: flex;
      justify-content: flex-end;
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
      margin-right: 10px;
    }
    
    .btn-secondary:hover {
      background-color: #dee2e6;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
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

    /* Preview Photo */
    .image-preview-container {
      margin-top: 15px;
      display: none;
    }

    .image-preview {
      max-width: 200px;
      max-height: 200px;
      border-radius: 5px;
      border: 2px solid #ddd;
    }
    
    /* Additional styles for account page */
    .account-info {
      background-color: #f8f9fa;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .account-info-item {
      display: flex;
      margin-bottom: 10px;
    }
    
    .account-info-label {
      font-weight: 500;
      width: 120px;
      color: #666;
    }
    
    .account-info-value {
      flex: 1;
      color: var(--dark);
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
      
      .form-container, .form-section {
        padding: 20px 15px;
      }
      
      .form-buttons {
        flex-direction: column;
        gap: 10px;
      }
      
      .btn {
        width: 100%;
      }
      
      .btn-secondary {
        margin-right: 0;
        margin-bottom: 10px;
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
      <div class="navbar-title">Data Akun</div>
    </div>
    
    <div class="navbar-right">
      <div class="user-dropdown" onclick="toggleDropdown()">
        <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" class="user-dropdown-img" onerror="this.src='gambar/default.png'">
      </div>
      
      <div class="dropdown-content" id="userDropdown">
        <div class="dropdown-panel">
          <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" onerror="this.
          src='gambar/default.png'">
          <h4><?php echo $_SESSION['user_name']; ?></h4>
          <small><?php echo $_SESSION['user_role']; ?></small>
        </div>
        <div class="dropdown-menu">
          <a href="data_akun.php">
            <i class="fas fa-user-circle"></i>
            Profil Saya
          </a>
          <a href="#" onclick="showLogoutModal(); return false;">
            <i class="fas fa-sign-out-alt"></i>
            Keluar
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <!-- Alert Message -->
    <?php if(isset($msg)): ?>
    <div class="alert alert-<?php echo $msgType; ?>">
      <i class="fas fa-<?php echo $msgType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
      <?php echo $msg; ?>
    </div>
    <?php endif; ?>
    
    <!-- Account Header -->
    <div class="account-header">
      <h1 class="account-title">Data Akun</h1>
      <p class="account-subtitle">Kelola informasi akun dan ubah pengaturan akun Anda</p>
      <div class="account-decoration"></div>
    </div>
    
    <!-- Account Container -->
    <div class="account-container">
      <form action="" method="post" enctype="multipart/form-data">
        <!-- Profile Photo Section -->
        <div class="profile-photo-section">
          <img src="<?php echo $fotoprofil; ?>" alt="Foto Profil" class="profile-photo" id="currentPhoto" onerror="this.src='gambar/default.png'">
          
          <!-- Image Preview Container -->
          <div class="image-preview-container" id="imagePreviewContainer">
            <img src="" alt="Preview" class="image-preview" id="imagePreview">
          </div>
          
          <label for="foto_profil" class="photo-upload-btn" id="uploadBtn" style="display:none;">
            <i class="fas fa-camera"></i>
            Ubah Foto
          </label>
          <input type="file" name="foto_profil" id="foto_profil" style="display: none;" accept="image/jpeg, image/png, image/jpg">
          <div class="form-text">Gunakan foto dengan ukuran maksimal 2MB (format: JPG, JPEG, PNG)</div>
        </div>
        
        <!-- Form Section -->
        <div class="form-section">
          <div class="section-title">
            <div>
              <i class="fas fa-user"></i>
              Informasi Pribadi
            </div>
            <button type="button" class="edit-button" id="editBtn">
              <i class="fas fa-edit"></i>
              Edit
            </button>
          </div>
          
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama" id="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" disabled required>
              </div>
            </div>
            <div class="form-col">
              <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled required>
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="telepon" class="form-label">Nomor Telepon</label>
                <input type="text" class="form-control" name="telepon" id="telepon" value="<?php echo htmlspecialchars($user_data['telepon']); ?>" disabled required>
              </div>
            </div>
            <div class="form-col">
              <div class="form-group">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" name="alamat" id="alamat" rows="3" disabled required><?php echo htmlspecialchars($user_data['alamat'] ?? ''); ?></textarea>
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="kecamatan" class="form-label">Kecamatan</label>
                <input type="text" class="form-control" name="kecamatan" id="kecamatan" value="<?php echo htmlspecialchars($user_data['kecamatan'] ?? ''); ?>" disabled>
              </div>
            </div>
            <div class="form-col">
              <div class="form-group">
                <label for="kelurahan" class="form-label">Kelurahan</label>
                <input type="text" class="form-control" name="kelurahan" id="kelurahan" value="<?php echo htmlspecialchars($user_data['kelurahan'] ?? ''); ?>" disabled>
              </div>
            </div>
          </div>
          
          <!-- Password Section -->
          <div class="section-title" style="margin-top: 30px;">
            <div>
              <i class="fas fa-lock"></i>
              Ubah Password
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="password" class="form-label">Password Baru</label>
                <input type="password" class="form-control" name="password" id="password" disabled>
                <div class="form-text">Biarkan kosong jika tidak ingin mengubah password</div>
              </div>
            </div>
            <div class="form-col">
              <div class="form-group">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" disabled>
              </div>
            </div>
          </div>
          
          <!-- Buttons -->
          <div class="form-buttons">
            <button type="button" class="btn btn-secondary" id="cancelBtn" style="display:none;">Batal</button>
            <button type="submit" class="btn btn-primary" name="update_account" id="saveBtn" style="display:none;">Simpan Perubahan</button>
          </div>
        </div>
      </form>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer" id="footer">
    <div>&copy; <?php echo date('Y'); ?> SILADU - Sistem Informasi Pengaduan Dinas Sosial Kota Palembang</div>
  </footer>

  <!-- Logout Modal -->
  <div class="modal" id="logoutModal">
    <div class="modal-content">
      <div class="modal-icon">
        <i class="fas fa-sign-out-alt"></i>
      </div>
      <div class="modal-title">Konfirmasi Keluar</div>
      <div class="modal-message">Apakah Anda yakin ingin keluar dari sistem?</div>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeLogoutModal()">Batal</button>
        <a href="logout.php" class="btn btn-primary">Ya, Keluar</a>
      </div>
    </div>
  </div>

  <script>
    // Toggle sidebar
    const sidebar = document.getElementById('sidebar');
    const navbar = document.getElementById('navbar');
    const mainContent = document.getElementById('mainContent');
    const footer = document.getElementById('footer');
    const toggleBtn = document.getElementById('toggleSidebar');
    
    toggleBtn.addEventListener('click', function() {
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
    window.addEventListener('click', function(event) {
      if (!event.target.matches('.user-dropdown') && !event.target.matches('.user-dropdown-img')) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown.classList.contains('show')) {
          dropdown.classList.remove('show');
        }
      }
    });
    
    // Show/Hide Logout Modal
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
    
    // Preview uploaded image
    document.getElementById('foto_profil').addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const previewContainer = document.getElementById('imagePreviewContainer');
          const imagePreview = document.getElementById('imagePreview');
          
          imagePreview.src = e.target.result;
          previewContainer.style.display = 'block';
          document.getElementById('currentPhoto').style.display = 'none';
        };
        reader.readAsDataURL(file);
      }
    });
    
    // Edit mode functionality
const editBtn = document.getElementById('editBtn');
const saveBtn = document.getElementById('saveBtn');
const cancelBtn = document.getElementById('cancelBtn');
const uploadBtn = document.getElementById('uploadBtn');
const formFields = document.querySelectorAll('.form-control');
    // Initially hide upload button if not in edit mode
document.addEventListener('DOMContentLoaded', function() {
  uploadBtn.style.display = 'none';
});

    editBtn.addEventListener('click', function() {
  // Enable all form fields
  formFields.forEach(field => {
    field.disabled = false;
  });
      
      /// Show save and cancel buttons
  saveBtn.style.display = 'block';
  cancelBtn.style.display = 'block';
  uploadBtn.style.display = 'inline-flex';
      
      // Hide edit button
  editBtn.style.display = 'none';
});
    
    cancelBtn.addEventListener('click', function() {
      // Disable all form fields
      formFields.forEach(field => {
        field.disabled = true;
      });
      
      // Reset form (reloads the page)
      window.location.reload();
      
      // Hide save and cancel buttons
      saveBtn.style.display = 'none';
      cancelBtn.style.display = 'none';
      uploadBtn.style.display = 'none';
      
      // Show edit button
      editBtn.style.display = 'inline-flex';
      
      // Hide image preview if it exists
      document.getElementById('imagePreviewContainer').style.display = 'none';
      document.getElementById('currentPhoto').style.display = 'block';
    });
  </script>
</body>
</html>