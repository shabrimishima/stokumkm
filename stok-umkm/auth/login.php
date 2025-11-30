<?php
session_start();
require_once '../config/database.php';

// Jika sudah login, redirect ke dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        // Cari user berdasarkan email
        $user = fetchRow("SELECT * FROM users WHERE email = ?", [$email]);
        
        if($user && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nama_usaha'] = $user['nama_usaha'];
            
            // Redirect ke dashboard
            header('Location: ../pages/dashboard.php');
            exit;
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StokUMKM</title>
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
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 700;
        }
        
        .login-body {
            padding: 3rem;
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
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-info {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        
        .demo-info strong {
            color: #92400E;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="login-card">
                    <div class="login-header">
                        <i class="bi bi-box-seam" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Selamat Datang!</h2>
                        <p class="mb-0">Login ke StokUMKM</p>
                    </div>
                    
                    <div class="login-body">
                        <!-- Demo Info -->
                        <div class="demo-info">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill text-warning me-2" style="font-size: 1.2rem;"></i>
                                <div>
                                    <strong>Akun Demo:</strong><br>
                                    <small>Email: <code>ahmad@gmail.com</code></small><br>
                                    <small>Password: <code>admin123</code></small>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="Masukkan email Anda" required autofocus
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Masukkan password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>
                        
                        <div class="register-link">
                            Belum punya akun? <a href="register.php">Daftar di sini</a>
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