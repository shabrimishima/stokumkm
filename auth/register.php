<?php
session_start();
require_once '../config/database.php';

// Jika sudah login, redirect ke dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = cleanInput($_POST['nama_lengkap']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $nama_usaha = cleanInput($_POST['nama_usaha']);
    $telepon = cleanInput($_POST['telepon']);
    $alamat = cleanInput($_POST['alamat']);
    
    // Validasi
    if(empty($nama_lengkap) || empty($email) || empty($password) || empty($nama_usaha)) {
        $error = 'Semua field wajib diisi!';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif(strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif($password !== $password_confirm) {
        $error = 'Password tidak cocok!';
    } else {
        // Cek email sudah terdaftar
        $check = fetchRow("SELECT id FROM users WHERE email = ?", [$email]);
        
        if($check) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            $sql = "INSERT INTO users (nama_lengkap, email, password, nama_usaha, telepon, alamat) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            if(executeQuery($sql, [$nama_lengkap, $email, $hashed_password, $nama_usaha, $telepon, $alamat])) {
                $success = 'Registrasi berhasil! Silakan login.';
                
                // Redirect ke login setelah 2 detik
                header("refresh:2;url=login.php");
            } else {
                $error = 'Registrasi gagal! Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - StokUMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-header h2 {
            margin: 0;
            font-weight: 700;
        }
        
        .register-body {
            padding: 2.5rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
        .btn-primary-custom {
            background: var(--primary);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
        }
        
        .input-group-text {
            background: #F3F4F6;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="register-card">
                    <div class="register-header">
                        <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                        <h2 class="mt-2">Daftar StokUMKM</h2>
                        <p class="mb-0">Mulai kelola stok bisnis Anda</p>
                    </div>
                    
                    <div class="register-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?= $success ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="nama_lengkap" class="form-control" 
                                           placeholder="Nama lengkap Anda" required 
                                           value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="email@example.com" required
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Minimal 6 karakter" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="password_confirm" class="form-control" 
                                           placeholder="Ulangi password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nama Usaha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shop"></i></span>
                                    <input type="text" name="nama_usaha" class="form-control" 
                                           placeholder="Nama toko/usaha Anda" required
                                           value="<?= isset($_POST['nama_usaha']) ? htmlspecialchars($_POST['nama_usaha']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Telepon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" name="telepon" class="form-control" 
                                           placeholder="08xxxxxxxxxx"
                                           value="<?= isset($_POST['telepon']) ? htmlspecialchars($_POST['telepon']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Alamat</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <textarea name="alamat" class="form-control" rows="2" 
                                              placeholder="Alamat usaha"><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="bi bi-rocket-takeoff"></i> Daftar Sekarang
                            </button>
                        </form>
                        
                        <div class="back-link">
                            Sudah punya akun? <a href="login.php">Login di sini</a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-white text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>