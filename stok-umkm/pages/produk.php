<?php
session_start();
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_id = $_SESSION['user_id'];

// Handle AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    // CREATE
    if ($action === 'create') {
        $nama = cleanInput($_POST['nama_produk']);
        $kategori_id = (int)$_POST['kategori_id'];
        $kode = cleanInput($_POST['kode_produk']);
        $harga_beli = (float)$_POST['harga_beli'];
        $harga_jual = (float)$_POST['harga_jual'];
        $stok = (int)$_POST['stok'];
        $stok_minimum = (int)$_POST['stok_minimum'];
        $satuan = cleanInput($_POST['satuan']);
        $deskripsi = cleanInput($_POST['deskripsi']);
        
        if (empty($nama) || empty($kategori_id)) {
            echo json_encode(['success' => false, 'message' => 'Nama produk dan kategori wajib diisi!']);
            exit;
        }
        
        $sql = "INSERT INTO produk (user_id, kategori_id, nama_produk, kode_produk, harga_beli, harga_jual, stok, stok_minimum, satuan, deskripsi) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if (executeQuery($sql, [$user_id, $kategori_id, $nama, $kode, $harga_beli, $harga_jual, $stok, $stok_minimum, $satuan, $deskripsi])) {
            echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk!']);
        }
        exit;
    }
    
    // UPDATE
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $nama = cleanInput($_POST['nama_produk']);
        $kategori_id = (int)$_POST['kategori_id'];
        $kode = cleanInput($_POST['kode_produk']);
        $harga_beli = (float)$_POST['harga_beli'];
        $harga_jual = (float)$_POST['harga_jual'];
        $stok_minimum = (int)$_POST['stok_minimum'];
        $satuan = cleanInput($_POST['satuan']);
        $deskripsi = cleanInput($_POST['deskripsi']);
        
        $sql = "UPDATE produk SET nama_produk = ?, kategori_id = ?, kode_produk = ?, harga_beli = ?, harga_jual = ?, stok_minimum = ?, satuan = ?, deskripsi = ? 
                WHERE id = ? AND user_id = ?";
        if (executeQuery($sql, [$nama, $kategori_id, $kode, $harga_beli, $harga_jual, $stok_minimum, $satuan, $deskripsi, $id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate produk!']);
        }
        exit;
    }
    
    // DELETE
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        $sql = "DELETE FROM produk WHERE id = ? AND user_id = ?";
        if (executeQuery($sql, [$id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk!']);
        }
        exit;
    }
    
    // GET
    if ($action === 'get') {
        $id = (int)$_POST['id'];
        $produk = fetchRow("SELECT * FROM produk WHERE id = ? AND user_id = ?", [$id, $user_id]);
        
        if ($produk) {
            echo json_encode(['success' => true, 'data' => $produk]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan!']);
        }
        exit;
    }
}

// Get kategori untuk dropdown
$kategori_list = fetchData("SELECT * FROM kategori WHERE user_id = ? ORDER BY nama_kategori", [$user_id]);

// Get produk dengan filter
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$filter_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$sql = "SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.kategori_id = k.id 
        WHERE p.user_id = ?";
$params = [$user_id];

if (!empty($search)) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_kategori > 0) {
    $sql .= " AND p.kategori_id = ?";
    $params[] = $filter_kategori;
}

$sql .= " ORDER BY p.nama_produk ASC";
$produk_list = fetchData($sql, $params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - StokUMKM</title>
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
        
        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .badge-success {
            background: #D1FAE5;
            color: #065F46;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-warning {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-danger {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
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
            <a href="produk.php" class="menu-item active">
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
        <div class="topbar">
            <div>
                <h4 class="mb-0">Produk</h4>
                <small class="text-muted">Kelola produk dan stok</small>
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
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari produk..." style="width: 300px;">
                        <select id="filterKategori" class="form-select" style="width: 200px;">
                            <option value="">Semua Kategori</option>
                            <?php foreach($kategori_list as $kat): ?>
                            <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduk" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle"></i> Tambah Produk
                    </button>
                </div>

                <div id="alertContainer"></div>

                <?php if (count($produk_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th class="text-end">Harga Beli</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produk_list as $prod): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($prod['nama_produk']) ?></strong>
                                    <?php if($prod['kode_produk']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($prod['kode_produk']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($prod['nama_kategori']) ?></td>
                                <td class="text-end"><?= formatRupiah($prod['harga_beli']) ?></td>
                                <td class="text-end"><?= formatRupiah($prod['harga_jual']) ?></td>
                                <td class="text-center"><strong><?= $prod['stok'] ?></strong> <?= $prod['satuan'] ?></td>
                                <td class="text-center">
                                    <?php if($prod['stok'] == 0): ?>
                                        <span class="badge-danger">Habis</span>
                                    <?php elseif($prod['stok'] <= $prod['stok_minimum']): ?>
                                        <span class="badge-warning">Menipis</span>
                                    <?php else: ?>
                                        <span class="badge-success">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary" onclick="editProduk(<?= $prod['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteProduk(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['nama_produk']) ?>')">
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
                    <i class="bi bi-box" style="font-size: 5rem; color: #D1D5DB;"></i>
                    <h5 class="mt-3">Belum Ada Produk</h5>
                    <p class="text-muted">Tambahkan produk pertama Anda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Produk -->
    <div class="modal fade" id="modalProduk" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formProduk">
                    <div class="modal-body">
                        <input type="hidden" id="produkId" name="id">
                        <input type="hidden" id="action" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="namaProduk" name="nama_produk" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kode/SKU</label>
                                <input type="text" class="form-control" id="kodeProduk" name="kode_produk">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="kategoriProduk" name="kategori_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control" id="satuan" name="satuan" value="pcs">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Harga Beli</label>
                                <input type="number" class="form-control" id="hargaBeli" name="harga_beli" value="0" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Harga Jual</label>
                                <input type="number" class="form-control" id="hargaJual" name="harga_jual" value="0" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stok Minimum</label>
                                <input type="number" class="form-control" id="stokMinimum" name="stok_minimum" value="5" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3" id="stokAwalDiv">
                            <label class="form-label">Stok Awal</label>
                            <input type="number" class="form-control" id="stok" name="stok" value="0" min="0">
                            <small class="text-muted">Stok awal produk (hanya saat tambah produk baru)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsiProduk" name="deskripsi" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
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
            document.getElementById('modalTitle').textContent = 'Tambah Produk';
            document.getElementById('formProduk').reset();
            document.getElementById('action').value = 'create';
            document.getElementById('stokAwalDiv').style.display = 'block';
        }

        function editProduk(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);

            fetch('produk.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Edit Produk';
                    document.getElementById('action').value = 'update';
                    document.getElementById('produkId').value = data.data.id;
                    document.getElementById('namaProduk').value = data.data.nama_produk;
                    document.getElementById('kodeProduk').value = data.data.kode_produk || '';
                    document.getElementById('kategoriProduk').value = data.data.kategori_id;
                    document.getElementById('hargaBeli').value = data.data.harga_beli;
                    document.getElementById('hargaJual').value = data.data.harga_jual;
                    document.getElementById('stokMinimum').value = data.data.stok_minimum;
                    document.getElementById('satuan').value = data.data.satuan;
                    document.getElementById('deskripsiProduk').value = data.data.deskripsi || '';
                    document.getElementById('stokAwalDiv').style.display = 'none';
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalProduk'));
                    modal.show();
                }
            });
        }

        function deleteProduk(id, nama) {
            if (confirm(`Hapus produk "${nama}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('produk.php', {
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

        document.getElementById('formProduk').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;

            fetch('produk.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnSubmit.disabled = false;
                
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalProduk')).hide();
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            });
        });

        // Filter
        document.getElementById('filterKategori').addEventListener('change', function() {
            window.location.href = 'produk.php?kategori=' + this.value;
        });

        document.getElementById('searchInput').addEventListener('input', function() {
            const value = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        });
    </script>
</body>
</html>