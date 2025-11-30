<?php
session_start();
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_id = $_SESSION['user_id'];

// Handle AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'create') {
        $produk_id = (int)$_POST['produk_id'];
        $tanggal = cleanInput($_POST['tanggal']);
        $jumlah = (int)$_POST['jumlah'];
        $keterangan = cleanInput($_POST['keterangan']);
        
        if (empty($produk_id) || empty($tanggal) || $jumlah <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap!']);
            exit;
        }
        
        // Cek stok tersedia
        $produk = fetchRow("SELECT stok, nama_produk FROM produk WHERE id = ? AND user_id = ?", [$produk_id, $user_id]);
        
        if (!$produk) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan!']);
            exit;
        }
        
        if ($produk['stok'] < $jumlah) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak cukup! Stok tersedia: ' . $produk['stok']]);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Insert stok keluar
            $sql = "INSERT INTO stok_keluar (user_id, produk_id, tanggal, jumlah, keterangan) 
                    VALUES (?, ?, ?, ?, ?)";
            executeQuery($sql, [$user_id, $produk_id, $tanggal, $jumlah, $keterangan]);
            
            // Update stok produk
            $sql = "UPDATE produk SET stok = stok - ? WHERE id = ? AND user_id = ?";
            executeQuery($sql, [$jumlah, $produk_id, $user_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Stok keluar berhasil ditambahkan!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan stok keluar!']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        
        try {
            $pdo->beginTransaction();
            
            // Get data stok keluar
            $stok = fetchRow("SELECT * FROM stok_keluar WHERE id = ? AND user_id = ?", [$id, $user_id]);
            
            if (!$stok) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan!']);
                exit;
            }
            
            // Kembalikan stok produk
            $sql = "UPDATE produk SET stok = stok + ? WHERE id = ? AND user_id = ?";
            executeQuery($sql, [$stok['jumlah'], $stok['produk_id'], $user_id]);
            
            // Hapus stok keluar
            $sql = "DELETE FROM stok_keluar WHERE id = ? AND user_id = ?";
            executeQuery($sql, [$id, $user_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Stok keluar berhasil dihapus!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus stok keluar!']);
        }
        exit;
    }
}

// Get produk untuk dropdown
$produk_list = fetchData("SELECT * FROM produk WHERE user_id = ? ORDER BY nama_produk", [$user_id]);

// Get riwayat stok keluar
$tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

$sql = "SELECT sk.*, p.nama_produk, p.satuan, p.harga_jual
        FROM stok_keluar sk 
        JOIN produk p ON sk.produk_id = p.id 
        WHERE sk.user_id = ? AND sk.tanggal BETWEEN ? AND ?
        ORDER BY sk.tanggal DESC, sk.id DESC";
$stok_keluar_list = fetchData($sql, [$user_id, $tanggal_dari, $tanggal_sampai]);

// Total stok keluar
$total_keluar = fetchRow("SELECT COUNT(*) as total, SUM(sk.jumlah) as total_item
                         FROM stok_keluar sk
                         WHERE sk.user_id = ? AND sk.tanggal BETWEEN ? AND ?", 
                         [$user_id, $tanggal_dari, $tanggal_sampai]);

// Hitung estimasi nilai jual
$nilai_jual = 0;
foreach($stok_keluar_list as $sk) {
    $nilai_jual += $sk['jumlah'] * $sk['harga_jual'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Keluar - StokUMKM</title>
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
        }
        
        .stat-box {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 2rem;
        }

        .badge-keterangan {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
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
            <a href="stok_keluar.php" class="menu-item active">
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
        <div class="topbar">
            <div>
                <h4 class="mb-0">Stok Keluar</h4>
                <small class="text-muted">Input penjualan dan barang keluar</small>
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
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Total Transaksi</h5>
                                <h3><?= $total_keluar['total'] ?> kali</h3>
                            </div>
                            <i class="bi bi-arrow-up-circle" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Total Item Keluar</h5>
                                <h3><?= $total_keluar['total_item'] ?? 0 ?> item</h3>
                            </div>
                            <i class="bi bi-box" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Estimasi Nilai Jual</h5>
                                <h3 style="font-size: 1.5rem;"><?= formatRupiah($nilai_jual) ?></h3>
                            </div>
                            <i class="bi bi-currency-dollar" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex gap-2">
                        <input type="date" id="tanggalDari" class="form-control" value="<?= $tanggal_dari ?>">
                        <input type="date" id="tanggalSampai" class="form-control" value="<?= $tanggal_sampai ?>">
                        <button class="btn btn-secondary" onclick="filterTanggal()">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalStokKeluar" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle"></i> Input Stok Keluar
                    </button>
                </div>

                <div id="alertContainer"></div>

                <?php if (count($stok_keluar_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th class="text-center">Jumlah</th>
                                <th>Keterangan</th>
                                <th class="text-end">Nilai (Est.)</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stok_keluar_list as $sk): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($sk['tanggal'])) ?></td>
                                <td><strong><?= htmlspecialchars($sk['nama_produk']) ?></strong></td>
                                <td class="text-center"><?= $sk['jumlah'] ?> <?= $sk['satuan'] ?></td>
                                <td>
                                    <span class="badge-keterangan"><?= htmlspecialchars($sk['keterangan']) ?></span>
                                </td>
                                <td class="text-end"><?= formatRupiah($sk['jumlah'] * $sk['harga_jual']) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-danger" onclick="deleteStok(<?= $sk['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 5rem; color: #D1D5DB;"></i>
                    <h5 class="mt-3">Belum Ada Transaksi</h5>
                    <p class="text-muted">Mulai input stok keluar pertama Anda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalStokKeluar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Input Stok Keluar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formStokKeluar">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" required value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Produk <span class="text-danger">*</span></label>
                            <select class="form-select" name="produk_id" id="produkSelect" required>
                                <option value="">Pilih Produk</option>
                                <?php foreach($produk_list as $prod): ?>
                                <option value="<?= $prod['id'] ?>" data-satuan="<?= $prod['satuan'] ?>" data-stok="<?= $prod['stok'] ?>">
                                    <?= htmlspecialchars($prod['nama_produk']) ?> (Stok: <?= $prod['stok'] ?> <?= $prod['satuan'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="jumlah" id="jumlahKeluar" required min="1" value="1">
                                <span class="input-group-text" id="satuanText">pcs</span>
                            </div>
                            <small class="text-muted">Stok tersedia: <span id="stokTersedia">-</span></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                            <select class="form-select" name="keterangan" required>
                                <option value="Terjual">Terjual</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Hilang">Hilang</option>
                                <option value="Return">Return/Retur</option>
                                <option value="Sample">Sample/Contoh</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Stok produk akan otomatis berkurang setelah input
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" id="btnSubmit">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAlert(message, type = 'success') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = alertHtml;
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }

        function openCreateModal() {
            document.getElementById('formStokKeluar').reset();
            document.querySelector('[name="tanggal"]').value = new Date().toISOString().split('T')[0];
            document.getElementById('stokTersedia').textContent = '-';
        }

        // Update info stok saat pilih produk
        document.getElementById('produkSelect').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const satuan = option.getAttribute('data-satuan');
            const stok = option.getAttribute('data-stok');
            
            document.getElementById('satuanText').textContent = satuan || 'pcs';
            document.getElementById('stokTersedia').textContent = stok ? `${stok} ${satuan}` : '-';
            document.getElementById('jumlahKeluar').max = stok || 0;
        });

        document.getElementById('formStokKeluar').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;

            fetch('stok_keluar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnSubmit.disabled = false;
                
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalStokKeluar')).hide();
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            });
        });

        function deleteStok(id) {
            if (confirm('Hapus transaksi ini? Stok produk akan dikembalikan.')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('stok_keluar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                });
            }
        }

        function filterTanggal() {
            const dari = document.getElementById('tanggalDari').value;
            const sampai = document.getElementById('tanggalSampai').value;
            window.location.href = `stok_keluar.php?dari=${dari}&sampai=${sampai}`;
        }
    </script>
</body>
</html>