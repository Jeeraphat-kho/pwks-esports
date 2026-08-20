<?php
/**
 * FREE FIRE ESPORTS - Admin System Settings
 * Manages Multi-Academic-Year, Registration Status, and Admin Security
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$active_admin_page = 'settings';
$page_title = 'ตั้งค่าระบบการแข่งขัน | Free Fire Esports';

// Handle Settings Form Submissions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'CSRF Token ไม่ถูกต้องหรือหมดอายุ');
        header("Location: settings.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Update Academic Year Settings
    if ($action === 'update_academic_year') {
        $active_year = trim($_POST['active_academic_year'] ?? '');
        $new_year = trim($_POST['new_academic_year'] ?? '');

        $years_arr = json_decode(get_setting('available_academic_years', '["2569", "2570"]'), true) ?: ['2569'];

        if (!empty($new_year)) {
            if (!in_array($new_year, $years_arr)) {
                $years_arr[] = $new_year;
                rsort($years_arr);
                update_setting('available_academic_years', json_encode(array_values($years_arr)));
                $active_year = $new_year; // Switch to newly created year
            }
        }

        if (!empty($active_year)) {
            update_setting('current_academic_year', $active_year);
            set_flash('success', "เปลี่ยนปีการศึกษาที่ใช้งานเป็น ปี {$active_year} เรียบร้อยแล้ว ระบบหน้าบ้านจะแสดงผลเฉพาะข้อมูลของปีนี้");
        }
        header("Location: settings.php");
        exit;
    }

    // 2. Update General Competition Settings
    if ($action === 'update_competition_settings') {
        $comp_name = trim($_POST['competition_name'] ?? '');
        $school_name = trim($_POST['school_name'] ?? '');
        $reg_status = $_POST['registration_status'] ?? 'open';
        $start_date = $_POST['registration_start_date'] ?? '';
        $end_date = $_POST['registration_end_date'] ?? '';

        if (!empty($comp_name)) update_setting('competition_name', $comp_name);
        if (!empty($school_name)) update_setting('school_name', $school_name);
        update_setting('registration_status', $reg_status);
        if (!empty($start_date)) update_setting('registration_start_date', $start_date);
        if (!empty($end_date)) update_setting('registration_end_date', $end_date);

        set_flash('success', 'บันทึกการตั้งค่าการแข่งขันเรียบร้อยแล้ว');
        header("Location: settings.php");
        exit;
    }

    // 3. Change Admin Password
    if ($action === 'change_password') {
        $curr_pass = trim($_POST['current_password'] ?? '');
        $new_pass = trim($_POST['new_password'] ?? '');
        $conf_pass = trim($_POST['confirm_password'] ?? '');

        if (empty($curr_pass) || empty($new_pass) || empty($conf_pass)) {
            set_flash('danger', 'กรุณากรอกรหัสผ่านให้ครบทุกช่อง');
        } elseif ($new_pass !== $conf_pass) {
            set_flash('danger', 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
        } elseif (strlen($new_pass) < 6) {
            set_flash('danger', 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        } else {
            $stmt = $pdo->prepare("SELECT `password_hash` FROM `freefire_admins` WHERE `id` = ?");
            $stmt->execute([$_SESSION['ff_admin_id']]);
            $hash = $stmt->fetchColumn();

            if ($hash && password_verify($curr_pass, $hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt_up = $pdo->prepare("UPDATE `freefire_admins` SET `password_hash` = ?, `updated_at` = NOW() WHERE `id` = ?");
                $stmt_up->execute([$new_hash, $_SESSION['ff_admin_id']]);
                set_flash('success', 'เปลี่ยนรหัสผ่านผู้ดูแลระบบเรียบร้อยแล้ว');
            } else {
                set_flash('danger', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }
        }
        header("Location: settings.php");
        exit;
    }
}

// Current Values
$curr_academic_year = get_active_academic_year();
$all_years = get_available_academic_years();
$comp_name_val = get_setting('competition_name', 'FREE FIRE ESPORTS TOURNAMENT โรงเรียนพร้าววิทยาคม');
$school_name_val = get_setting('school_name', 'โรงเรียนพร้าววิทยาคม');
$reg_status_val = get_setting('registration_status', 'open');
$start_date_val = get_setting('registration_start_date', date('Y-m-d'));
$end_date_val = get_setting('registration_end_date', date('Y-m-d', strtotime('+30 days')));

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid px-lg-4 py-4">
    
    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1 font-orbitron">
                <i class="fa-solid fa-sliders text-warning me-2"></i> ตั้งค่าระบบการแข่งขัน
            </h2>
            <p class="text-secondary small mb-0">
                กำหนดปีการศึกษาที่ใช้งาน สถานะการรับสมัคร และความปลอดภัยของระบบ
            </p>
        </div>
    </div>

    <?= render_flash() ?>

    <div class="row g-4">
        
        <!-- Setting Box 1: Multi-Academic-Year Management (สำคัญมาก) -->
        <div class="col-lg-6">
            <div class="admin-table-wrapper h-100 border-warning border-opacity-50">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                    <div class="fs-4 text-warning"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-0 font-chakra">ระบบปีการศึกษา (Academic Year System)</h5>
                        <p class="text-secondary small mb-0">สลับปีการศึกษาที่ใช้งาน หรือเพิ่มปีการศึกษาใหม่</p>
                    </div>
                </div>

                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_academic_year">

                    <div class="mb-4 p-3 rounded-3 bg-dark border border-secondary border-opacity-25">
                        <label class="form-label text-warning fw-bold mb-1">
                            <i class="fa-solid fa-check-circle me-1"></i> ปีการศึกษาที่ใช้งานในปัจจุบัน (Active Year)
                        </label>
                        <select name="active_academic_year" class="form-select font-chakra fs-6 text-white bg-black border-warning">
                            <?php foreach ($all_years as $y): ?>
                                <option value="<?= e($y) ?>" <?= $y === $curr_academic_year ? 'selected' : '' ?>>
                                    ปีการศึกษา <?= e($y) ?> <?= $y === $curr_academic_year ? '★ (กำลังใช้งานอยู่)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-secondary small mt-2">
                            <i class="fa-solid fa-circle-info text-info me-1"></i> เมื่อเปลี่ยนปีการศึกษา หน้าเว็บผู้ใช้ (หน้าแรก, สมัคร, กติกา, สายแข่ง) จะสลับไปแสดงข้อมูลเฉพาะปีที่เลือก โดยข้อมูลปีอื่น ๆ ยังคงถูกจัดเก็บไว้อย่างปลอดภัยใน Database
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small">หรือเพิ่มปีการศึกษาใหม่เข้าสู่ระบบ (เช่น 2571)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary">พ.ศ.</span>
                            <input type="text" name="new_academic_year" class="form-control" placeholder="เช่น 2571" pattern="[0-9]{4}">
                        </div>
                        <div class="form-text text-muted small">ระบบจะเพิ่มปีใหม่เข้าสู่ตัวเลือก และสลับการทำงานไปยังปีใหม่ทันที</div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning font-chakra px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกปีการศึกษา
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Setting Box 2: Competition Info & Registration Control -->
        <div class="col-lg-6">
            <div class="admin-table-wrapper h-100">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                    <div class="fs-4 text-warning"><i class="fa-solid fa-trophy"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-0 font-chakra">ข้อมูลการแข่งขันและสถานะรับสมัคร</h5>
                        <p class="text-secondary small mb-0">ควบคุมการเปิด/ปิดรับสมัคร และข้อมูลชื่อการแข่งขัน</p>
                    </div>
                </div>

                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_competition_settings">

                    <div class="mb-3">
                        <label class="form-label text-secondary small">ชื่อการแข่งขัน</label>
                        <input type="text" name="competition_name" class="form-control" value="<?= e($comp_name_val) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small">ชื่อโรงเรียน / สถาบัน</label>
                        <input type="text" name="school_name" class="form-control" value="<?= e($school_name_val) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small">สถานะการรับสมัคร (Registration Status)</label>
                        <select name="registration_status" class="form-select">
                            <option value="open" <?= $reg_status_val === 'open' ? 'selected' : '' ?>>🟢 เปิดรับสมัคร (Registration Open)</option>
                            <option value="closed" <?= $reg_status_val === 'closed' ? 'selected' : '' ?>>🔴 ปิดรับสมัคร (Registration Closed)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label text-secondary small">วันที่เริ่มรับสมัคร</label>
                            <input type="date" name="registration_start_date" class="form-control form-control-sm" value="<?= e($start_date_val) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">วันที่สิ้นสุดการรับสมัคร</label>
                            <input type="date" name="registration_end_date" class="form-control form-control-sm" value="<?= e($end_date_val) ?>">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning font-chakra px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Setting Box 3: Admin Security / Change Password -->
        <div class="col-lg-6">
            <div class="admin-table-wrapper">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                    <div class="fs-4 text-warning"><i class="fa-solid fa-shield-keyhole"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-0 font-chakra">ความปลอดภัยและรหัสผ่าน Admin</h5>
                        <p class="text-secondary small mb-0">เปลี่ยนรหัสผ่านสำหรับเข้าสู่ระบบผู้ดูแลระบบ</p>
                    </div>
                </div>

                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label text-secondary small">รหัสผ่านปัจจุบัน (Current Password)</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="กรอกรหัสผ่านเดิม">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small">รหัสผ่านใหม่ (New Password)</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small">ยืนยันรหัสผ่านใหม่ (Confirm New Password)</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-danger font-chakra px-4">
                            <i class="fa-solid fa-key me-1"></i> เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Setting Box 4: Database & System Environment Info -->
        <div class="col-lg-6">
            <div class="admin-table-wrapper">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                    <div class="fs-4 text-info"><i class="fa-solid fa-server"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-0 font-chakra">ข้อมูลสภาพแวดล้อมระบบ (System Info)</h5>
                        <p class="text-secondary small mb-0">รายละเอียดเซิร์ฟเวอร์และฐานข้อมูล</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-sm mb-0 small">
                        <tbody>
                            <tr>
                                <td class="text-muted">PHP Version:</td>
                                <td class="text-warning font-orbitron fw-bold"><?= phpversion() ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Database Engine:</td>
                                <td class="text-white">MySQL / MariaDB (PDO Enabled)</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Database Name:</td>
                                <td class="text-info font-orbitron">pwks_esport</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Table Prefix:</td>
                                <td class="text-warning font-orbitron">freefire_*</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Multi-Year Support:</td>
                                <td class="text-success"><i class="fa-solid fa-circle-check"></i> Enabled (Active: <?= e($curr_academic_year) ?>)</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Uploads Path:</td>
                                <td class="text-secondary font-monospace">freefire/uploads/logos/</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
