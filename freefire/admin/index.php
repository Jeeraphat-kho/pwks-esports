<?php
/**
 * FREE FIRE ESPORTS - Admin Dashboard
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$active_admin_page = 'dashboard';
$page_title = 'แดชบอร์ดผู้ดูแลระบบ | Free Fire Esports';
$current_year = get_active_academic_year();
$selected_year = $_GET['year'] ?? $current_year;

// Query KPI metrics for $selected_year
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_teams,
    SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending_teams,
    SUM(CASE WHEN `status` = 'approved' THEN 1 ELSE 0 END) as approved_teams,
    SUM(CASE WHEN `status` = 'rejected' THEN 1 ELSE 0 END) as rejected_teams,
    SUM(CASE WHEN `level` = 'junior' THEN 1 ELSE 0 END) as junior_teams,
    SUM(CASE WHEN `level` = 'senior' THEN 1 ELSE 0 END) as senior_teams
FROM `freefire_teams` WHERE `academic_year` = ?");
$stmt->execute([$selected_year]);
$kpi = $stmt->fetch();

$total_teams = (int)($kpi['total_teams'] ?? 0);
$pending_teams = (int)($kpi['pending_teams'] ?? 0);
$approved_teams = (int)($kpi['approved_teams'] ?? 0);
$rejected_teams = (int)($kpi['rejected_teams'] ?? 0);
$junior_teams = (int)($kpi['junior_teams'] ?? 0);
$senior_teams = (int)($kpi['senior_teams'] ?? 0);

// Query Rules and Brackets count
$stmt_r = $pdo->prepare("SELECT COUNT(*) FROM `freefire_rules` WHERE `academic_year` = ?");
$stmt_r->execute([$selected_year]);
$total_rules = (int)$stmt_r->fetchColumn();

$stmt_b = $pdo->prepare("SELECT COUNT(*) FROM `freefire_brackets` WHERE `academic_year` = ?");
$stmt_b->execute([$selected_year]);
$total_brackets = (int)$stmt_b->fetchColumn();

// Query Recent 5 Registrations
$stmt_recent = $pdo->prepare("SELECT * FROM `freefire_teams` WHERE `academic_year` = ? ORDER BY `created_at` DESC LIMIT 5");
$stmt_recent->execute([$selected_year]);
$recent_teams = $stmt_recent->fetchAll();

$reg_status = get_setting('registration_status', 'open');

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid px-lg-4 py-4">
    
    <!-- Page Header & Academic Year Banner -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1 font-orbitron" style="letter-spacing: 0.5px;">
                <i class="fa-solid fa-gauge-high text-warning me-2"></i> ESPORTS DASHBOARD
            </h2>
            <p class="text-secondary small mb-0 font-kanit">
                ภาพรวมและสถิติระบบการแข่งขัน Free Fire ประจำปีการศึกษา <span class="text-warning fw-bold fs-6 font-chakra">ปี <?= e($selected_year) ?></span>
                <?php if ($selected_year === $current_year): ?>
                    <span class="badge bg-success font-chakra ms-1">ปีที่กำลังใช้งานในระบบ</span>
                <?php else: ?>
                    <span class="badge bg-secondary font-chakra ms-1">ข้อมูลปีการศึกษาอื่น</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-warning font-chakra">
                <i class="fa-solid fa-users me-1"></i> จัดการทีมทั้งหมด
            </a>
            <a href="settings.php" class="btn btn-sm btn-outline-secondary font-chakra">
                <i class="fa-solid fa-gear me-1"></i> ตั้งค่าการแข่งขัน
            </a>
        </div>
    </div>

    <?= render_flash() ?>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        
        <!-- Metric 1: Total Registered Teams -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card h-100" style="border-color: #ff5500;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value text-warning"><?= $total_teams ?></div>
                        <div class="metric-label">ทีมที่ลงทะเบียนทั้งหมด</div>
                    </div>
                    <div class="metric-icon-box" style="background: rgba(240, 70, 13, 0.12); color: #ff5500; border-color: rgba(240, 70, 13, 0.4);">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 small font-chakra">
                    <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="text-decoration-none" style="color: #ff5500;">
                        ดูทีมทั้งหมด <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric 2: Junior Level Teams -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card h-100" style="border-color: rgba(13, 202, 240, 0.4);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value text-info"><?= $junior_teams ?></div>
                        <div class="metric-label">มัธยมศึกษาตอนต้น (ม.1-3)</div>
                    </div>
                    <div class="metric-icon-box" style="background: rgba(13, 202, 240, 0.12); color: #0dcaf0; border-color: rgba(13, 202, 240, 0.4);">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 small font-chakra">
                    <a href="teams.php?year=<?= urlencode($selected_year) ?>&level=junior" class="text-info text-decoration-none">
                        ดูทีม ม.ต้น ทั้งหมด <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric 3: Senior Level Teams -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card h-100" style="border-color: rgba(255, 193, 7, 0.4);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value text-warning"><?= $senior_teams ?></div>
                        <div class="metric-label">ม.ปลาย / ปวช. (ม.4-6 / ปวช.1-3)</div>
                    </div>
                    <div class="metric-icon-box" style="background: rgba(255, 193, 7, 0.12); color: #ffc107; border-color: rgba(255, 193, 7, 0.4);">
                        <i class="fa-solid fa-award"></i>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 small font-chakra">
                    <a href="teams.php?year=<?= urlencode($selected_year) ?>&level=senior" class="text-warning text-decoration-none">
                        ดูทีม ม.ปลาย/ปวช. ทั้งหมด <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric 4: Rules & Brackets -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card h-100" style="border-color: rgba(40, 167, 69, 0.4);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value text-success"><?= $total_rules ?> <span class="fs-6 text-muted">/ <?= $total_brackets ?></span></div>
                        <div class="metric-label">หัวข้อกติกา / สายการแข่ง</div>
                    </div>
                    <div class="metric-icon-box" style="background: rgba(40, 167, 69, 0.12); color: #28a745; border-color: rgba(40, 167, 69, 0.4);">
                        <i class="fa-solid fa-sitemap"></i>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2 pt-2 border-top border-secondary border-opacity-25 small font-chakra">
                    <a href="rules.php?year=<?= urlencode($selected_year) ?>" class="text-success text-decoration-none">กติกา</a> • 
                    <a href="brackets.php?year=<?= urlencode($selected_year) ?>" class="text-success text-decoration-none">สายแข่ง</a>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Shortcuts & Recent Registrations -->
    <div class="row g-4">
        
        <!-- Recent 5 Teams -->
        <div class="col-lg-8">
            <div class="admin-table-wrapper">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="text-white fw-bold mb-0 font-chakra d-flex align-items-center gap-2">
                        <i class="fa-solid fa-list-check text-warning"></i> ทีมที่สมัครล่าสุด (ปีการศึกษา <?= e($selected_year) ?>)
                    </h5>
                    <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-warning font-chakra">
                        ดูทั้งหมด (<?= $total_teams ?>)
                    </a>
                </div>

                <?php if (empty($recent_teams)): ?>
                    <div class="empty-state-box py-4">
                        <div class="empty-icon fs-2 mb-2">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <h5 class="text-white fs-6 fw-bold font-chakra mb-1">ยังไม่มีทีมสมัครเข้าร่วมการแข่งขัน</h5>
                        <p class="text-light small mb-0">ปีการศึกษา <?= e($selected_year) ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-esports align-middle">
                            <thead>
                                <tr>
                                    <th>ชื่อทีม</th>
                                    <th>ระดับชั้น</th>
                                    <th>ครูที่ปรึกษา</th>
                                    <th>วันที่ลงทะเบียน</th>
                                    <th>สถานะ</th>
                                    <th class="text-end">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_teams as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-white fs-6"><?= e($t['team_name']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $t['level'] === 'junior' ? 'bg-info text-dark' : 'bg-warning text-dark' ?>">
                                                <?= $t['level'] === 'junior' ? 'ม.ต้น' : 'ม.ปลาย / ปวช.' ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary"><?= e($t['teacher_advisor']) ?></td>
                                        <td class="text-muted small font-chakra">
                                            <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?> น.
                                        </td>
                                        <td><?= format_status_badge($t['status']) ?></td>
                                        <td class="text-end">
                                            <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-info font-chakra" title="ไปหน้าจัดการทีม">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Management Shortcuts & System Status -->
        <div class="col-lg-4">
            
            <!-- System Status Card -->
            <div class="admin-table-wrapper mb-4">
                <h5 class="text-white fw-bold mb-3 font-chakra">
                    <i class="fa-solid fa-circle-nodes text-warning me-1"></i> สถานะระบบปัจจุบัน
                </h5>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-25">
                    <span class="text-secondary small">ปีการศึกษาที่เปิดใช้งาน:</span>
                    <span class="badge bg-info text-dark font-chakra fs-6">ปี <?= e($current_year) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-25">
                    <span class="text-secondary small">สถานะการรับสมัคร:</span>
                    <?php if ($reg_status === 'open'): ?>
                        <span class="badge bg-success font-chakra"><i class="fa-solid fa-circle-dot me-1"></i> เปิดรับสมัคร</span>
                    <?php else: ?>
                        <span class="badge bg-danger font-chakra"><i class="fa-solid fa-lock me-1"></i> ปิดรับสมัคร</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-secondary small">ฐานข้อมูล:</span>
                    <span class="font-orbitron text-warning small">pwks_esport</span>
                </div>
                <div class="mt-3 text-center">
                    <a href="settings.php" class="btn btn-sm btn-outline-warning w-100 font-chakra">
                        <i class="fa-solid fa-sliders me-1"></i> ไปหน้าตั้งค่าระบบ
                    </a>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="admin-table-wrapper">
                <h5 class="text-white fw-bold mb-3 font-chakra">
                    <i class="fa-solid fa-bolt text-warning me-1"></i> เมนูด่วน (Quick Actions)
                </h5>
                <div class="d-grid gap-2">
                    <a href="rules.php?year=<?= urlencode($selected_year) ?>&action=create" class="btn btn-sm btn-outline-light text-start font-chakra py-2">
                        <i class="fa-solid fa-plus text-warning me-2"></i> เพิ่มหัวข้อกติกาใหม่
                    </a>
                    <a href="brackets.php?year=<?= urlencode($selected_year) ?>&action=create" class="btn btn-sm btn-outline-light text-start font-chakra py-2">
                        <i class="fa-solid fa-plus text-warning me-2"></i> เพิ่มสายการแข่งขัน (Embed)
                    </a>
                    <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-light text-start font-chakra py-2">
                        <i class="fa-solid fa-filter text-warning me-2"></i> กรองและค้นหาทีม
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
