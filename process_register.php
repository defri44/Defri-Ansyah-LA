<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama = $_POST['name'];
    $nik = $_POST['nik'];
    $email = $_POST['email'];
    $telepon = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($nama) || empty($nik) || empty($email) || empty($telepon) || empty($password) || empty($confirm_password)) {
        echo "<script>alert('Semua kolom harus diisi!'); window.location.href='register.php';</script>";
        exit();
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Kata sandi dan konfirmasi kata sandi tidak cocok!'); window.location.href='register.php';</script>";
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Koneksi ke database
    $servername = "localhost";
    $username = "root"; // ganti dengan username database Anda
    $password_db = ""; // ganti dengan password database Anda
    $dbname = "pengaduan_db"; // ganti dengan nama database Anda

    // Membuat koneksi
    $conn = new mysqli($servername, $username, $password_db, $dbname);

    // Cek koneksi
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Query untuk mengecek apakah email sudah terdaftar
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email sudah terdaftar!'); window.location.href='register.php';</script>";
    } else {
        // Query untuk menyimpan data pengguna baru dan otomatis memberi role 'masyarakat'
        $role = 'masyarakat'; // Pastikan user yang mendaftar otomatis memiliki role masyarakat
        $sql = "INSERT INTO users (nama, nik, email, telepon, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $nama, $nik, $email, $telepon, $hashed_password, $role);
        
        if ($stmt->execute()) {
            // Redirect ke halaman login setelah registrasi berhasil
            header("Location: beranda.php");
            exit();  // Pastikan eksekusi dihentikan setelah redirect
        } else {
            echo "<script>alert('Terjadi kesalahan. Coba lagi nanti!'); window.location.href='register.php';</script>";
        }
    }

    // Menutup koneksi
    $stmt->close();
    $conn->close();
}
?>
