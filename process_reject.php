<?php
session_start();

// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "", "pengaduan_db");

// Periksa koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Check if the form is submitted
if (isset($_POST['id_pengaduan']) && isset($_POST['nama_pengadu']) && isset($_POST['keterangan'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
    $nama_pengadu = mysqli_real_escape_string($conn, $_POST['nama_pengadu']);
    $status = "ditolak";  // You can also update this for "disetujui" or "diproses" if needed
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Insert into status_pengaduan table
    $insertQuery = "INSERT INTO status_pengaduan (id_pengaduan, nama_pengadu, status, keterangan) 
                    VALUES ('$id_pengaduan', '$nama_pengadu', '$status', '$keterangan')";

    // Update status in pengaduan table
    $updateQuery = "UPDATE pengaduan SET status_pengaduan = '$status' WHERE id_pengaduan = '$id_pengaduan'";

    if (mysqli_query($conn, $insertQuery) && mysqli_query($conn, $updateQuery)) {
        $_SESSION['success_message'] = "Pengaduan berhasil ditolak.";
    } else {
        $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
    }

    // Redirect to refresh the page
    header("Location: data_pengaduan.php");
    exit();
}

?>
