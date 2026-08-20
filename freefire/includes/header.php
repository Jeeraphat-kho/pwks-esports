<?php
/**
 * Shared User Header & Navigation for Free Fire Esports
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$active_page = $active_page ?? 'home';
$page_title = $page_title ?? 'Free Fire Esports | โรงเรียนพร้าววิทยาคม';
$comp_name = get_setting('competition_name', 'FREE FIRE ESPORTS TOURNAMENT โรงเรียนพร้าววิทยาคม');
$current_year = get_active_academic_year();
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
    <!-- Fancybox 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <!-- Custom Esports CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/contrast-fix.css?v=<?= time() ?>">
</head>

<body>

    <!-- Background Particle Canvas -->
    <canvas id="particleCanvas"></canvas>
    
    <!-- Cyber Grid Overlay & Ambient Glowing Orbs -->
    <div class="bg-grid"></div>
    <div class="glow-orb-left"></div>
    <div class="glow-orb-right"></div>

    <div class="main-content">

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg custom-navbar">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <div class="brand-icon">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    </div>
                    <div class="brand-text">
                        <span class="brand-title">PWKS <span class="highlight">FREE FIRE</span></span>
                        <span class="brand-sub">Esports <?= e($current_year) ?></span>
                    </div>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page === 'home' ? 'active' : '' ?>" href="index.php">
                                <i class="fa-solid fa-house"></i> หน้าแรก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page === 'register' ? 'active' : '' ?>" href="register.php">
                                <i class="fa-solid fa-pen-to-square"></i> สมัครแข่งขัน
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page === 'rules' ? 'active' : '' ?>" href="rules.php">
                                <i class="fa-solid fa-scroll"></i> กติกา
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page === 'bracket' ? 'active' : '' ?>" href="bracket.php">
                                <i class="fa-solid fa-sitemap"></i> สายการแข่งขัน
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://sc.pwks.ac.th/main/to/disesports" target="_blank">
                                <i class="fa-brands fa-discord"></i> Discord
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                        <a href="../index.html" class="nav-portal-btn" title="กลับสู่หน้าเลือกเกมหลัก">
                            <i class="fa-solid fa-arrow-left"></i> เว็บหลัก Esports
                        </a>
                        <a href="admin/login.php" class="nav-admin-btn">
                            <i class="fa-solid fa-shield-halved"></i> Admin
                        </a>
                    </div>
                </div>
            </div>
        </nav>
