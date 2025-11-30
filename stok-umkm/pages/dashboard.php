<?php
session_start();
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_id = $_SESSION['user_id'];

// Get statistik
$stats = fetchRow("
    SELECT 
        COUNT(*) as total_produk,
        COALESCE(SUM(stok), 0) as total_stok,
        COALESCE(SUM(harga_beli * stok), 0) as nilai_inventori
    FROM produk 
    WHERE user_id = ?
", [$user_id]);

// Get produk stok menipis
$produk_menipis = fetchData("
    SELECT p.*, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.kategori_id = k.id
    WHERE p.user_id = ? AND p.stok <= p.stok_minimum
    ORDER BY p.stok ASC
    LIMIT 10
", [$user_id]);

// Get kategori count
$total_kategori = fetchRow("SELECT COUNT(*) as total FROM kategori WHERE user_id = ?", [$user_id]);

// Get transaksi hari ini
$transaksi_hari_ini = fetchRow("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(jumlah), 0) as total_keluar
    FROM stok_keluar 
    WHERE user_id = ? AND tanggal = CURDATE()
", [$user_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StokUMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F9FAFB;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 0;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        
        .menu-item i {
            font-size: 1.2rem;
            width: 24px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .topbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #8B5CF6, #6366F1);
            color: white;
        }
        
        .stat-icon.blue {
            background: linear-gradient(135deg, #06B6D4, #3B82F6);
            color: white;
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #F59E0B, #D97706);
            color: white;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1F2937;
            margin: 0;
        }
        
        .stat-label {
            color: #6B7280;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Alert Card */
        .alert-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        
        .alert-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #F3F4F6;
        }
        
        .alert-header h5 {
            margin: 0;
            font-weight: 700;
            color: #1F2937;
        }
        
        .table-custom {
            margin: 0;
        }
        
        .table-custom th {
            background: #F9FAFB;
            color: #6B7280;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            border: none;
            padding: 1rem;
        }
        
        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            color: #374151;
        }
        
        .badge-warning-custom {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-danger-custom {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9CA3AF;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-box-seam"></i>
                <span>StokUMKM</span>
            </a>
            <div class="mt-3" style="font-size: 0.85rem; opacity: 0.8;">
                <i class="bi bi-shop"></i> <?= htmlspecialchars($_SESSION['nama_usaha']) ?>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="kategori.php" class="menu-item">
                <i class="bi bi-folder"></i>
                <span>Kategori</span>
            </a>
            <a href="produk.php" class="menu-item">
                <i class="bi bi-box"></i>
                <span>Produk</span>
            </a>
            <a href="stok_masuk.php" class="menu-item">
                <i class="bi bi-arrow-down-circle"></i>
                <span>Stok Masuk</span>
            </a>
            <a href="stok_keluar.php" class="menu-item">
                <i class="bi bi-arrow-up-circle"></i>
                <span>Stok Keluar</span>
            </a>
            <a href="laporan.php" class="menu-item">
                <i class="bi bi-graph-up"></i>
                <span>Laporan</span>
            </a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 1rem 0;">
            <a href="../auth/logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">Selamat datang kembali, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!</small>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($_SESSION['email']) ?></small>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content-area">
            <!-- Stats Cards -->
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="bi bi-box"></i>
                        </div>
                        <h3 class="stat-value"><?= number_format($stats['total_produk']) ?></h3>
                        <p class="stat-label">Total Produk</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="bi bi-layers"></i>
                        </div>
                        <h3 class="stat-value"><?= number_format($stats['total_stok']) ?></h3>
                        <p class="stat-label">Total Stok</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h3 class="stat-value"><?= formatRupiah($stats['nilai_inventori']) ?></h3>
                        <p class="stat-label">Nilai Inventori</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <h3 class="stat-value"><?= count($produk_menipis) ?></h3>
                        <p class="stat-label">Stok Menipis</p>
                    </div>
                </div>
            </div>

            <!-- Alert Stok Menipis -->
            <div class="alert-card">
                <div class="alert-header">
                    <i class="bi bi-bell-fill text-warning" style="font-size: 1.5rem;"></i>
                    <h5>Produk Stok Menipis</h5>
                </div>
                
                <?php if(count($produk_menipis) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Stok Sekarang</th>
                                    <th class="text-center">Stok Minimum</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($produk_menipis as $produk): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($produk['nama_produk']) ?></strong>
                                        <?php if($produk['kode_produk']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($produk['kode_produk']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($produk['nama_kategori']) ?></td>
                                    <td class="text-center">
                                        <strong><?= $produk['stok'] ?></strong> <?= htmlspecialchars($produk['satuan']) ?>
                                    </td>
                                    <td class="text-center"><?= $produk['stok_minimum'] ?> <?= htmlspecialchars($produk['satuan']) ?></td>
                                    <td class="text-center">
                                        <?php if($produk['stok'] == 0): ?>
                                            <span class="badge-danger-custom">Habis</span>
                                        <?php else: ?>
                                            <span class="badge-warning-custom">Menipis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <p class="mb-0">Semua produk memiliki stok yang cukup!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>