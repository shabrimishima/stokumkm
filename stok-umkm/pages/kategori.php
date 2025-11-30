<?php
session_start();
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_id = $_SESSION['user_id'];

// Handle AJAX Request untuk CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    // CREATE
    if ($action === 'create') {
        $nama = cleanInput($_POST['nama_kategori']);
        $deskripsi = cleanInput($_POST['deskripsi']);
        
        if (empty($nama)) {
            echo json_encode(['success' => false, 'message' => 'Nama kategori wajib diisi!']);
            exit;
        }
        
        $sql = "INSERT INTO kategori (user_id, nama_kategori, deskripsi) VALUES (?, ?, ?)";
        if (executeQuery($sql, [$user_id, $nama, $deskripsi])) {
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan kategori!']);
        }
        exit;
    }
    
    // UPDATE
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $nama = cleanInput($_POST['nama_kategori']);
        $deskripsi = cleanInput($_POST['deskripsi']);
        
        if (empty($nama)) {
            echo json_encode(['success' => false, 'message' => 'Nama kategori wajib diisi!']);
            exit;
        }
        
        $sql = "UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id = ? AND user_id = ?";
        if (executeQuery($sql, [$nama, $deskripsi, $id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil diupdate!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate kategori!']);
        }
        exit;
    }
    
    // DELETE
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Cek apakah kategori masih digunakan
        $check = fetchRow("SELECT COUNT(*) as total FROM produk WHERE kategori_id = ? AND user_id = ?", [$id, $user_id]);
        
        if ($check['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh ' . $check['total'] . ' produk!']);
            exit;
        }
        
        $sql = "DELETE FROM kategori WHERE id = ? AND user_id = ?";
        if (executeQuery($sql, [$id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori!']);
        }
        exit;
    }
    
    // GET (untuk edit)
    if ($action === 'get') {
        $id = (int)$_POST['id'];
        $kategori = fetchRow("SELECT * FROM kategori WHERE id = ? AND user_id = ?", [$id, $user_id]);
        
        if ($kategori) {
            echo json_encode(['success' => true, 'data' => $kategori]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan!']);
        }
        exit;
    }
}

// Get semua kategori
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$sql = "SELECT k.*, COUNT(p.id) as jumlah_produk 
        FROM kategori k 
        LEFT JOIN produk p ON k.id = p.kategori_id 
        WHERE k.user_id = ?";

if (!empty($search)) {
    $sql .= " AND (k.nama_kategori LIKE ? OR k.deskripsi LIKE ?)";
    $params = [$user_id, "%$search%", "%$search%"];
} else {
    $params = [$user_id];
}

$sql .= " GROUP BY k.id ORDER BY k.nama_kategori ASC";
$kategori_list = fetchData($sql, $params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - StokUMKM</title>
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
        }
        
        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #EEF2FF;
            color: var(--primary);
        }
        
        .btn-edit:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-delete {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .btn-delete:hover {
            background: #DC2626;
            color: white;
        }
        
        .badge-info {
            background: #DBEAFE;
            color: #1E40AF;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #9CA3AF;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .search-box {
            position: relative;
            max-width: 400px;
        }
        
        .search-box input {
            padding-left: 2.5rem;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
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
            <a href="kategori.php" class="menu-item active">
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
                <h4 class="mb-0">Kategori Produk</h4>
                <small class="text-muted">Kelola kategori produk Anda</small>
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
            <div class="content-card">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari kategori...">
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle"></i> Tambah Kategori
                    </button>
                </div>

                <!-- Alert -->
                <div id="alertContainer"></div>

                <!-- Table -->
                <?php if (count($kategori_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="tableKategori">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Nama Kategori</th>
                                <th width="45%">Deskripsi</th>
                                <th width="15%" class="text-center">Jumlah Produk</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach($kategori_list as $kat): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($kat['nama_kategori']) ?></strong></td>
                                <td><?= htmlspecialchars($kat['deskripsi']) ?: '-' ?></td>
                                <td class="text-center">
                                    <span class="badge-info"><?= $kat['jumlah_produk'] ?> produk</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn-action btn-edit" onclick="editKategori(<?= $kat['id'] ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteKategori(<?= $kat['id'] ?>, '<?= htmlspecialchars($kat['nama_kategori']) ?>')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-folder-x"></i>
                    <h5>Belum Ada Kategori</h5>
                    <p>Mulai dengan menambahkan kategori produk pertama Anda</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle"></i> Tambah Kategori
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Kategori -->
    <div class="modal fade" id="modalKategori" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formKategori">
                    <div class="modal-body">
                        <input type="hidden" id="kategoriId" name="id">
                        <input type="hidden" id="action" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="namaKategori" name="nama_kategori" required placeholder="Contoh: Makanan, Minuman, dll">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Deskripsi kategori (opsional)"></textarea>
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
        // Show Alert
        function showAlert(message, type = 'success') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = alertHtml;
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 150);
                }
            }, 5000);
        }

        // Open Create Modal
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kategori';
            document.getElementById('formKategori').reset();
            document.getElementById('action').value = 'create';
            document.getElementById('kategoriId').value = '';
            document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-save"></i> Simpan';
        }

        // Edit Kategori
        function editKategori(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);

            fetch('kategori.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Edit Kategori';
                    document.getElementById('action').value = 'update';
                    document.getElementById('kategoriId').value = data.data.id;
                    document.getElementById('namaKategori').value = data.data.nama_kategori;
                    document.getElementById('deskripsi').value = data.data.deskripsi || '';
                    document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-save"></i> Update';
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalKategori'));
                    modal.show();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Terjadi kesalahan!', 'danger');
                console.error('Error:', error);
            });
        }

        // Delete Kategori
        function deleteKategori(id, nama) {
            if (confirm(`Apakah Anda yakin ingin menghapus kategori "${nama}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('kategori.php', {
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
                })
                .catch(error => {
                    showAlert('Terjadi kesalahan!', 'danger');
                    console.error('Error:', error);
                });
            }
        }

        // Submit Form
        document.getElementById('formKategori').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnSubmit = document.getElementById('btnSubmit');
            const originalText = btnSubmit.innerHTML;
            
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

            fetch('kategori.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
                
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalKategori')).hide();
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
                showAlert('Terjadi kesalahan!', 'danger');
                console.error('Error:', error);
            });
        });

        // Search
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tableKategori tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html>