<?php
session_start();
// Pastikan pengguna sudah login sebelum menampilkan halaman
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role'])) {
    header('Location: login.php'); // Redirect ke halaman login jika belum login
    exit();
}

// Pastikan hanya admin yang bisa mengakses halaman ini
if ($_SESSION['user_role'] != 'admin') {
    header('Location: dashboard.php'); // Redirect ke dashboard jika bukan admin
    exit();
}

// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "", "pengaduan_db");

// Periksa koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Validasi data yang diterima
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_pengaduan']) && isset($_POST['status']) && isset($_POST['nik']) && isset($_POST['nama_pengadu'])) {
        $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $nik = mysqli_real_escape_string($conn, $_POST['nik']);
        $nama_pengadu = mysqli_real_escape_string($conn, $_POST['nama_pengadu']);
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($conn, $_POST['keterangan']) : null;
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Update status pada tabel pengaduan
            $updateQuery = "UPDATE pengaduan SET status_pengaduan = ? WHERE id_pengaduan = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "si", $status, $id_pengaduan);
            $updateResult = mysqli_stmt_execute($stmt);
            
            // 2. Insert ke tabel status_pengaduan
            $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, nik, nama_pengadu, status, keterangan) 
                            VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($stmt, "issss", $id_pengaduan, $nik, $nama_pengadu, $status, $keterangan);
            $insertResult = mysqli_stmt_execute($stmt);
            
            // Jika kedua operasi berhasil, commit transaksi
            if ($updateResult && $insertResult) {
                mysqli_commit($conn);
                $_SESSION['success_message'] = "Pengaduan berhasil " . ($status == 'diterima' ? 'diterima' : 'ditolak');
            } else {
                throw new Exception("Gagal memproses pengaduan");
            }
        } catch (Exception $e) {
            // Jika terjadi kesalahan, rollback transaksi
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        
        // Redirect kembali ke halaman data pengaduan
        header('Location: data_pengaduan.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Data yang dikirimkan tidak lengkap";
        header('Location: data_pengaduan.php');
        exit();
    }
} else {
    // Jika bukan method POST, redirect ke halaman data pengaduan
    header('Location: data_pengaduan.php');
    exit();
}
?>