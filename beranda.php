<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Sistem Pengaduan Online Dinas Sosial Kota Palembang</title>
    <link rel="icon" type="image/png" href="gambar/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            height: 50px;
        }
        
        .logo-text h1 {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
        }
        
        .logo-text p {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav a:hover {
            color: var(--secondary-color);
        }
        
        .auth-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-outline {
            background-color: transparent;
            color: white;
            border: 1px solid white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.8)), url('/api/placeholder/1200/500') center/cover;
            color: white;
            padding: 5rem 0;
            text-align: center;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .btn-large {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }
        
        /* Info Section */
        .info-section {
            padding: 4rem 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .section-title p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            color: #7f8c8d;
        }
        
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .info-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
        }
        
        .info-card i {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
        }
        
        .info-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .info-card p {
            color: #7f8c8d;
        }
        
        /* Steps Section */
        .steps-section {
            padding: 4rem 0;
            background-color: var(--light-color);
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            counter-reset: step-counter;
        }
        
        .step {
            position: relative;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            counter-increment: step-counter;
        }
        
        .step::before {
            content: counter(step-counter);
            position: absolute;
            top: -20px;
            left: 20px;
            width: 40px;
            height: 40px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .step p {
            color: #7f8c8d;
        }
        
        /* Stats Section */
        .stats-section {
            padding: 4rem 0;
            background-color: var(--primary-color);
            color: white;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        
        .stat i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        .stat .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat p {
            font-size: 1.1rem;
        }
        
        /* FAQ Section */
        .faq-section {
            padding: 4rem 0;
        }
        
        .faq {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .faq-item {
            background-color: white;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .faq-question {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .faq-question:hover {
            background-color: #f8f9fa;
        }
        
        .faq-question i {
            transition: transform 0.3s;
        }
        
        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s, padding 0.3s;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        
        .faq-item.active .faq-answer {
            max-height: 500px;
            padding: 0 1.5rem 1.5rem;
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 3rem 0;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--secondary-color);
        }
        
        .footer-column p {
            margin-bottom: 1rem;
        }
        
        .contact-info {
            list-style: none;
        }
        
        .contact-info li {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .contact-info i {
            color: var(--secondary-color);
            margin-top: 5px;
        }
        
        .quick-links {
            list-style: none;
        }
        
        .quick-links li {
            margin-bottom: 0.8rem;
        }
        
        .quick-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .quick-links a:hover {
            color: var(--secondary-color);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            color: #bdc3c7;
        }
        
        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .modal-backdrop.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal-backdrop.active .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 0.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .form-switch {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .form-switch input {
            margin-right: 0.5rem;
        }
        
        .modal-footer .btn {
            padding: 0.75rem 1.5rem;
        }
        
        .register-link, .login-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .register-link a, .login-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover, .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .logo {
                justify-content: center;
            }
            
            nav ul {
                justify-content: center;
                margin: 1rem 0;
            }
            
            .auth-buttons {
                justify-content: center;
            }
            
            .hero h2 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .footer-content {
                text-align: center;
            }
            
            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .contact-info li {
                justify-content: center;
            }
            
            .social-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <img src="gambar/logo.png" alt="Logo Dinas Sosial Palembang">
                <div class="logo-text">
                    <h1>LAYANAN PENGADUAN ONLINE</h1>
                    <p>Dinas Sosial Kota Palembang</p>
                </div>
            </div>
            
            <div class="auth-buttons">
                <button class="btn btn-outline" id="loginBtn">Masuk</button>
                <button class="btn btn-primary" id="registerBtn">Daftar</button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>Layanan Pengaduan Online Dinas Sosial Kota Palembang</h2>
            <p>Platform resmi untuk melaporkan permasalahan sosial dan mendapatkan bantuan dari Dinas Sosial Kota Palembang secara cepat, transparan, dan efektif.</p>
            <div class="hero-buttons">
                <button class="btn btn-primary btn-large" id="buatPengaduanBtn">Buat Pengaduan</button>
                <button class="btn btn-outline btn-large"id="loginBtn" >Cek Status Pengaduan</button>
            </div>
        </div>
    </section>

    <!-- Info Section -->
    <section class="info-section">
        <div class="container">
            <div class="section-title">
                <h2>Layanan Pengaduan Online</h2>
                <p>Sistem Pengaduan Online bertujuan untuk meningkatkan pelayanan publik dan menyediakan akses yang mudah bagi masyarakat Kota Palembang.</p>
            </div>
            <div class="info-cards">
                <div class="info-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Cepat &amp; Mudah</h3>
                    <p>Proses pengaduan yang sederhana dan cepat, dapat dilakukan kapan saja dan di mana saja tanpa perlu datang ke kantor Dinas Sosial.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Aman &amp; Terpercaya</h3>
                    <p>Data pengaduan Anda akan diproses secara aman dan terjamin kerahasiaannya oleh petugas yang berwenang.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Transparan</h3>
                    <p>Pantau status pengaduan Anda secara real-time dan dapatkan notifikasi perkembangan proses penanganan pengaduan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="steps-section">
        <div class="container">
            <div class="section-title">
                <h2>Cara Membuat Pengaduan</h2>
                <p>Ikuti langkah-langkah sederhana berikut untuk melaporkan masalah sosial kepada Dinas Sosial Kota Palembang.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <h3>Buat Akun</h3>
                    <p>Daftar akun baru untuk mengakses sistem pengaduan online dengan mengisi data diri Anda.</p>
                </div>
                <div class="step">
                    <h3>Isi Formulir Pengaduan</h3>
                    <p>Lengkapi formulir pengaduan dengan detail permasalahan dan lokasi yang akurat.</p>
                </div>
                <div class="step">
                    <h3>Unggah Bukti Pendukung</h3>
                    <p>Lampirkan foto, video, atau dokumen pendukung untuk memperkuat laporan Anda.</p>
                </div>
                <div class="step">
                    <h3>Pantau Status Pengaduan</h3>
                    <p>Cek status pengaduan Anda secara berkala dan terima notifikasi perkembangan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="section-title">
                <h2>Statistik Pengaduan</h2>
                <p>Komitmen kami dalam menyelesaikan setiap pengaduan masyarakat Kota Palembang.</p>
            </div>
            <div class="stats">
                <div class="stat">
                    <i class="fas fa-file-alt"></i>
                    <div class="number">2,543</div>
                    <p>Total Pengaduan</p>
                </div>
                <div class="stat">
                    <i class="fas fa-check-circle"></i>
                    <div class="number">2,187</div>
                    <p>Pengaduan Selesai</p>
                </div>
                <div class="stat">
                    <i class="fas fa-spinner"></i>
                    <div class="number">356</div>
                    <p>Dalam Proses</p>
                </div>
                <div class="stat">
                    <i class="fas fa-clock"></i>
                    <div class="number">3</div>
                    <p>Hari Rata-rata Penyelesaian</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="section-title">
                <h2>Pertanyaan Umum</h2>
                <p>Temukan jawaban untuk pertanyaan yang sering diajukan seputar sistem pengaduan online.</p>
            </div>
            <div class="faq">
                <div class="faq-item">
                    <div class="faq-question">
                        Siapa yang bisa menggunakan sistem pengaduan online ini?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Semua warga Kota Palembang dapat menggunakan sistem pengaduan online ini. Anda hanya perlu mendaftar dengan KTP atau identitas resmi lainnya untuk membuat akun.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Masalah apa saja yang bisa dilaporkan melalui sistem ini?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Anda dapat melaporkan berbagai masalah sosial seperti kemiskinan, tunawisma, penyandang disabilitas yang membutuhkan bantuan, lansia terlantar, anak terlantar, korban bencana, dan masalah sosial lainnya yang menjadi tanggung jawab Dinas Sosial Kota Palembang.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Berapa lama pengaduan saya akan diproses?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Waktu pemrosesan pengaduan bervariasi tergantung pada jenis dan kompleksitas masalah. Namun, kami berkomitmen untuk merespons setiap pengaduan dalam waktu 1x24 jam kerja dan menyelesaikannya dalam waktu rata-rata 3 hari kerja.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Apakah identitas pelapor akan dirahasiakan?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Ya, identitas pelapor akan dijaga kerahasiaannya dan hanya diketahui oleh petugas yang berwenang. Anda juga dapat memilih opsi untuk membuat pengaduan anonim jika diperlukan.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Bagaimana cara mengetahui status pengaduan saya?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Setelah membuat pengaduan, Anda akan mendapatkan nomor tiket yang dapat digunakan untuk melacak status pengaduan melalui sistem. Anda juga akan menerima notifikasi email atau SMS ketika ada pembaruan status pengaduan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Tentang Kami</h3>
                    <p>Sistem Pengaduan Online Dinas Sosial Kota Palembang (SILADU) adalah platform digital yang memudahkan masyarakat untuk melaporkan permasalahan sosial secara cepat dan efisien.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Kontak Kami</h3>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <div>Jl. Merdeka No. 123, Palembang 30111, Sumatera Selatan, Indonesia</div>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <div>(0711) 123456</div>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <div>dinsos@palembang.go.id</div>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <div>Senin - Jumat: 08.00 - 16.00 WIB</div>
                        </li>
                    </ul>
                </div>
                
                </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Dinas Sosial Kota Palembang. Hak Cipta Dilindungi Undang-Undang.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal-backdrop" id="loginModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Masuk ke Akun</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="loginForm" action="process_login.php" method="POST">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Kata Sandi</label>
                        <input type="password" id="login-password" name="password" class="form-control" required>
                        <a href="#" class="forgot-password">Lupa kata sandi?</a>
                    </div>
                    <div class="form-switch">
                        <input type="checkbox" id="remember-me" name="remember">
                        <label for="remember-me">Ingat saya</label>
                    </div>
                </form>
                <div class="register-link">
                    Belum punya akun? <a href="#" id="switchToRegister">Daftar sekarang</a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline modal-close">Batal</button>
                <button type="submit" form="loginForm" class="btn btn-primary">Masuk</button>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal-backdrop" id="registerModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Buat Akun Baru</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="registerForm" action="process_register.php" method="POST">
                    <div class="form-group">
                        <label for="register-name">Nama Lengkap</label>
                        <input type="text" id="register-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="register-nik">NIK</label>
                        <input type="text" id="register-nik" name="nik" class="form-control" pattern="[0-9]{16}" title="NIK harus 16 digit angka" required>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="register-phone">Nomor Telepon</label>
                        <input type="tel" id="register-phone" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Kata Sandi</label>
                        <input type="password" id="register-password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="register-confirm-password">Konfirmasi Kata Sandi</label>
                        <input type="password" id="register-confirm-password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="form-switch">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">Saya menyetujui <a href="#">Syarat dan Ketentuan</a></label>
                    </div>
                </form>
                <div class="login-link">
                    Sudah punya akun? <a href="#" id="switchToLogin">Masuk di sini</a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline modal-close">Batal</button>
                <button type="submit" form="registerForm" class="btn btn-primary">Daftar</button>
            </div>
        </div>
    </div>

    <!-- Buat Pengaduan Modal -->
    <div class="modal-backdrop" id="pengaduanModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Buat Pengaduan Baru</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Anda perlu masuk terlebih dahulu untuk membuat pengaduan.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline modal-close">Tutup</button>
                <button class="btn btn-primary" id="loginFromPengaduan">Masuk</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ toggle
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                question.addEventListener('click', () => {
                    item.classList.toggle('active');
                });
            });

            // Modal functionality
            const loginBtn = document.getElementById('loginBtn');
            const registerBtn = document.getElementById('registerBtn');
            const buatPengaduanBtn = document.getElementById('buatPengaduanBtn');
            const Btn = document.getElementById('loginBtn');
            const loginModal = document.getElementById('loginModal');
            const registerModal = document.getElementById('registerModal');
            const pengaduanModal = document.getElementById('pengaduanModal');
            const switchToRegister = document.getElementById('switchToRegister');
            const switchToLogin = document.getElementById('switchToLogin');
            const loginFromPengaduan = document.getElementById('loginFromPengaduan');
            
            // Close buttons
            const closeButtons = document.querySelectorAll('.modal-close');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    loginModal.classList.remove('active');
                    registerModal.classList.remove('active');
                    pengaduanModal.classList.remove('active');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === loginModal) {
                    loginModal.classList.remove('active');
                }
                if (event.target === registerModal) {
                    registerModal.classList.remove('active');
                }
                if (event.target === pengaduanModal) {
                    pengaduanModal.classList.remove('active');
                }
            });
            
            // Open modals
            loginBtn.addEventListener('click', function() {
                loginModal.classList.add('active');
            });
            
            registerBtn.addEventListener('click', function() {
                registerModal.classList.add('active');
            });
            
            buatPengaduanBtn.addEventListener('click', function() {
                // Check if user is logged in
                const isLoggedIn = checkLoginStatus(); // This function should check if user is logged in
                if (isLoggedIn) {
                    // Redirect to pengaduan form page
                    window.location.href = 'buat_pengaduan.php';
                } else {
                    pengaduanModal.classList.add('active');
                }
            });
            
            // Switch between modals
            switchToRegister.addEventListener('click', function(e) {
                e.preventDefault();
                loginModal.classList.remove('active');
                registerModal.classList.add('active');
            });
            
            switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                registerModal.classList.remove('active');
                loginModal.classList.add('active');
            });
            
            loginFromPengaduan.addEventListener('click', function() {
                pengaduanModal.classList.remove('active');
                loginModal.classList.add('active');
            });
            
            // Form validation
            const registerForm = document.getElementById('registerForm');
            registerForm.addEventListener('submit', function(e) {
                const password = document.getElementById('register-password').value;
                const confirmPassword = document.getElementById('register-confirm-password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Kata sandi dan konfirmasi kata sandi tidak cocok!');
                }
            });
            
            // Function to check login status
            function checkLoginStatus() {
                // This is a placeholder function
                // In a real application, you would check session/cookie/localStorage
                // or make an AJAX request to the server to check if user is logged in
                return false; // For demo purposes, always return false
            }
        });
        

        
    </script>
</body>
</html>