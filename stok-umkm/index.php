<?php
session_start();

// Jika sudah login, redirect ke dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StokUMKM - Sistem Manajemen Stok untuk UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #06B6D4;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 100px 0;
            min-height: 600px;
            display: flex;
            align-items: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom {
            background: white;
            color: var(--primary);
            border: none;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            background: transparent;
        }
        
        .btn-outline-custom:hover {
            background: white;
            color: var(--primary);
        }
        
        .feature-card {
            padding: 2rem;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .stats-section {
            background: #F9FAFB;
            padding: 80px 0;
        }
        
        .stat-card {
            text-align: center;
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: #6B7280;
            margin-top: 0.5rem;
        }
        
        .navbar-custom {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary) !important;
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        footer {
            background: #1F2937;
            color: white;
            padding: 40px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam"></i> StokUMKM
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#fitur">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-primary ms-3" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="auth/register.php">Daftar Gratis</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Kelola Stok UMKM Anda dengan Mudah</h1>
                    <p class="hero-subtitle">Sistem manajemen stok barang yang sederhana, praktis, dan gratis untuk semua UMKM Indonesia</p>
                    <div class="d-flex gap-3">
                        <a href="auth/register.php" class="btn btn-hero btn-primary-custom">
                            <i class="bi bi-rocket-takeoff"></i> Mulai Sekarang
                        </a>
                        <a href="#fitur" class="btn btn-hero btn-outline-custom">
                            <i class="bi bi-play-circle"></i> Lihat Fitur
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-graph-up-arrow" style="font-size: 15rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Gratis Selamanya</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Akses Kapan Saja</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">∞</div>
                        <div class="stat-label">Produk Tanpa Batas</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="fitur" class="py-5" style="padding: 100px 0;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Fitur Lengkap untuk UMKM</h2>
                <p class="lead text-muted">Semua yang Anda butuhkan untuk mengelola stok barang</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h4 class="mb-3">Manajemen Produk</h4>
                        <p class="text-muted">Kelola produk dengan kategori, harga beli, harga jual, dan stok dengan mudah</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-arrow-down-up"></i>
                        </div>
                        <h4 class="mb-3">Stok Masuk & Keluar</h4>
                        <p class="text-muted">Catat setiap transaksi stok masuk dan keluar dengan riwayat lengkap</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h4 class="mb-3">Alert Stok Menipis</h4>
                        <p class="text-muted">Dapatkan notifikasi otomatis saat stok produk hampir habis</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h4 class="mb-3">Laporan Real-time</h4>
                        <p class="text-muted">Lihat laporan stok dan nilai inventori secara real-time</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-phone"></i>
                        </div>
                        <h4 class="mb-3">Mobile Friendly</h4>
                        <p class="text-muted">Akses dari smartphone, tablet, atau komputer dengan responsive design</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="mb-3">Data Aman</h4>
                        <p class="text-muted">Data Anda tersimpan aman dengan enkripsi dan backup otomatis</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="display-5 fw-bold mb-4">Siap Mulai Mengelola Stok?</h2>
            <p class="lead mb-4">Daftar sekarang dan mulai optimalkan bisnis UMKM Anda</p>
            <a href="auth/register.php" class="btn btn-hero btn-primary-custom">
                <i class="bi bi-rocket-takeoff"></i> Daftar Gratis Sekarang
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; 2024 StokUMKM. Dibuat dengan <i class="bi bi-heart-fill text-danger"></i> untuk UMKM Indonesia</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>