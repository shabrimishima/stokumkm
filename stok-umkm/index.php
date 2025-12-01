<?php
require_once 'config.php';

// Ambil statistik dari database
$stats = [];

// Total Produk
$result = $conn->query("SELECT COUNT(*) as total FROM produk");
$stats['total_produk'] = $result->fetch_assoc()['total'];

// Total Bahan Baku
$result = $conn->query("SELECT COUNT(*) as total FROM bahan_baku");
$stats['total_bahan'] = $result->fetch_assoc()['total'];

// Total Transaksi (Produksi + Penjualan)
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM penjualan) as total");
$stats['total_transaksi'] = $result->fetch_assoc()['total'];

// Total Penjualan (Omzet)
$result = $conn->query("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan");
$stats['total_penjualan'] = $result->fetch_assoc()['total'];

// Total Produksi Bulan Ini
$result = $conn->query("SELECT COUNT(*) as total FROM produksi 
                        WHERE MONTH(tanggal_produksi) = MONTH(CURRENT_DATE()) 
                        AND YEAR(tanggal_produksi) = YEAR(CURRENT_DATE())");
$stats['produksi_bulan_ini'] = $result->fetch_assoc()['total'];

// Total Penjualan Bulan Ini
$result = $conn->query("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan 
                        WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) 
                        AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
$stats['penjualan_bulan_ini'] = $result->fetch_assoc()['total'];

// Produk dengan Stok Rendah (< 5)
$result = $conn->query("SELECT COUNT(*) as total FROM produk WHERE stok < 5");
$stats['stok_rendah'] = $result->fetch_assoc()['total'];

// Produk Terlaris
$produk_terlaris = $conn->query("SELECT p.nama_produk, SUM(pj.jumlah_terjual) as total_terjual
                                 FROM penjualan pj
                                 JOIN produk p ON pj.id_produk = p.id_produk
                                 GROUP BY p.id_produk
                                 ORDER BY total_terjual DESC
                                 LIMIT 3");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem UMKM - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e0e0e0;
            padding: 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .sidebar-brand {
            font-size: 20px;
            font-weight: 600;
            color: #1a73e8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-brand i {
            font-size: 24px;
        }
        
        .sidebar-menu {
            padding: 16px 0;
        }
        
        .menu-item {
            display: block;
            padding: 12px 20px;
            color: #5f6368;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            background-color: #f1f3f4;
            color: #1a73e8;
            border-left-color: #1a73e8;
        }
        
        .menu-item i {
            width: 20px;
            margin-right: 12px;
        }
        
        .menu-item.active {
            background-color: #e8f0fe;
            color: #1a73e8;
            border-left-color: #1a73e8;
            font-weight: 500;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 0;
        }
        
        /* Top Bar */
        .top-bar {
            background: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 500;
            color: #202124;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1a73e8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }
        
        /* Content Area */
        .content-area {
            padding: 32px;
        }
        
        /* Alert Box */
        .alert-box {
            background: #fff4e5;
            border-left: 4px solid #ff9800;
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-box i {
            color: #ff9800;
            font-size: 20px;
        }
        
        .alert-box a {
            color: #1a73e8;
            font-weight: 500;
            text-decoration: none;
        }
        
        .alert-box a:hover {
            text-decoration: underline;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        
        .stat-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 13px;
            color: #5f6368;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }
        
        .stat-card.blue .stat-icon { background: #e8f0fe; color: #1a73e8; }
        .stat-card.green .stat-icon { background: #e6f4ea; color: #1e8e3e; }
        .stat-card.orange .stat-icon { background: #fef7e0; color: #f9ab00; }
        .stat-card.red .stat-icon { background: #fce8e6; color: #d93025; }
        
        /* Menu Cards */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .menu-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            display: block;
        }
        
        .menu-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            border-color: #1a73e8;
        }
        
        .menu-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #f1f3f4;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 22px;
            color: #5f6368;
        }
        
        .menu-card:hover .menu-card-icon {
            background: #e8f0fe;
            color: #1a73e8;
        }
        
        .menu-card-title {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .menu-card-desc {
            font-size: 13px;
            color: #5f6368;
            line-height: 1.4;
        }
        
        /* Info Panels */
        .info-panel {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
        }
        
        .panel-header {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .panel-header i {
            color: #5f6368;
        }
        
        .guide-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .guide-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .guide-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #e8f0fe;
            color: #1a73e8;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 13px;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .guide-title {
            font-size: 14px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 6px;
        }
        
        .guide-desc {
            font-size: 13px;
            color: #5f6368;
            line-height: 1.5;
            margin-left: 32px;
        }
        
        .product-rank {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .product-rank:last-child {
            border-bottom: none;
        }
        
        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #f1f3f4;
            color: #5f6368;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-size: 13px;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .rank-badge.first { background: #fef7e0; color: #f9ab00; }
        
        .product-name {
            font-size: 14px;
            color: #202124;
            font-weight: 500;
        }
        
        .product-sales {
            font-size: 13px;
            color: #5f6368;
            background: #f1f3f4;
            padding: 4px 12px;
            border-radius: 12px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .summary-label {
            font-size: 12px;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-store"></i>
                <span>Sistem UMKM</span>
            </a>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="pages/bahan_baku.php" class="menu-item">
                <i class="fas fa-warehouse"></i> Bahan Baku
            </a>
            <a href="pages/produk.php" class="menu-item">
                <i class="fas fa-box-open"></i> Produk
            </a>
            <a href="pages/komposisi.php" class="menu-item">
                <i class="fas fa-layer-group"></i> Komposisi Produk
            </a>
            <a href="pages/produksi.php" class="menu-item">
                <i class="fas fa-industry"></i> Produksi
            </a>
            <a href="pages/penjualan.php" class="menu-item">
                <i class="fas fa-cash-register"></i> Penjualan
            </a>
            <a href="pages/laporan.php" class="menu-item">
                <i class="fas fa-file-invoice-dollar"></i> Laporan Keuangan
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <span>Admin</span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Alert Stok Rendah -->
            <?php if($stats['stok_rendah'] > 0): ?>
            <div class="alert-box">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Peringatan Stok!</strong> Ada <?= $stats['stok_rendah'] ?> produk dengan stok rendah (kurang dari 5 unit). 
                    <a href="pages/produk.php">Lihat detail</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-label">Total Produk</div>
                    <div class="stat-value"><?= $stats['total_produk'] ?></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div class="stat-label">Bahan Baku</div>
                    <div class="stat-value"><?= $stats['total_bahan'] ?></div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-label">Total Transaksi</div>
                    <div class="stat-value"><?= $stats['total_transaksi'] ?></div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-label">Total Penjualan</div>
                    <div class="stat-value"><?= formatRupiah($stats['total_penjualan']) ?></div>
                </div>
            </div>

            <!-- Menu Navigation -->
            <div class="menu-grid">
                <a href="pages/bahan_baku.php" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="menu-card-title">Bahan Baku</div>
                    <div class="menu-card-desc">Kelola data bahan baku untuk produksi</div>
                </a>
                <a href="pages/produk.php" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="menu-card-title">Produk</div>
                    <div class="menu-card-desc">Kelola data produk jadi dan stok</div>
                </a>
                <a href="pages/komposisi.php" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="menu-card-title">Komposisi Produk</div>
                    <div class="menu-card-desc">Atur resep dan komposisi bahan</div>
                </a>
                <a href="pages/produksi.php" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div class="menu-card-title">Produksi</div>
                    <div class="menu-card-desc">Catat kegiatan produksi dan hitung HPP</div>
                </a>
                <a href="pages/penjualan.php" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="menu-card-title">Penjualan</div>
                    <div class="menu-card-desc">Input dan kelola transaksi penjualan</div>
                </a>
                <a href="pages/laporan.php" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="menu-card-title">Laporan Keuangan</div>
                    <div class="menu-card-desc">Lihat laporan laba-rugi bisnis</div>
                </a>
            </div>

            <!-- Info Panels -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="info-panel">
                        <h3 class="panel-header">
                            <i class="fas fa-lightbulb"></i> Panduan Penggunaan
                        </h3>
                        <div class="guide-item">
                            <div class="guide-title">
                                <span class="guide-number">1</span>
                                Input Data Bahan Baku
                            </div>
                            <p class="guide-desc">Masukkan semua bahan yang digunakan untuk produksi beserta harga per satuan.</p>
                        </div>
                        <div class="guide-item">
                            <div class="guide-title">
                                <span class="guide-number">2</span>
                                Tambah Produk
                            </div>
                            <p class="guide-desc">Daftarkan produk yang akan dijual beserta harga jual.</p>
                        </div>
                        <div class="guide-item">
                            <div class="guide-title">
                                <span class="guide-number">3</span>
                                Atur Komposisi
                            </div>
                            <p class="guide-desc">Tentukan resep produk (berapa bahan baku yang dibutuhkan per produk).</p>
                        </div>
                        <div class="guide-item">
                            <div class="guide-title">
                                <span class="guide-number">4</span>
                                Catat Produksi & Penjualan
                            </div>
                            <p class="guide-desc">Sistem akan otomatis menghitung HPP dan keuntungan Anda.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="info-panel">
                        <h3 class="panel-header">
                            <i class="fas fa-chart-bar"></i> Produk Terlaris
                        </h3>
                        <?php if($produk_terlaris->num_rows > 0): ?>
                            <?php 
                            $rank = 1;
                            while($produk = $produk_terlaris->fetch_assoc()): 
                            ?>
                            <div class="product-rank">
                                <div>
                                    <span class="rank-badge <?= $rank == 1 ? 'first' : '' ?>"><?= $rank ?></span>
                                    <span class="product-name"><?= $produk['nama_produk'] ?></span>
                                </div>
                                <span class="product-sales"><?= $produk['total_terjual'] ?> unit</span>
                            </div>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        <?php else: ?>
                            <p style="color: #5f6368; font-size: 14px;">Belum ada data penjualan</p>
                        <?php endif; ?>
                        
                        <div class="stats-summary">
                            <div class="summary-item">
                                <div class="summary-value"><?= $stats['produksi_bulan_ini'] ?></div>
                                <div class="summary-label">Produksi Bulan Ini</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value"><?= formatRupiah($stats['penjualan_bulan_ini']) ?></div>
                                <div class="summary-label">Omzet Bulan Ini</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>