<?php
session_start();
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_id = $_SESSION['user_id'];

// Filter tanggal
$tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

// Laporan Stok Produk
$laporan_stok = fetchData("
    SELECT 
        p.id,
        p.nama_produk,
        p.kode_produk,
        k.nama_kategori,
        p.stok as stok_sekarang,
        p.stok_minimum,
        p.satuan,
        p.harga_beli,
        p.harga_jual,
        COALESCE(SUM(sm.jumlah), 0) as total_masuk,
        COALESCE(SUM(sk.jumlah), 0) as total_keluar,
        (p.harga_beli * p.stok) as nilai_stok
    FROM produk p
    LEFT JOIN kategori k ON p.kategori_id = k.id
    LEFT JOIN stok_masuk sm ON p.id = sm.produk_id AND sm.tanggal BETWEEN ? AND ?
    LEFT JOIN stok_keluar sk ON p.id = sk.produk_id AND sk.tanggal BETWEEN ? AND ?
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.nama_produk
", [$tanggal_dari, $tanggal_sampai, $tanggal_dari, $tanggal_sampai, $user_id]);

// Summary
$total_nilai_stok = 0;
$total_produk = count($laporan_stok);
$produk_habis = 0;
$produk_menipis = 0;

foreach($laporan_stok as $item) {
    $total_nilai_stok += $item['nilai_stok'];
    if ($item['stok_sekarang'] == 0) $produk_habis++;
    if ($item['stok_sekarang'] > 0 && $item['stok_sekarang'] <= $item['stok_minimum']) $produk_menipis++;
}

// Transaksi Periode
$total_transaksi = fetchRow("
    SELECT 
        (SELECT COUNT(*) FROM stok_masuk WHERE user_id = ? AND tanggal BETWEEN ? AND ?) as total_masuk,
        (SELECT COUNT(*) FROM stok_keluar WHERE user_id = ? AND tanggal BETWEEN ? AND ?) as total_keluar,
        (SELECT COALESCE(SUM(jumlah * harga_satuan), 0) FROM stok_masuk WHERE user_id = ? AND tanggal BETWEEN ? AND ?) as nilai_pembelian
", [$user_id, $tanggal_dari, $tanggal_sampai, $user_id, $tanggal_dari, $tanggal_sampai, $user_id, $tanggal_dari, $tanggal_sampai]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - StokUMKM</title>
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
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        
        .summary-card.blue { border-color: #3B82F6; }
        .summary-card.green { border-color: #10B981; }
        .summary-card.orange { border-color: #F59E0B; }
        .summary-card.red { border-color: #EF4444; }
        .summary-card.purple { border-color: #8B5CF6; }
        
        .badge-aman {
            background: #D1FAE5;
            color: #065F46;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-menipis {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-habis {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        @media print {
            .sidebar, .topbar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .content-card {
                box-shadow: none !important;
                border: 1px solid #ddd;
            }
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
            <a href="dashboard.php" class="menu-item">
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
            <a href="laporan.php" class="menu-item active">
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
        <div class="topbar">
            <div>
                <h4 class="mb-0">Laporan Stok</h4>
                <small class="text-muted">Laporan dan analisis stok barang</small>
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

        <div class="content-area">
            <!-- Filter -->
            <div class="content-card no-print">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" id="tanggalDari" class="form-control" value="<?= $tanggal_dari ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" id="tanggalSampai" class="form-control" value="<?= $tanggal_sampai ?>">
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-primary" onclick="filterLaporan()">
                            <i class="bi bi-filter"></i> Tampilkan Laporan
                        </button>
                        <button class="btn btn-success" onclick="exportExcel()">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <h5 class="mb-3">Ringkasan Periode: <?= date('d/m/Y', strtotime($tanggal_dari)) ?> - <?= date('d/m/Y', strtotime($tanggal_sampai)) ?></h5>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="summary-card blue">
                        <h6 class="text-muted mb-2">Total Produk</h6>
                        <h3 class="mb-0"><?= $total_produk ?></h3>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="summary-card green">
                        <h6 class="text-muted mb-2">Nilai Total Stok</h6>
                        <h4 class="mb-0"><?= formatRupiah($total_nilai_stok) ?></h4>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="summary-card orange">
                        <h6 class="text-muted mb-2">Produk Menipis</h6>
                        <h3 class="mb-0"><?= $produk_menipis ?></h3>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="summary-card red">
                        <h6 class="text-muted mb-2">Produk Habis</h6>
                        <h3 class="mb-0"><?= $produk_habis ?></h3>
                    </div>
                </div>
            </div>

            <!-- Transaksi Periode -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="summary-card green">
                        <h6 class="text-muted mb-2">Transaksi Stok Masuk</h6>
                        <h3 class="mb-0"><?= $total_transaksi['total_masuk'] ?> kali</h3>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="summary-card red">
                        <h6 class="text-muted mb-2">Transaksi Stok Keluar</h6>
                        <h3 class="mb-0"><?= $total_transaksi['total_keluar'] ?> kali</h3>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="summary-card purple">
                        <h6 class="text-muted mb-2">Total Pembelian</h6>
                        <h4 class="mb-0"><?= formatRupiah($total_transaksi['nilai_pembelian']) ?></h4>
                    </div>
                </div>
            </div>

            <!-- Tabel Laporan -->
            <div class="content-card">
                <h5 class="mb-4">Detail Laporan Stok Produk</h5>
                
                <?php if (count($laporan_stok) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tableLaporan">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok Awal</th>
                                <th class="text-center">Masuk</th>
                                <th class="text-center">Keluar</th>
                                <th class="text-center">Stok Akhir</th>
                                <th class="text-end">Nilai Stok</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            foreach($laporan_stok as $item): 
                                $stok_awal = $item['stok_sekarang'] - $item['total_masuk'] + $item['total_keluar'];
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($item['nama_produk']) ?></strong>
                                    <?php if($item['kode_produk']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($item['kode_produk']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['nama_kategori']) ?></td>
                                <td class="text-center"><?= $stok_awal ?> <?= $item['satuan'] ?></td>
                                <td class="text-center text-success"><strong><?= $item['total_masuk'] ?></strong></td>
                                <td class="text-center text-danger"><strong><?= $item['total_keluar'] ?></strong></td>
                                <td class="text-center"><strong><?= $item['stok_sekarang'] ?></strong> <?= $item['satuan'] ?></td>
                                <td class="text-end"><?= formatRupiah($item['nilai_stok']) ?></td>
                                <td class="text-center">
                                    <?php if($item['stok_sekarang'] == 0): ?>
                                        <span class="badge-habis">Habis</span>
                                    <?php elseif($item['stok_sekarang'] <= $item['stok_minimum']): ?>
                                        <span class="badge-menipis">Menipis</span>
                                    <?php else: ?>
                                        <span class="badge-aman">Aman</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end">TOTAL NILAI STOK:</th>
                                <th class="text-end"><?= formatRupiah($total_nilai_stok) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-text" style="font-size: 5rem; color: #D1D5DB;"></i>
                    <h5 class="mt-3">Tidak Ada Data</h5>
                    <p class="text-muted">Belum ada data laporan untuk periode ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterLaporan() {
            const dari = document.getElementById('tanggalDari').value;
            const sampai = document.getElementById('tanggalSampai').value;
            window.location.href = `laporan.php?dari=${dari}&sampai=${sampai}`;
        }

        function exportExcel() {
            const table = document.getElementById('tableLaporan');
            let csv = [];
            
            // Header
            const headerRow = table.querySelector('thead tr');
            let headerData = [];
            headerRow.querySelectorAll('th').forEach(th => {
                headerData.push(th.textContent.trim());
            });
            csv.push(headerData.join(';')); // Semicolon untuk Excel Indonesia
            
            // Data
            table.querySelectorAll('tbody tr').forEach(tr => {
                let rowData = [];
                tr.querySelectorAll('td').forEach(td => {
                    // Bersihkan text, hapus newline dan extra spaces
                    let text = td.textContent.trim().replace(/\s+/g, ' ');
                    // Escape double quotes
                    text = text.replace(/"/g, '""');
                    rowData.push('"' + text + '"');
                });
                csv.push(rowData.join(';')); // Semicolon untuk Excel Indonesia
            });
            
            // Tambahkan UTF-8 BOM agar Excel bisa baca karakter Indonesia
            const BOM = '\uFEFF';
            const csvContent = BOM + csv.join('\r\n'); // Gunakan \r\n untuk Windows
            
            // Download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            const tanggal = new Date().toISOString().split('T')[0];
            link.download = `laporan_stok_${tanggal}.csv`;
            link.click();
            
            // Cleanup
            setTimeout(() => URL.revokeObjectURL(link.href), 100);
        }
    </script>
</body>
</html>