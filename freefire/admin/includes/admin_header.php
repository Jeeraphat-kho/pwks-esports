<?php
/**
 * Shared Admin Header & Navigation Component
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/auth.php';

$active_admin_page = $active_admin_page ?? 'dashboard';
$page_title = $page_title ?? 'Admin Dashboard | Free Fire Esports';
$admin_name = $_SESSION['ff_admin_name'] ?? 'ผู้ดูแลระบบ';
$current_year = get_active_academic_year();
$available_years = get_available_academic_years();

// Filter year for admin view if set in query
$selected_year = $_GET['year'] ?? $current_year;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>

    <!-- Google Fonts: Kanit, Chakra Petch, Orbitron -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:ital,wght@0,400;0,600;0,700;1,700&family=Kanit:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.1 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Summernote Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
    <!-- Fancybox 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <!-- Custom Theme & Admin CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/contrast-fix.css">
</head>

<body class="admin-body">

    <!-- Top Admin Navigation Bar -->
    <nav class="navbar navbar-expand-xl admin-navbar sticky-top">
        <div class="container-fluid px-lg-4">
            
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-icon" style="width: 38px; height: 38px; font-size: 18px;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="brand-title" style="font-size: 17px;">PWKS <span class="highlight">ADMIN</span></span>
                    <span class="brand-sub" style="font-size: 10px;">Free Fire Management</span>
                </div>
            </a>

            <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarContent">
                <i class="fa-solid fa-bars text-warning"></i>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbarContent">
                
                <!-- Main Admin Menus -->
                <ul class="navbar-nav me-auto mb-2 mb-xl-0 ms-xl-3 gap-1">
                    <li class="nav-item">
                        <a class="admin-nav-link <?= $active_admin_page === 'dashboard' ? 'active' : '' ?>" href="index.php?year=<?= urlencode($selected_year) ?>">
                            <i class="fa-solid fa-gauge-high"></i> แดชบอร์ด
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?= $active_admin_page === 'teams' ? 'active' : '' ?>" href="teams.php?year=<?= urlencode($selected_year) ?>">
                            <i class="fa-solid fa-users"></i> จัดการทีม
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?= $active_admin_page === 'rules' ? 'active' : '' ?>" href="rules.php?year=<?= urlencode($selected_year) ?>">
                            <i class="fa-solid fa-scroll"></i> จัดการกติกา
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?= $active_admin_page === 'brackets' ? 'active' : '' ?>" href="brackets.php?year=<?= urlencode($selected_year) ?>">
                            <i class="fa-solid fa-sitemap"></i> จัดการสายแข่ง
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?= $active_admin_page === 'settings' ? 'active' : '' ?>" href="settings.php">
                            <i class="fa-solid fa-sliders"></i> ตั้งค่าระบบ
                        </a>
                    </li>
                </ul>

                <!-- Right Side Actions & Academic Year Switcher -->
                <div class="d-flex align-items-center flex-wrap gap-2 mt-3 mt-xl-0">
                    
                    <!-- Quick Academic Year Filter -->
                    <form method="GET" class="d-flex align-items-center gap-1">
                        <span class="text-secondary small font-chakra me-1"><i class="fa-solid fa-calendar-check text-warning"></i> ปีการศึกษา:</span>
                        <select name="year" class="form-select form-select-sm year-badge-selector py-1 pe-4" onchange="this.form.submit()" style="width: auto;">
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?= e($y) ?>" <?= $y === $selected_year ? 'selected' : '' ?>>
                                    ปี <?= e($y) ?> <?= ($y === $current_year) ? '(ใช้งานอยู่)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- View Live Front Site -->
                    <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-info font-chakra px-3" title="เปิดดูหน้าเว็บไซต์จริง">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> ดูหน้าเว็บ User
                    </a>

                    <!-- Admin Profile & Logout Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-warning dropdown-toggle font-chakra px-3 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user"></i> <?= e($admin_name) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow border-secondary border-opacity-50">
                            <li><h6 class="dropdown-header text-secondary font-chakra">ผู้ดูแลระบบ</h6></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fa-solid fa-key me-2"></i> เปลี่ยนรหัสผ่าน</a></li>
                            <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a></li>
                        </ul>
                    </div>

                </div>

            </div>

        </div>
    </nav>
