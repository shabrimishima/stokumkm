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
        $harga_satuan = (float)$_POST['harga_satuan'];
        $supplier = cleanInput($_POST['supplier']);
        $keterangan = cleanInput($_POST['keterangan']);
        
        if (empty($produk_id) || empty($tanggal) || $jumlah <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap!']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Insert stok masuk
            $sql = "INSERT INTO stok_masuk (user_id, produk_id, tanggal, jumlah, harga_satuan, supplier, keterangan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            executeQuery($sql, [$user_id, $produk_id, $tanggal, $jumlah, $harga_satuan, $supplier, $keterangan]);
            
            // Update stok produk
            $sql = "UPDATE produk SET stok = stok + ? WHERE id = ? AND user_id = ?";
            executeQuery($sql, [$jumlah, $produk_id, $user_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Stok masuk berhasil ditambahkan!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan stok masuk!']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        
        try {
            $pdo->beginTransaction();
            
            // Get data stok masuk
            $stok = fetchRow("SELECT * FROM stok_masuk WHERE id = ? AND user_id = ?", [$id, $user_id]);
            
            if (!$stok) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan!']);
                exit;
            }
            
            // Kurangi stok produk
            $sql = "UPDATE produk SET stok = stok - ? WHERE id = ? AND user_id = ?";
            executeQuery($sql, [$stok['jumlah'], $stok['produk_id'], $user_id]);
            
            // Hapus stok masuk
            $sql = "DELETE FROM stok_masuk WHERE id = ? AND user_id = ?";
            executeQuery($sql, [$id, $user_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Stok masuk berhasil dihapus!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus stok masuk!']);
        }
        exit;
    }
}

// Get produk untuk dropdown
$produk_list = fetchData("SELECT * FROM produk WHERE user_id = ? ORDER BY nama_produk", [$user_id]);

// Get riwayat stok masuk
$tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

$sql = "SELECT sm.*, p.nama_produk, p.satuan 
        FROM stok_masuk sm 
        JOIN produk p ON sm.produk_id = p.id 
        WHERE sm.user_id = ? AND sm.tanggal BETWEEN ? AND ?
        ORDER BY sm.tanggal DESC, sm.id DESC";
$stok_masuk_list = fetchData($sql, [$user_id, $tanggal_dari, $tanggal_sampai]);

// Total stok masuk
$total_masuk = fetchRow("SELECT COUNT(*) as total, SUM(jumlah * harga_satuan) as nilai 
                         FROM stok_masuk 
                         WHERE user_id = ? AND tanggal BETWEEN ? AND ?", 
                         [$user_id, $tanggal_dari, $tanggal_sampai]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Masuk - StokUMKM</title>
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
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 2rem;
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
            <a href="stok_masuk.php" class="menu-item active">
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
        <div class="topbar">
            <div>
                <h4 class="mb-0">Stok Masuk</h4>
                <small class="text-muted">Input pembelian dan barang masuk</small>
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
                <div class="col-md-6">
                    <div class="stat-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Total Transaksi</h5>
                                <h3><?= $total_masuk['total'] ?> kali</h3>
                            </div>
                            <i class="bi bi-arrow-down-circle" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-box" style="background: linear-gradient(135deg, #3B82F6, #1E40AF);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Total Nilai</h5>
                                <h3><?= formatRupiah($total_masuk['nilai'] ?? 0) ?></h3>
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
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalStokMasuk" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle"></i> Input Stok Masuk
                    </button>
                </div>

                <div id="alertContainer"></div>

                <?php if (count($stok_masuk_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Total</th>
                                <th>Supplier</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stok_masuk_list as $sm): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($sm['tanggal'])) ?></td>
                                <td><strong><?= htmlspecialchars($sm['nama_produk']) ?></strong></td>
                                <td class="text-center"><?= $sm['jumlah'] ?> <?= $sm['satuan'] ?></td>
                                <td class="text-end"><?= formatRupiah($sm['harga_satuan']) ?></td>
                                <td class="text-end"><strong><?= formatRupiah($sm['jumlah'] * $sm['harga_satuan']) ?></strong></td>
                                <td><?= htmlspecialchars($sm['supplier'] ?: '-') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-danger" onclick="deleteStok(<?= $sm['id'] ?>)">
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
                    <p class="text-muted">Mulai input stok masuk pertama Anda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalStokMasuk" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Input Stok Masuk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formStokMasuk">
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
                                <option value="<?= $prod['id'] ?>" data-satuan="<?= $prod['satuan'] ?>" data-harga="<?= $prod['harga_beli'] ?>">
                                    <?= htmlspecialchars($prod['nama_produk']) ?> (Stok: <?= $prod['stok'] ?> <?= $prod['satuan'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="jumlah" id="jumlahMasuk" required min="1" value="1">
                                    <span class="input-group-text" id="satuanText">pcs</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga Satuan</label>
                                <input type="number" class="form-control" name="harga_satuan" id="hargaSatuan" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" name="supplier" placeholder="Nama supplier/pemasok">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="2" placeholder="Catatan tambahan"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Stok produk akan otomatis bertambah setelah input
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="btnSubmit">
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
            document.getElementById('formStokMasuk').reset();
            document.querySelector('[name="tanggal"]').value = new Date().toISOString().split('T')[0];
        }

        // Auto fill harga beli saat pilih produk
        document.getElementById('produkSelect').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const satuan = option.getAttribute('data-satuan');
            const harga = option.getAttribute('data-harga');
            
            document.getElementById('satuanText').textContent = satuan || 'pcs';
            document.getElementById('hargaSatuan').value = harga || 0;
        });

        document.getElementById('formStokMasuk').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;

            fetch('stok_masuk.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnSubmit.disabled = false;
                
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalStokMasuk')).hide();
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            });
        });

        function deleteStok(id) {
            if (confirm('Hapus transaksi ini? Stok produk akan dikurangi kembali.')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('stok_masuk.php', {
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
            window.location.href = `stok_masuk.php?dari=${dari}&sampai=${sampai}`;
        }
    </script>
</body>
</html>