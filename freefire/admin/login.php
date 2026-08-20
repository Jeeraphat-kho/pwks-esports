<?php
/**
 * FREE FIRE ESPORTS - Secure Admin Login
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (!empty($_SESSION['ff_admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Session หมดอายุ กรุณารีเฟรชหน้าเว็บและลองใหม่';
    } elseif (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `freefire_admins` WHERE `username` = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login Success
                $_SESSION['ff_admin_id'] = $admin['id'];
                $_SESSION['ff_admin_username'] = $admin['username'];
                $_SESSION['ff_admin_name'] = $admin['name'];
                $_SESSION['ff_admin_role'] = $admin['role'];
                $_SESSION['ff_admin_logged_in'] = true;

                // Update last login
                $stmt_up = $pdo->prepare("UPDATE `freefire_admins` SET `last_login` = NOW() WHERE `id` = ?");
                $stmt_up->execute([$admin['id']]);

                set_flash('success', "ยินดีต้อนรับคุณ {$admin['name']} เข้าสู่ระบบจัดการ Free Fire Esports");
                header('Location: index.php');
                exit;
            } else {
                $error = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ระบบจัดการ Free Fire Esports</title>

    <!-- Google Fonts: Kanit, Chakra Petch, Orbitron -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:ital,wght@0,400;0,600;0,700;1,700&family=Kanit:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.1 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Custom Esports CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <!-- Background Particle Canvas -->
    <canvas id="particleCanvas"></canvas>
    
    <!-- Cyber Grid Overlay & Ambient Glowing Orbs -->
    <div class="bg-grid"></div>
    <div class="glow-orb-left"></div>
    <div class="glow-orb-right"></div>

    <div class="main-content d-flex flex-column min-vh-100 justify-content-between">

        <!-- Top Navigation Bar -->
        <header class="py-3">
            <div class="container d-flex justify-content-between align-items-center">
                <a href="../index.php" class="navbar-brand">
                    <div class="brand-icon">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    </div>
                    <div class="brand-text">
                        <span class="brand-title">PWKS <span class="highlight">FREE FIRE</span></span>
                        <span class="brand-sub">Admin Portal</span>
                    </div>
                </a>

                <div class="d-flex gap-2">
                    <a href="../index.php" class="nav-portal-btn">
                        <i class="fa-solid fa-arrow-left"></i> กลับหน้าเว็บ Free Fire
                    </a>
                </div>
            </div>
        </header>

        <!-- Admin Login Section -->
        <main class="admin-login-wrapper">
            <div class="container d-flex justify-content-center">
                
                <div class="admin-login-card text-center">
                    
                    <div class="admin-login-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div class="school-badge fire-badge mb-2">
                        โรงเรียนพร้าววิทยาคม
                    </div>

                    <h2 class="text-white fw-bold mb-1 font-orbitron" style="letter-spacing: 1px;">
                        ADMIN LOGIN
                    </h2>
                    <p class="text-secondary small mb-4 font-kanit">
                        เข้าสู่ระบบจัดการข้อมูลการแข่งขัน Free Fire Esports
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show text-start small py-2 px-3 mb-3 border-danger" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= e($error) ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="login.php">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3 text-start">
                            <label for="adminUsername" class="form-label">
                                <i class="fa-solid fa-user text-warning me-1"></i> ชื่อผู้ใช้งาน (Username)
                            </label>
                            <div class="input-icon-group">
                                <input type="text" class="form-control" id="adminUsername" name="username" value="<?= e($_POST['username'] ?? 'admin') ?>" placeholder="Username" required autofocus autocomplete="username">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="adminPassword" class="form-label">
                                <i class="fa-solid fa-lock text-warning me-1"></i> รหัสผ่าน (Password)
                            </label>
                            <div class="input-icon-group">
                                <input type="password" class="form-control" id="adminPassword" name="password" placeholder="Password" required autocomplete="current-password">
                                <i class="fa-solid fa-key"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-esports btn-esports-primary w-100 py-3 mb-3 fs-6">
                            <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ (Sign In)
                        </button>

                    </form>

                    <div class="pt-3 border-top border-secondary border-opacity-25">
                        <a href="../../index.html" class="text-secondary text-decoration-none small d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-house-laptop"></i> กลับสู่หน้าหลัก Esports รวมของโรงเรียน
                        </a>
                    </div>

                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center py-3 text-secondary small font-chakra">
            <div class="container">
                © 2026 Admin Portal • การแข่งขัน Esports โรงเรียนพร้าววิทยาคม
            </div>
        </footer>

    </div>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
