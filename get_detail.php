<?php
// Menghubungkan ke database
$conn = mysqli_connect("localhost", "root", "", "pengaduan_db");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Mendapatkan ID dari query string
$id = $_GET['id']; 

// Query untuk mengambil detail pengaduan berdasarkan id_pengaduan
$query = "SELECT * FROM pengaduan WHERE id_pengaduan = '$id'";
$result = mysqli_query($conn, $query);

// Mengecek apakah ada data
if (mysqli_num_rows($result) > 0) {
    // Mengambil data baris pertama
    $row = mysqli_fetch_assoc($result);

    // Menampilkan data dalam format JSON
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Data pengaduan tidak ditemukan']);
}

mysqli_close($conn);
?>
