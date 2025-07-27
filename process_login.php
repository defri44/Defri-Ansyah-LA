<?php
session_start();

// Cek apakah form login sudah disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($email) || empty($password)) {
        echo "<script>alert('Email dan kata sandi wajib diisi!'); window.location.href='login.php';</script>";
        exit();
    }

    // Koneksi ke database
    $servername = "localhost";
    $username = "root"; // ganti dengan username database Anda
    $db_password = ""; // ganti dengan password database Anda
    $dbname = "pengaduan_db"; // ganti dengan nama database Anda

    // Membuat koneksi
    $conn = new mysqli($servername, $username, $db_password, $dbname);

    // Cek koneksi
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Query untuk mencari user berdasarkan email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Jika ada user, cek password
        $user = $result->fetch_assoc();

        // Verifikasi password (jika menggunakan hash seperti bcrypt)
        if (password_verify($password, $user['password'])) {
            // Jika password benar, simpan data user ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_nik'] = $user['nik'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role']; // Menyimpan role pengguna

            // Redirect berdasarkan role pengguna
if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'kepala_dinas') {
    header("Location: dashboard_admin.php"); // Halaman dashboard admin & kepala dinas
} else if ($_SESSION['user_role'] == 'masyarakat') {
    header("Location: dashboard_masyarakat.php"); // Halaman dashboard masyarakat
} else {
    echo "<script>alert('Role pengguna tidak dikenali!'); window.location.href='beranda.php';</script>";
}
exit;

        } else {
            echo "<script>alert('Kata sandi salah!'); window.location.href='beranda.php';</script>";
        }
    } else {
        echo "<script>alert('Email tidak terdaftar!'); window.location.href='beranda.php';</script>";
    }

    // Menutup koneksi
    $stmt->close();
    $conn->close();
}
?>
