<?php
session_start();
// Pastikan pengguna sudah login sebelum menampilkan halaman
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role'])) {
    header('Location: beranda.php'); // Redirect ke halaman login jika belum login
    exit();
}

// Pastikan hanya admin yang bisa mengakses halaman ini
if ($_SESSION['user_role'] != 'admin') {
    header('Location: beranda.php'); // Redirect ke dashboard jika bukan admin
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

// Process pengaduan dengan bukti proses
if (isset($_POST['process_with_evidence']) && isset($_POST['id_pengaduan'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $status = "diproses";
    
    // Handle file upload
    $bukti_proses = '';
    if (isset($_FILES['bukti_proses']) && $_FILES['bukti_proses']['error'] == 0) {
        $upload_dir = 'uploads/bukti_proses/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_tmp = $_FILES['bukti_proses']['tmp_name'];
        $file_name = basename($_FILES['bukti_proses']['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Generate unique filename
        $new_file_name = 'bukti_proses_' . $id_pengaduan . '_' . date('YmdHis') . '.' . $file_ext;
        
        // Upload file
        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $bukti_proses = $new_file_name;
        } else {
            $_SESSION['error_message'] = "Gagal mengunggah file bukti proses.";
            header("Location: status_pengaduan.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Bukti proses harus diunggah.";
        header("Location: status_pengaduan.php");
        exit();
    }
    
    // Check if record exists in status_pengaduan table
    $checkQuery = "SELECT id_status FROM status_pengaduan WHERE id_pengaduan = '$id_pengaduan'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Update existing record
        $row = mysqli_fetch_assoc($checkResult);
        $id_status = $row['id_status'];
        
        $updateStatusQuery = "UPDATE status_pengaduan 
                             SET status = '$status', 
                                 keterangan = '$keterangan',
                                 bukti_proses = '$bukti_proses',
                                 updated_at = NOW() 
                             WHERE id_status = '$id_status'";
        
        if (mysqli_query($conn, $updateStatusQuery)) {
            // Update status in pengaduan table
            $updatePengaduanQuery = "UPDATE pengaduan 
                                    SET status_pengaduan = '$status' 
                                    WHERE id_pengaduan = '$id_pengaduan'";
            
            if (mysqli_query($conn, $updatePengaduanQuery)) {
                $_SESSION['success_message'] = "Status pengaduan berhasil diubah menjadi diproses dengan bukti proses.";
            } else {
                $_SESSION['error_message'] = "Error updating pengaduan: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error updating status: " . mysqli_error($conn);
        }
    } else {
        // Insert new record if no existing record found (fallback)
        $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, status, keterangan, bukti_proses) 
                        VALUES ('$id_pengaduan', '$status', '$keterangan', '$bukti_proses')";
        
        if (mysqli_query($conn, $insertQuery)) {
            // Update status in pengaduan table
            $updateQuery = "UPDATE pengaduan 
                           SET status_pengaduan = '$status' 
                           WHERE id_pengaduan = '$id_pengaduan'";
            
            if (mysqli_query($conn, $updateQuery)) {
                $_SESSION['success_message'] = "Status pengaduan berhasil diubah menjadi diproses dengan bukti proses.";
            } else {
                $_SESSION['error_message'] = "Error updating pengaduan: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
        }
    }

    // Redirect to refresh the page
    header("Location: status_pengaduan.php");
    exit();
}

if (isset($_GET['process']) && isset($_GET['id'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_GET['id']);
    $status = "diproses";
    $keterangan = "Pengaduan sedang dalam proses penanganan";

    // Check if record exists in status_pengaduan table
    $checkQuery = "SELECT id_status FROM status_pengaduan WHERE id_pengaduan = '$id_pengaduan'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Update existing record
        $row = mysqli_fetch_assoc($checkResult);
        $id_status = $row['id_status'];
        
        $updateStatusQuery = "UPDATE status_pengaduan 
                             SET status = '$status', 
                                 keterangan = '$keterangan',
                                 updated_at = NOW() 
                             WHERE id_status = '$id_status'";
        
        if (mysqli_query($conn, $updateStatusQuery)) {
            // Update status in pengaduan table
            $updatePengaduanQuery = "UPDATE pengaduan 
                                    SET status_pengaduan = '$status' 
                                    WHERE id_pengaduan = '$id_pengaduan'";
            
            if (mysqli_query($conn, $updatePengaduanQuery)) {
                $_SESSION['success_message'] = "Status pengaduan berhasil diubah menjadi diproses.";
            } else {
                $_SESSION['error_message'] = "Error updating pengaduan: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error updating status: " . mysqli_error($conn);
        }
    } else {
        // Insert new record if no existing record found (fallback)
        $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, status, keterangan) 
                        VALUES ('$id_pengaduan', '$status', '$keterangan')";
        
        if (mysqli_query($conn, $insertQuery)) {
            // Update status in pengaduan table
            $updateQuery = "UPDATE pengaduan 
                           SET status_pengaduan = '$status' 
                           WHERE id_pengaduan = '$id_pengaduan'";
            
            if (mysqli_query($conn, $updateQuery)) {
                $_SESSION['success_message'] = "Status pengaduan berhasil diubah menjadi diproses.";
            } else {
                $_SESSION['error_message'] = "Error updating pengaduan: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
        }
    }

    // Redirect to refresh the page
    header("Location: status_pengaduan.php");
    exit();
}

// Process completion of complaint with evidence upload
if (isset($_POST['complete']) && isset($_POST['id_pengaduan'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $status = "selesai";
    
    // Handle file upload
    $bukti_file = '';
    if (isset($_FILES['bukti_selesai']) && $_FILES['bukti_selesai']['error'] == 0) {
        $upload_dir = 'uploads/bukti/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_tmp = $_FILES['bukti_selesai']['tmp_name'];
        $file_name = basename($_FILES['bukti_selesai']['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Generate unique filename
        $new_file_name = 'bukti_' . $id_pengaduan . '_' . date('YmdHis') . '.' . $file_ext;
        
        // Upload file
        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $bukti_file = $new_file_name;
        } else {
            $_SESSION['error_message'] = "Gagal mengunggah file bukti pengerjaan.";
            header("Location: status_pengaduan.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Bukti pengerjaan harus diunggah.";
        header("Location: status_pengaduan.php");
        exit();
    }
    
    // Check if record exists in status_pengaduan table
    $checkQuery = "SELECT id_status FROM status_pengaduan WHERE id_pengaduan = '$id_pengaduan'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Update existing record
        $row = mysqli_fetch_assoc($checkResult);
        $id_status = $row['id_status'];
        
        $updateStatusQuery = "UPDATE status_pengaduan 
                             SET status = '$status', 
                                 keterangan = '$keterangan',
                                 bukti_selesai = '$bukti_file',
                                 updated_at = NOW() 
                             WHERE id_status = '$id_status'";
        
        if (mysqli_query($conn, $updateStatusQuery)) {
            // Update status in pengaduan table
            $updatePengaduanQuery = "UPDATE pengaduan 
                                    SET status_pengaduan = '$status' 
                                    WHERE id_pengaduan = '$id_pengaduan'";
            
            if (mysqli_query($conn, $updatePengaduanQuery)) {
                $_SESSION['success_message'] = "Pengaduan berhasil diselesaikan.";
            } else {
                $_SESSION['error_message'] = "Error updating pengaduan: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error updating status: " . mysqli_error($conn);
        }
    } else {
        // Insert new record if no existing record found (fallback)
        $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, status, keterangan, bukti_selesai) 
                        VALUES ('$id_pengaduan', '$status', '$keterangan', '$bukti_file')";
        
        if (mysqli_query($conn, $insertQuery)) {
            // Update status in pengaduan table
            $updateQuery = "UPDATE pengaduan 
                           SET status_pengaduan = '$status' 
                           WHERE id_pengaduan = '$id_pengaduan'";
            
            if (mysqli_query($conn, $updateQuery)) {
                $_SESSION['success_message'] = "Pengaduan berhasil diselesaikan.";
            } else {
                $_SESSION['error_message'] = "Error updating pengaduan: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
        }
    }

    // Redirect to refresh the page
    header("Location: status_pengaduan.php");
    exit();

  }

// Ambil data pengaduan yang sudah disetujui dari tabel status_pengaduan
try {
    // Query untuk mendapatkan data pengaduan yang sudah disetujui
    $query = "SELECT p.id_pengaduan, p.nama, p.judul, p.deskripsi, p.alamat, 
                     p.alamat_lokasi, p.kategori, p.foto, p.created_at, 
                     sp.status, sp.created_at as status_date, sp.id_status
              FROM pengaduan p
              INNER JOIN (
                  SELECT id_pengaduan, status, created_at, id_status,
                         ROW_NUMBER() OVER (PARTITION BY id_pengaduan ORDER BY created_at DESC) as rn
                  FROM status_pengaduan
              ) sp ON p.id_pengaduan = sp.id_pengaduan AND sp.rn = 1
              WHERE sp.status = 'disetujui' OR sp.status = 'diproses'
              ORDER BY sp.created_at DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $result = false;
}

// Ambil riwayat status untuk setiap pengaduan
function getStatusHistory($conn, $id_pengaduan) {
    $query = "SELECT status, created_at FROM status_pengaduan 
              WHERE id_pengaduan = $id_pengaduan 
              ORDER BY created_at ASC";
    $result = mysqli_query($conn, $query);
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    return $history;
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
    
    /* Progress Tracker */
    .progress-tracker {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
      position: relative;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .progress-tracker::before {
      content: '';
      position: absolute;
      top: 30px;
      left: 40px;
      right: 40px;
      height: 2px;
      background-color: #ddd;
      z-index: 1;
    }
    
    .progress-step {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      color: #777;
    }
    
    .step-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background-color: white;
      border: 2px solid #ddd;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 10px;
      transition: var(--transition);
    }
    
    .step-label {
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 5px;
      transition: var(--transition);
    }
    
    .step-date {
      font-size: 11px;
      color: #999;
    }
    
    .progress-step.active .step-icon {
      background-color: var(--primary);
      border-color: var(--primary);
      color: white;
    }
    
    .progress-step.active .step-label {
      color: var(--primary);
      font-weight: 600;
    }
    
    .progress-step.completed .step-icon {
      background-color: var(--success);
      border-color: var(--success);
      color: white;
    }
    
    .progress-step.completed .step-label {
      color: var(--success);
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
    
    .btn-process {
      background-color: var(--warning);
      color: white;
    }
    
    .btn-process:hover {
      background-color: #d4ac0d;
    }
    
    .btn-complete {
      background-color: var(--success);
      color: white;
    }
    
    .btn-complete:hover {
      background-color: #27ae60;
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
    
    /* File Upload */
    .form-group {
      margin-bottom: 20px;
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
    
    .file-upload {
      display: block;
      width: 100%;
      padding: 10px;
      background-color: #f8f9fa;
      border: 1px dashed #ddd;
      border-radius: 4px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .file-upload:hover {
      background-color: #f1f1f1;
      border-color: #ccc;
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
    
    /* Progress Indicator */
    .progress-indicator {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .progress-steps {
      display: flex;
      justify-content: center;
      position: relative;
    }
    
    .progress-line {
      position: absolute;
      top: 30px;
      left: 60px;
      right: 60px;
      height: 4px;
      background-color: #e0e0e0;
      z-index: 1;
    }
    
    .progress-line-fill {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      background-color: var(--success);
      transition: width 0.3s ease;
    }
    
    .step {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 150px;
    }
    
    .step-circle {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: white;
      border: 3px solid #e0e0e0;
      margin-bottom: 10px;
      font-size: 24px;
      color: #999;
      transition: all 0.3s ease;
    }
    
    .step-name {
      font-weight: 500;
      font-size: 14px;
      color: #666;
      margin-bottom: 5px;
    }
    
    .step-date {
      font-size: 12px;
      color: #999;
    }
    
    .step.active .step-circle {
      background-color: var(--primary);
      border-color: var(--primary);
      color: white;
    }
    
    .step.active .step-name {
      color: var(--primary);
      font-weight: 600;
    }
    
    .step.completed .step-circle {
      background-color: var(--success);
      border-color: var(--success);
      color: white;
    }
    
    .step.completed .step-name {
      color: var(--success);
    }
    .modallogout{
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
      .modallogout-content {
      background-color: white;
      border-radius: var(--card-radius);
      width: 90%;
      max-width: 400px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .modallogout-icon {
      font-size: 48px;
      color: var(--warning);
      margin-bottom: 15px;
    }
    
    .modallogout-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
    }
    
    .modallogout-message {
      color: #777;
      margin-bottom: 20px;
    }
    
    .modallogout-actions {
      display: flex;
      justify-content: center;
      gap: 15px;
    }
    .btns {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
    }
    .btnlogout-secondary{
      background-color: #e9ecef;
      color: var(--dark);
    }

    .btnlogout-secondary{
      background-color: #dee2e6;
    }
    .btnlogout-primary{
      background-color: #e9ecef;
      color: var(--dark);
    }
    .btnlogout-primary{
     background-color: var(--primary);
      color: white;
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
      
      .progress-tracker {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
      }
      
      .progress-tracker::before {
        display: none;
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
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
      }
    }
    
    @media (max-width: 480px) {
      .navbar-title {
        font-size: 16px;
      }
      
      .search-input {
        width: 150px;
      }
      
      .data-table th, .data-table td {
        padding: 10px 8px;
        font-size: 13px;
      }
    }
    /* Modal Centering CSS - Perbaikan */

/* Modal utama (detailModal, processModal, completeModal) */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  animation: fadeIn 0.3s ease-in-out;
}

.modal-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideIn 0.3s ease-out;
}

/* Modal logout */
.modallogout {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  animation: fadeIn 0.3s ease-in-out;
}

.modallogout-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 400px;
  padding: 30px;
  text-align: center;
  animation: slideIn 0.3s ease-out;
}

/* Modal header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 30px;
  border-bottom: 1px solid #e9ecef;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px 12px 0 0;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  font-weight: bold;
  color: white;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background-color: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}

/* Modal body */
.modal-body {
  padding: 30px;
  max-height: 60vh;
  overflow-y: auto;
}

/* Modal footer */
.modal-footer {
  padding: 20px 30px;
  border-top: 1px solid #e9ecef;
  background-color: #f8f9fa;
  border-radius: 0 0 12px 12px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Logout modal specific styles */
.modallogout-icon {
  font-size: 4rem;
  color: #ffc107;
  margin-bottom: 20px;
}

.modal-message {
  font-size: 1.1rem;
  color: #666;
  margin-bottom: 30px;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.btns {
  padding: 12px 30px;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 100px;
}

.btnlogout-secondary {
  background-color: #6c757d;
  color: white;
}

.btnlogout-secondary:hover {
  background-color: #5a6268;
  transform: translateY(-2px);
}

.btnlogout-primary {
  background-color: #dc3545;
  color: white;
}

.btnlogout-primary:hover {
  background-color: #c82333;
  transform: translateY(-2px);
}

/* Animasi */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translate(-50%, -60%);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    max-height: 95vh;
    margin: 20px auto;
  }
  
  .modallogout-content {
    width: 95%;
    padding: 20px;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 20px;
    padding-right: 20px;
  }
  
  .modal-title {
    font-size: 1.3rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btns {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .modal-content {
    width: 98%;
    max-height: 98vh;
  }
  
  .modallogout-content {
    width: 98%;
    padding: 15px;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 15px;
    padding-right: 15px;
  }
  
  .modallogout-icon {
    font-size: 3rem;
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
        <a href="data_pengaduan.php" class="menu-link">
          <i class="fas fa-clipboard-list menu-icon"></i>
          <span>Data Pengaduan</span>
        </a>
      </div>

      <div class="menu-item">
        <a href="status_pengaduan.php" class="menu-link active">
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
        <a href="data_masyarakat.php" class="menu-link ">
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
      <div class="navbar-title">Status Pengaduan</div>
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
  <div class="main-content" id="mainContent">
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <?php echo $_SESSION['success_message']; ?>
        <button type="button" class="alert-close">&times;</button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-error">
        <?php echo $_SESSION['error_message']; ?>
        <button type="button" class="alert-close">&times;</button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="data-table-container">
      <div class="table-header">
        <div class="table-title">Daftar Pengaduan yang Disetujui</div>
        <div class="table-filter">
          <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Cari pengaduan...">
          </div>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="data-table" id="pengaduanTable">
          <thead>
            <tr>
              <th>No</th>
              <th>ID Pengaduan</th>
              <th>Nama Pelapor</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Tanggal Update</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Ambil data pengaduan yang sudah disetujui dari tabel status_pengaduan
            try {
                // Mengubah query untuk hanya mengambil dari tabel status_pengaduan yang paling baru
                $query = "SELECT sp.id_status, sp.id_pengaduan, p.nama, p.kategori, p.judul, p.deskripsi, p.alamat, 
                         p.alamat_lokasi, p.foto, p.created_at as tanggal_lapor, 
                         sp.status, sp.created_at as status_date, sp.keterangan, sp.bukti_selesai
                  FROM status_pengaduan sp
                  INNER JOIN pengaduan p ON sp.id_pengaduan = p.id_pengaduan
                  INNER JOIN (
                      SELECT id_pengaduan, MAX(created_at) as max_date
                      FROM status_pengaduan
                      GROUP BY id_pengaduan
                  ) latest ON sp.id_pengaduan = latest.id_pengaduan AND sp.created_at = latest.max_date
                  WHERE sp.status IN ('disetujui', 'diproses')
                  ORDER BY sp.created_at DESC";
                
                $result = mysqli_query($conn, $query);
                
                if (!$result) {
                    throw new Exception(mysqli_error($conn));
                }
                
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    $id_pengaduan = $row['id_pengaduan'];
                    $nama = $row['nama'];
                    $kategori = $row['kategori'];
                    $status = $row['status'];
                    $status_date = date('d-m-Y H:i', strtotime($row['status_date']));
                    $id_status = $row['id_status'];
                    
                    // Tentukan kelas CSS untuk status
                    $status_class = '';
                    switch ($status) {
                        case 'disetujui':
                            $status_class = 'status-pending';
                            break;
                        case 'diproses':
                            $status_class = 'status-process';
                            break;
                        case 'selesai':
                            $status_class = 'status-completed';
                            break;
                        case 'ditolak':
                            $status_class = 'status-rejected';
                            break;
                    }
                    
                    echo "<tr>";
                    echo "<td>$no</td>";
                    echo "<td>$id_pengaduan</td>";
                    echo "<td>$nama</td>";
                    echo "<td>$kategori</td>";
                    echo "<td><span class='status-badge $status_class'>" . ucfirst($status) . "</span></td>";
                    echo "<td>$status_date</td>";
                    echo "<td class='table-actions'>";
                    
                    // Tombol Lihat Detail
                    echo "<button type='button' class='btn-table btn-view' onclick='openDetailModal(\"$id_pengaduan\", \"$id_status\")'>Detail</button>";
                    
                    // Tombol Proses untuk pengaduan yang berstatus disetujui
                    if ($status === 'disetujui') {
                        echo "<button type='button' class='btn-table btn-process' onclick='openProcessModal(\"$id_pengaduan\")'>Proses</button>";
                    }
                    
                    // Tombol Selesai untuk pengaduan yang berstatus diproses
                    if ($status === 'diproses') {
                        echo "<button type='button' class='btn-table btn-complete' onclick='openCompleteModal(\"$id_pengaduan\")'>Selesai</button>";
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                    
                    $no++;
                }
                
                if ($no === 1) { // Tidak ada data
                    echo "<tr><td colspan='7' class='text-center'>Tidak ada pengaduan yang disetujui</td></tr>";
                }
                
            } catch (Exception $e) {
                echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- Footer -->
  <div class="footer" id="footer">
    <div>&copy; <?php echo date('Y'); ?> SILADU - Sistem Layanan Pengaduan Terpadu. All rights reserved.</div>
  </div>
  
  <!-- Modal Detail Pengaduan -->
  <div class="modal" id="detailModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Detail Pengaduan</div>
        <button type="button" class="modal-close" onclick="closeModal('detailModal')">&times;</button>
      </div>
      <div class="modal-body" id="detailModalBody">
        <!-- Konten akan diisi melalui AJAX -->
        <div class="progress-indicator">
          <div class="progress-steps">
            <div class="progress-line">
              <div class="progress-line-fill" id="progressLineFill"></div>
            </div>
            <div class="step" id="stepSubmitted">
              <div class="step-circle"><i class="fas fa-file-alt"></i></div>
              <div class="step-name">Diajukan</div>
              <div class="step-date" id="dateSubmitted">-</div>
            </div>
            <div class="step" id="stepApproved">
              <div class="step-circle"><i class="fas fa-check"></i></div>
              <div class="step-name">Disetujui</div>
              <div class="step-date" id="dateApproved">-</div>
            </div>
            <div class="step" id="stepProcessing">
              <div class="step-circle"><i class="fas fa-cogs"></i></div>
              <div class="step-name">Diproses</div>
              <div class="step-date" id="dateProcessing">-</div>
            </div>
            <div class="step" id="stepCompleted">
              <div class="step-circle"><i class="fas fa-check-double"></i></div>
              <div class="step-name">Selesai</div>
              <div class="step-date" id="dateCompleted">-</div>
            </div>
          </div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">ID Pengaduan</div>
          <div class="detail-value" id="detailId">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Nama Pelapor</div>
          <div class="detail-value" id="detailName">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Judul Pengaduan</div>
          <div class="detail-value" id="detailTitle">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Kategori</div>
          <div class="detail-value" id="detailCategory">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Deskripsi</div>
          <div class="detail-value" id="detailDescription">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Alamat Pelapor</div>
          <div class="detail-value" id="detailAddress">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Lokasi Pengaduan</div>
          <div class="detail-value detail-address" id="detailLocation">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Foto</div>
          <div class="detail-value">
            <img src="" alt="Foto Pengaduan" class="detail-image" id="detailImage">
          </div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Tanggal Pengaduan</div>
          <div class="detail-value" id="detailDate">-</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Status Saat Ini</div>
          <div class="detail-value" id="detailStatus">-</div>
        </div>
        <div class="detail-row" id="detailEvidence" style="display: none;">
          <div class="detail-label">Bukti Pengerjaan</div>
          <div class="detail-value">
            <img src="" alt="Bukti Pengerjaan" class="detail-image" id="detailEvidenceImage">
          </div>
        </div>
        <div class="detail-row" id="detailNotes" style="display: none;">
          <div class="detail-label">Keterangan Penyelesaian</div>
          <div class="detail-value" id="detailNotesText">-</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-table btn-view" onclick="closeModal('detailModal')">Tutup</button>
      </div>
    </div>
  </div>

  <!-- Modal Proses Pengaduan -->
  <div class="modal" id="processModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Proses Pengaduan</div>
        <button type="button" class="modal-close" onclick="closeModal('processModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="processForm" method="post" enctype="multipart/form-data">
          <input type="hidden" id="processId" name="id_pengaduan">
          <div class="form-group">
            <label for="keterangan_proses">Keterangan Proses:</label>
            <textarea class="form-control" id="keterangan_proses" name="keterangan" rows="4" required placeholder="Masukkan keterangan proses pengaduan..."></textarea>
          </div>
          <div class="form-group">
            <label for="bukti_proses">Bukti Proses:</label>
            <input type="file" id="bukti_proses" name="bukti_proses" class="form-control" required accept="image/*">
            <small class="form-text">Upload foto bukti proses pengaduan (format: jpg, jpeg, png, gif)</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-table" onclick="closeModal('processModal')">Batal</button>
            <button type="submit" name="process_with_evidence" class="btn-table btn-process">Proses</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Modal Selesaikan Pengaduan -->
  <div class="modal" id="completeModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Selesaikan Pengaduan</div>
        <button type="button" class="modal-close" onclick="closeModal('completeModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="completeForm" method="post" enctype="multipart/form-data">
          <input type="hidden" id="completeId" name="id_pengaduan">
          <div class="form-group">
            <label for="keterangan">Keterangan Penyelesaian:</label>
            <textarea class="form-control" id="keterangan" name="keterangan" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="bukti_selesai">Bukti Pengerjaan:</label>
            <input type="file" id="bukti_selesai" name="bukti_selesai" class="form-control" required accept="image/*">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-table" onclick="closeModal('completeModal')">Batal</button>
            <button type="submit" name="complete" class="btn-table btn-complete">Selesaikan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modallogout" id="logoutModal">
    <div class="modallogout-content">
      <div class="modallogout-icon">
        <i class="fas fa-question-circle"></i>
      </div>
      <div class="modal-title">Konfirmasi Keluar</div>
      <div class="modal-message">Anda yakin ingin keluar dari sistem?</div>
      <div class="modal-actions">
        <button class="btns btnlogout-secondary" onclick="closeLogoutModal()">Batal</button>
        <button class="btns btnlogout-primary" onclick="logout()">Ya, Keluar</button>
      </div>
    </div>
  </div>
  
  <script>
    // Toggle sidebar
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      const sidebar = document.getElementById('sidebar');
      const mainContent = document.getElementById('mainContent');
      const navbar = document.getElementById('navbar');
      const footer = document.getElementById('footer');
      
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
      navbar.classList.toggle('expanded');
      footer.classList.toggle('expanded');
    });

    // Toggle dropdown
    function toggleDropdown() {
      const dropdown = document.getElementById('userDropdown');
      dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
      if (!event.target.matches('.user-dropdown-img')) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown.classList.contains('show')) {
          dropdown.classList.remove('show');
        }
      }
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
      const input = this.value.toLowerCase();
      const table = document.getElementById('pengaduanTable');
      const rows = table.getElementsByTagName('tr');
      
      for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
          if (cells[j].textContent.toLowerCase().indexOf(input) > -1) {
            found = true;
            break;
          }
        }
        
        rows[i].style.display = found ? '' : 'none';
      }
    });

    // Close alert messages
    document.querySelectorAll('.alert-close').forEach(button => {
      button.addEventListener('click', function() {
        this.parentElement.style.display = 'none';
      });
    });

    // Modal functions
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    // Open process modal
    function openProcessModal(idPengaduan) {
      document.getElementById('processId').value = idPengaduan;
      openModal('processModal');
    }

    // Open complete modal
    function openCompleteModal(idPengaduan) {
      document.getElementById('completeId').value = idPengaduan;
      openModal('completeModal');
    }

    // Open detail modal
    // Open detail modal - PERBAIKAN
function openDetailModal(idPengaduan, idStatus) {
    console.log('Opening detail modal for:', idPengaduan, idStatus); // Debug log
    
    // AJAX request to get detail data
    fetch('get_pengaduan_detail.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_pengaduan=' + encodeURIComponent(idPengaduan) + '&id_status=' + encodeURIComponent(idStatus)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data); // Debug log
        
        if (data.success) {
            // Fill modal with data
            document.getElementById('detailId').textContent = data.id_pengaduan || '-';
            document.getElementById('detailName').textContent = data.nama || '-';
            document.getElementById('detailTitle').textContent = data.judul || '-';
            document.getElementById('detailCategory').textContent = data.kategori || '-';
            document.getElementById('detailDescription').textContent = data.deskripsi || '-';
            document.getElementById('detailAddress').textContent = data.alamat || '-';
            document.getElementById('detailLocation').textContent = data.alamat_lokasi || '-';
            document.getElementById('detailDate').textContent = data.tanggal_lapor || '-';
            
            // Set status with proper styling
            const statusMap = {
                'diajukan': 'status-pending',
                'disetujui': 'status-approved', 
                'diproses': 'status-process',
                'selesai': 'status-completed',
                'ditolak': 'status-rejected'
            };
            
            const statusClass = statusMap[data.status] || 'status-pending';
            const statusText = data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'Diajukan';
            document.getElementById('detailStatus').innerHTML = '<span class="status-badge ' + statusClass + '">' + statusText + '</span>';
            
            // Set image
            const detailImage = document.getElementById('detailImage');
            if (data.foto) {
                detailImage.src = 'uploads/' + data.foto;
                detailImage.style.display = 'block';
                detailImage.onerror = function() {
                    this.style.display = 'none';
                    console.log('Image not found:', this.src);
                };
            } else {
                detailImage.style.display = 'none';
            }
            
            // Show evidence if completed
            const detailEvidence = document.getElementById('detailEvidence');
            const detailEvidenceImage = document.getElementById('detailEvidenceImage');
            if (data.bukti_selesai) {
                detailEvidence.style.display = 'block';
                detailEvidenceImage.src = 'uploads/bukti/' + data.bukti_selesai;
                detailEvidenceImage.onerror = function() {
                    detailEvidence.style.display = 'none';
                };
            } else {
                detailEvidence.style.display = 'none';
            }
            
            // Show notes if available
            const detailNotes = document.getElementById('detailNotes');
            const detailNotesText = document.getElementById('detailNotesText');
            if (data.keterangan && data.keterangan.trim() !== '') {
                detailNotes.style.display = 'block';
                detailNotesText.textContent = data.keterangan;
            } else {
                detailNotes.style.display = 'none';
            }
            
            // Update progress indicator
            if (data.status_history && Array.isArray(data.status_history)) {
                updateProgressIndicator(data.status_history);
            } else {
                // Fallback jika tidak ada riwayat status
                updateProgressIndicator([{status: data.status || 'diajukan', created_at: data.tanggal_lapor}]);
            }
            
            // Show modal
            openModal('detailModal');
        } else {
            alert('Error: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Terjadi kesalahan saat mengambil data: ' + error.message);
    });
}

// Update progress indicator - PERBAIKAN
function updateProgressIndicator(statusHistory) {
    // Reset all steps
    const steps = ['stepSubmitted', 'stepApproved', 'stepProcessing', 'stepCompleted'];
    const dates = ['dateSubmitted', 'dateApproved', 'dateProcessing', 'dateCompleted'];
    
    // Reset classes and dates
    steps.forEach((step, index) => {
        const stepElement = document.getElementById(step);
        const dateElement = document.getElementById(dates[index]);
        if (stepElement) stepElement.classList.remove('active', 'completed');
        if (dateElement) dateElement.textContent = '-';
    });
    
    // Set progress based on status history
    let progressPercentage = 0;
    
    if (statusHistory && Array.isArray(statusHistory)) {
        statusHistory.forEach(status => {
            switch (status.status) {
                case 'diajukan':
                    const stepSubmitted = document.getElementById('stepSubmitted');
                    const dateSubmitted = document.getElementById('dateSubmitted');
                    if (stepSubmitted) stepSubmitted.classList.add('completed');
                    if (dateSubmitted) dateSubmitted.textContent = status.created_at || '-';
                    progressPercentage = Math.max(progressPercentage, 25);
                    break;
                case 'disetujui':
                    const stepApproved = document.getElementById('stepApproved');
                    const dateApproved = document.getElementById('dateApproved');
                    if (stepApproved) stepApproved.classList.add('completed');
                    if (dateApproved) dateApproved.textContent = status.created_at || '-';
                    progressPercentage = Math.max(progressPercentage, 50);
                    break;
                case 'diproses':
                    const stepProcessing = document.getElementById('stepProcessing');
                    const dateProcessing = document.getElementById('dateProcessing');
                    if (stepProcessing) stepProcessing.classList.add('completed');
                    if (dateProcessing) dateProcessing.textContent = status.created_at || '-';
                    progressPercentage = Math.max(progressPercentage, 75);
                    break;
                case 'selesai':
                    const stepCompleted = document.getElementById('stepCompleted');
                    const dateCompleted = document.getElementById('dateCompleted');
                    if (stepCompleted) stepCompleted.classList.add('completed');
                    if (dateCompleted) dateCompleted.textContent = status.created_at || '-';
                    progressPercentage = 100;
                    break;
            }
        });
    }
    
    // Update progress line
    const progressLineFill = document.getElementById('progressLineFill');
    if (progressLineFill) {
        progressLineFill.style.width = progressPercentage + '%';
    }
}

    // Update progress indicator
    function updateProgressIndicator(statusHistory) {
      // Reset all steps
      const steps = ['stepSubmitted', 'stepApproved', 'stepProcessing', 'stepCompleted'];
      steps.forEach(step => {
        document.getElementById(step).classList.remove('active', 'completed');
      });
      
      // Set progress based on status history
      let progressPercentage = 0;
      
      statusHistory.forEach(status => {
        switch (status.status) {
          case 'diajukan':
            document.getElementById('stepSubmitted').classList.add('completed');
            document.getElementById('dateSubmitted').textContent = status.created_at;
            progressPercentage = Math.max(progressPercentage, 25);
            break;
          case 'disetujui':
            document.getElementById('stepApproved').classList.add('completed');
            document.getElementById('dateApproved').textContent = status.created_at;
            progressPercentage = Math.max(progressPercentage, 50);
            break;
          case 'diproses':
            document.getElementById('stepProcessing').classList.add('completed');
            document.getElementById('dateProcessing').textContent = status.created_at;
            progressPercentage = Math.max(progressPercentage, 75);
            break;
          case 'selesai':
            document.getElementById('stepCompleted').classList.add('completed');
            document.getElementById('dateCompleted').textContent = status.created_at;
            progressPercentage = 100;
            break;
        }
      });
      
      // Update progress line
      document.getElementById('progressLineFill').style.width = progressPercentage + '%';
    }

    // Logout functions
    function showLogoutModal() {
      document.getElementById('logoutModal').style.display = 'block';
    }

    function closeLogoutModal() {
      document.getElementById('logoutModal').style.display = 'none';
    }

    function logout() {
      window.location.href = 'logout.php';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modals = ['detailModal', 'processModal', 'completeModal', 'logoutModal'];
      modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>