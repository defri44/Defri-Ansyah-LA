<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "pengaduan_db";

$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['id_pengaduan'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Pengaduan required']);
    exit();
}

$id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);

try {
    // Get pengaduan details
    $query = "SELECT p.*, 
              DATE_FORMAT(p.created_at, '%d-%m-%Y %H:%i') as tanggal_lapor_formatted
              FROM pengaduan p 
              WHERE p.id_pengaduan = '$id_pengaduan'";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception('Pengaduan tidak ditemukan');
    }
    
    $pengaduan = mysqli_fetch_assoc($result);
    
    // Get status history
    $status_query = "SELECT status, 
                     DATE_FORMAT(created_at, '%d-%m-%Y %H:%i') as created_at,
                     keterangan, bukti_selesai, bukti_proses
                     FROM status_pengaduan 
                     WHERE id_pengaduan = '$id_pengaduan' 
                     ORDER BY created_at ASC";
    
    $status_result = mysqli_query($conn, $status_query);
    
    $status_history = [];
    $current_status = $pengaduan['status_pengaduan'] ?: 'diajukan';
    $keterangan = '';
    $bukti_selesai = '';
    $bukti_proses = '';
    
    if ($status_result && mysqli_num_rows($status_result) > 0) {
        while ($status_row = mysqli_fetch_assoc($status_result)) {
            $status_history[] = $status_row;
            // Get the latest status info
            $current_status = $status_row['status'];
            if ($status_row['keterangan']) {
                $keterangan = $status_row['keterangan'];
            }
            if ($status_row['bukti_selesai']) {
                $bukti_selesai = $status_row['bukti_selesai'];
            }
            if ($status_row['bukti_proses']) {
                $bukti_proses = $status_row['bukti_proses'];
            }
        }
    } else {
        // If no status history, create default with submission date
        $status_history[] = [
            'status' => 'diajukan',
            'created_at' => $pengaduan['tanggal_lapor_formatted'],
            'keterangan' => '',
            'bukti_selesai' => '',
            'bukti_proses' => ''
        ];
    }
    
    // Prepare response data
    $response_data = [
        'success' => true,
        'id_pengaduan' => $pengaduan['id_pengaduan'],
        'nama' => $pengaduan['nama'],
        'nik' => $pengaduan['nik'],
        'alamat' => $pengaduan['alamat'],
        'telepon' => $pengaduan['telepon'],
        'email' => $pengaduan['email'],
        'judul' => $pengaduan['judul'],
        'kategori' => $pengaduan['kategori'],
        'deskripsi' => $pengaduan['deskripsi'],
        'alamat_lokasi' => $pengaduan['alamat_lokasi'],
        'foto' => $pengaduan['foto'],
        'tanggal_lapor' => $pengaduan['tanggal_lapor_formatted'],
        'status' => $current_status,
        'keterangan' => $keterangan,
        'bukti_selesai' => $bukti_selesai,
        'bukti_proses' => $bukti_proses,
        'status_history' => $status_history
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response_data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>