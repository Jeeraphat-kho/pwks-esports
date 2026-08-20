<?php
/**
 * FREE FIRE ESPORTS - Admin Team Management
 * Features: View Details Popup Modal, Full Edit Team & Players Modal, SweetAlert2 Deletion
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$active_admin_page = 'teams';
$page_title = 'จัดการทีมการแข่งขัน | Free Fire Esports';
$current_year = get_active_academic_year();
$selected_year = $_GET['year'] ?? $current_year;

// Handle Actions (Update, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'CSRF Token ไม่ถูกต้องหรือหมดอายุ');
        header("Location: teams.php?year=" . urlencode($selected_year));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $team_id = (int)($_POST['id'] ?? 0);

    // 1. Full Edit Team & Players
    if ($action === 'edit_team_full' && $team_id > 0) {
        $team_name = trim($_POST['team_name'] ?? '');
        $teacher_advisor = trim($_POST['teacher_advisor'] ?? '');
        $level = in_array($_POST['level'] ?? '', ['junior', 'senior']) ? $_POST['level'] : 'senior';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        $delete_logo = isset($_POST['delete_logo']);

        if (!empty($team_name) && !empty($teacher_advisor)) {
            try {
                $pdo->beginTransaction();

                // Get existing logo before update
                $stmt_curr_logo = $pdo->prepare("SELECT `team_logo` FROM `freefire_teams` WHERE `id` = ?");
                $stmt_curr_logo->execute([$team_id]);
                $old_logo_path = $stmt_curr_logo->fetchColumn();

                // Handle Logo Upload
                $new_logo_path = null;
                if (!empty($_FILES['team_logo']['tmp_name'])) {
                    $new_logo_path = upload_team_logo($_FILES['team_logo']);
                }

                // Update team profile
                if ($new_logo_path) {
                    // Delete old logo file from disk
                    if (!empty($old_logo_path)) {
                        delete_team_logo($old_logo_path);
                    }

                    $stmt_up_team = $pdo->prepare("UPDATE `freefire_teams` SET 
                        `team_name` = ?, 
                        `teacher_advisor` = ?, 
                        `level` = ?, 
                        `admin_notes` = ?, 
                        `team_logo` = ?, 
                        `updated_at` = NOW() 
                        WHERE `id` = ?");
                    $stmt_up_team->execute([$team_name, $teacher_advisor, $level, $admin_notes, $new_logo_path, $team_id]);
                } elseif ($delete_logo) {
                    // Delete old logo file from disk
                    if (!empty($old_logo_path)) {
                        delete_team_logo($old_logo_path);
                    }

                    $stmt_up_team = $pdo->prepare("UPDATE `freefire_teams` SET 
                        `team_name` = ?, 
                        `teacher_advisor` = ?, 
                        `level` = ?, 
                        `admin_notes` = ?, 
                        `team_logo` = NULL, 
                        `updated_at` = NOW() 
                        WHERE `id` = ?");
                    $stmt_up_team->execute([$team_name, $teacher_advisor, $level, $admin_notes, $team_id]);
                } else {
                    $stmt_up_team = $pdo->prepare("UPDATE `freefire_teams` SET 
                        `team_name` = ?, 
                        `teacher_advisor` = ?, 
                        `level` = ?, 
                        `admin_notes` = ?, 
                        `updated_at` = NOW() 
                        WHERE `id` = ?");
                    $stmt_up_team->execute([$team_name, $teacher_advisor, $level, $admin_notes, $team_id]);
                }

                // Update players if submitted
                if (!empty($_POST['players']) && is_array($_POST['players'])) {
                    $stmt_up_player = $pdo->prepare("UPDATE `freefire_players` SET 
                        `title` = ?, 
                        `first_name` = ?, 
                        `last_name` = ?, 
                        `ign` = ?, 
                        `class_level` = ?, 
                        `room` = ?, 
                        `student_no` = ?, 
                        `email` = ?, 
                        `phone` = ? 
                        WHERE `id` = ? AND `team_id` = ?");

                    foreach ($_POST['players'] as $pid => $pdata) {
                        $p_id = (int)$pid;
                        $stmt_up_player->execute([
                            trim($pdata['title'] ?? ''),
                            trim($pdata['first_name'] ?? ''),
                            trim($pdata['last_name'] ?? ''),
                            trim($pdata['ign'] ?? ''),
                            trim($pdata['class_level'] ?? ''),
                            trim($pdata['room'] ?? ''),
                            (int)($pdata['student_no'] ?? 0),
                            trim($pdata['email'] ?? ''),
                            trim($pdata['phone'] ?? ''),
                            $p_id,
                            $team_id
                        ]);
                    }
                }

                $pdo->commit();
                set_flash('success', "บันทึกการแก้ไขข้อมูลทีม \"{$team_name}\" เรียบร้อยแล้ว");
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
            }
        } else {
            set_flash('danger', 'กรุณากรอกชื่อทีมและชื่อครูที่ปรึกษาให้ครบถ้วน');
        }
        header("Location: teams.php?year=" . urlencode($selected_year));
        exit;
    }

    // 2. Delete Team
    if ($action === 'delete' && $team_id > 0) {
        // Delete team logo file from storage if exists
        $stmt_logo = $pdo->prepare("SELECT `team_logo` FROM `freefire_teams` WHERE `id` = ?");
        $stmt_logo->execute([$team_id]);
        $logo_file = $stmt_logo->fetchColumn();
        if (!empty($logo_file)) {
            delete_team_logo($logo_file);
        }

        // Delete record (Cascade will delete players automatically)
        $stmt_del = $pdo->prepare("DELETE FROM `freefire_teams` WHERE `id` = ?");
        $stmt_del->execute([$team_id]);
        set_flash('success', 'ลบข้อมูลทีมและสมาชิกทั้งหมดเรียบร้อยแล้ว');
        header("Location: teams.php?year=" . urlencode($selected_year));
        exit;
    }
}

// Filter parameters
$filter_level = $_GET['level'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

// Build query
$where_clauses = ["t.`academic_year` = ?"];
$params = [$selected_year];

if ($filter_level !== 'all') {
    $where_clauses[] = "t.`level` = ?";
    $params[] = $filter_level;
}

if (!empty($search_query)) {
    $where_clauses[] = "(t.`team_name` LIKE ? OR t.`teacher_advisor` LIKE ? OR EXISTS (
        SELECT 1 FROM `freefire_players` p 
        WHERE p.`team_id` = t.`id` 
        AND (p.`first_name` LIKE ? OR p.`last_name` LIKE ? OR p.`ign` LIKE ?)
    ))";
    $search_like = "%{$search_query}%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$where_sql = implode(' AND ', $where_clauses);
$stmt_list = $pdo->prepare("SELECT t.*, 
    (SELECT COUNT(*) FROM `freefire_players` p WHERE p.`team_id` = t.`id`) as player_count
    FROM `freefire_teams` t 
    WHERE {$where_sql} 
    ORDER BY t.`created_at` DESC");
$stmt_list->execute($params);
$teams = $stmt_list->fetchAll();

// Pre-load all players for modal datasets
$teams_data = [];
foreach ($teams as $t) {
    $stmt_p = $pdo->prepare("SELECT * FROM `freefire_players` WHERE `team_id` = ? ORDER BY `player_order` ASC");
    $stmt_p->execute([$t['id']]);
    $players = $stmt_p->fetchAll();
    
    $teams_data[$t['id']] = [
        'team' => $t,
        'players' => $players,
        'level_text' => format_level($t['level'])
    ];
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid px-lg-4 py-4">
    
    <!-- Page Title -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1 font-orbitron">
                <i class="fa-solid fa-users text-warning me-2"></i> จัดการทีมการแข่งขัน
            </h2>
            <p class="text-secondary small mb-0">
                รายชื่อทีมที่สมัครเข้าร่วมการแข่งขัน Free Fire Esports ประจำปีการศึกษา <strong class="text-warning">ปี <?= e($selected_year) ?></strong> (ทั้งหมด <?= count($teams) ?> ทีม)
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="export_teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-success font-chakra fw-bold shadow-sm d-flex align-items-center" target="_blank" title="ส่งออกไฟล์ Excel / ใบลงคะแนนการแข่งขัน">
                <i class="fa-solid fa-file-excel me-1 text-white"></i> ส่งออก Excel / ใบบันทึกคะแนน
            </a>
            <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-secondary font-chakra d-flex align-items-center">
                <i class="fa-solid fa-rotate me-1"></i> รีเฟรช
            </a>
        </div>
    </div>

    <?= render_flash() ?>

    <!-- Search & Filter Controls -->
    <div class="admin-table-wrapper mb-4 p-3">
        <form method="GET" action="teams.php" class="row g-3 align-items-center">
            <input type="hidden" name="year" value="<?= e($selected_year) ?>">
            
            <!-- 1. Search Input -->
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary px-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อทีม, ครูที่ปรึกษา, ผู้เล่น, IGN..." value="<?= e($search_query) ?>" style="height: 42px;">
                </div>
            </div>

            <!-- 2. Filter Level -->
            <div class="col-12 col-md-4">
                <select name="level" class="form-select" onchange="this.form.submit()" style="height: 42px;">
                    <option value="all" <?= $filter_level === 'all' ? 'selected' : '' ?>>-- ทุกระดับชั้น (ม.ต้น / ม.ปลาย / ปวช.) --</option>
                    <option value="junior" <?= $filter_level === 'junior' ? 'selected' : '' ?>>มัธยมศึกษาตอนต้น (ม.1 - ม.3)</option>
                    <option value="senior" <?= $filter_level === 'senior' ? 'selected' : '' ?>>มัธยมศึกษาตอนปลาย / ปวช. (ม.4 - ม.6 / ปวช.1 - ปวช.3)</option>
                </select>
            </div>

            <!-- 3. Submit Filter & Reset Buttons -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-warning flex-grow-1 font-chakra fw-bold d-flex align-items-center justify-content-center" style="height: 42px;">
                    <i class="fa-solid fa-filter me-2"></i> ค้นหา
                </button>
                <?php if (!empty($search_query) || $filter_level !== 'all'): ?>
                    <a href="teams.php?year=<?= urlencode($selected_year) ?>" class="btn btn-outline-secondary d-flex align-items-center justify-content-center px-3 font-chakra" style="height: 42px;" title="ล้างตัวกรอง">
                        <i class="fa-solid fa-xmark me-1"></i> ล้างค่า
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Teams Data Table -->
    <div class="admin-table-wrapper">
        <?php if (empty($teams)): ?>
            <div class="empty-state-box">
                <div class="empty-icon">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h5 class="text-white fs-5 fw-bold font-chakra">ไม่พบข้อมูลทีมตามเงื่อนไขที่ระบุ</h5>
                <p class="text-light small mb-0">ลองปรับเปลี่ยนตัวกรอง หรือค้นหาใหม่อีกครั้ง</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-esports align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">โลโก้</th>
                            <th>ชื่อทีม</th>
                            <th>ระดับชั้น</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>ผู้เล่น 5 คน</th>
                            <th>วันที่ลงทะเบียน</th>
                            <th class="text-end" style="min-width: 170px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $t): 
                            $team_info = $teams_data[$t['id']] ?? null;
                            $team_players = $team_info['players'] ?? [];
                        ?>
                            <tr>
                                <td>
                                    <?php if (!empty($t['team_logo'])): ?>
                                        <img src="../<?= e($t['team_logo']) ?>" alt="Logo" class="rounded-2" style="width: 42px; height: 42px; object-fit: cover; border: 1px solid rgba(255,85,0,0.4);">
                                    <?php else: ?>
                                        <div class="rounded-2 bg-dark border border-secondary d-flex align-items-center justify-content-center text-muted" style="width: 42px; height: 42px; font-size: 18px;">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-white fs-6"><?= e($t['team_name']) ?></div>
                                    <span class="badge bg-success bg-opacity-50 text-success border border-success border-opacity-50 font-chakra" style="font-size: 11px;">
                                        <i class="fa-solid fa-circle-check me-1"></i> ลงทะเบียนแล้ว
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $t['level'] === 'junior' ? 'bg-info text-dark' : 'bg-warning text-dark' ?>">
                                        <?= $t['level'] === 'junior' ? 'ม.ต้น' : 'ม.ปลาย / ปวช.' ?>
                                    </span>
                                </td>
                                <td class="text-secondary small"><?= e($t['teacher_advisor']) ?></td>
                                <td>
                                    <div style="max-width: 290px;">
                                        <?php foreach ($team_players as $tp): ?>
                                            <span class="player-tag-pill <?= $tp['player_type'] === 'substitute' ? 'text-secondary' : 'text-light' ?>" title="IGN: <?= e($tp['ign']) ?>">
                                                <?= e($tp['title'] .''.$tp['first_name'] . ' ' . $tp['last_name']) ?>
                                            </span><br>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-secondary small">
                                    <?= date('d/m/Y', strtotime($t['created_at'])) ?><br>
                                    <?= date('H:i', strtotime($t['created_at'])) ?> น.
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- 1. View Details Popup Button -->
                                        <button type="button" class="btn btn-outline-info font-chakra px-2 btn-open-view" data-team-id="<?= $t['id'] ?>" title="ดูข้อมูลสมาชิกในทีม">
                                            <i class="fa-solid fa-eye me-1"></i> ดูข้อมูล
                                        </button>

                                        <!-- 2. Edit Team Details Button -->
                                        <button type="button" class="btn btn-outline-warning font-chakra px-2 btn-open-edit" data-team-id="<?= $t['id'] ?>" title="แก้ไขรายละเอียดทีม">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> แก้ไข
                                        </button>

                                        <!-- 3. Delete Team Button -->
                                        <button type="button" class="btn btn-outline-danger px-2 btn-delete-team" 
                                            data-id="<?= $t['id'] ?>" 
                                            data-name="ทีม <?= e($t['team_name']) ?>"
                                            title="ลบทีมนี้">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- =========================================================================
     MODAL 1: VIEW TEAM DETAILS POPUP
     ========================================================================= -->
<div class="modal fade" id="teamViewModal" tabindex="-1" aria-labelledby="teamViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content esports-modal">
            <div class="modal-header esports-modal-header">
                <h5 class="modal-title font-chakra fw-bold text-warning d-flex align-items-center gap-2" id="teamViewModalLabel">
                    <i class="fa-solid fa-shield"></i> รายละเอียดทีมการแข่งขัน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Team Summary Header Card -->
                <div class="d-flex flex-wrap align-items-center gap-3 p-3 rounded-3 bg-dark border border-secondary border-opacity-25 mb-4">
                    <div id="viewLogoWrapper" style="width: 70px; height: 70px; min-width: 70px;">
                        <img id="viewTeamLogo" src="" alt="Logo" class="rounded-3" style="width: 70px; height: 70px; object-fit: cover; border: 2px solid #ff5500; display: none; cursor: pointer; transition: transform 0.2s;" title="คลิกเพื่อดูรูปภาพขนาดใหญ่" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                        <div id="viewLogoPlaceholder" class="rounded-3 bg-dark border border-secondary align-items-center justify-content-center text-muted" style="width: 70px; height: 70px; font-size: 32px; display: none;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="text-white fw-bold mb-0" id="viewTeamName">—</h3>
                            <span class="badge bg-success bg-opacity-75 font-chakra"><i class="fa-solid fa-circle-check me-1"></i> ลงทะเบียนแล้ว</span>
                        </div>
                        <div class="text-secondary small">
                            <span class="text-info fw-bold font-chakra" id="viewTeamLevel">—</span> • 
                            ปีการศึกษา <span class="text-warning font-orbitron" id="viewAcademicYear">—</span>
                        </div>
                        <div class="text-muted small mt-1">
                            <i class="fa-solid fa-chalkboard-user text-warning me-1"></i> ครูที่ปรึกษา: <strong class="text-white" id="viewAdvisor">—</strong>
                        </div>
                    </div>
                </div>

                <!-- Players Table -->
                <h5 class="text-white fw-bold mb-3 font-chakra d-flex align-items-center gap-2">
                    <i class="fa-solid fa-users text-warning"></i> <span id="viewPlayersHeaderTitle">รายชื่อสมาชิกในทีม</span>
                </h5>

                <div class="table-responsive mb-4">
                    <table class="table table-dark table-bordered table-sm align-middle font-kanit">
                        <thead class="table-active text-center small font-chakra">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>บทบาท</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th class="text-warning">ชื่อในเกม (IGN)</th>
                                <th>ชั้น/ห้อง</th>
                                <th>เลขที่</th>
                                <th>เบอร์โทร</th>
                                <th>อีเมล</th>
                            </tr>
                        </thead>
                        <tbody id="viewPlayersTableBody">
                            <!-- Injected by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Admin Notes Display -->
                <div id="viewNotesBox" class="p-3 rounded-3 bg-dark border border-secondary border-opacity-25" style="display: none;">
                    <div class="text-secondary small mb-1 fw-bold"><i class="fa-solid fa-note-sticky text-warning me-1"></i> บันทึกเพิ่มเติม:</div>
                    <div id="viewNotesText" class="text-white small"></div>
                </div>

            </div>
            <div class="modal-footer esports-modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-warning font-chakra" id="viewBtnSwitchToEdit">
                    <i class="fa-solid fa-pen-to-square me-1"></i> แก้ไขข้อมูลทีมนี้
                </button>
                <button type="button" class="btn btn-sm btn-secondary font-chakra px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: EDIT TEAM & PLAYERS POPUP
     ========================================================================= -->
<div class="modal fade" id="teamEditModal" tabindex="-1" aria-labelledby="teamEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content esports-modal">
            <div class="modal-header esports-modal-header border-warning border-opacity-50">
                <h5 class="modal-title font-chakra fw-bold text-warning d-flex align-items-center gap-2" id="teamEditModalLabel">
                    <i class="fa-solid fa-pen-to-square"></i> แก้ไขรายละเอียดทีมและสมาชิก
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="teams.php?year=<?= urlencode($selected_year) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit_team_full">
                <input type="hidden" name="id" id="editTeamId" value="">

                <div class="modal-body p-4">
                    
                    <!-- Team Basic Info -->
                    <h6 class="text-warning fw-bold font-chakra mb-3"><i class="fa-solid fa-shield me-1"></i> ข้อมูลทีม</h6>
                    <div class="row g-3 mb-4 p-3 rounded-3 bg-dark border border-secondary border-opacity-25">
                        <div class="col-md-5">
                            <label class="form-label text-secondary small">ชื่อทีม <span class="text-danger">*</span></label>
                            <input type="text" name="team_name" id="editTeamName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small">ครูที่ปรึกษาทีม <span class="text-danger">*</span></label>
                            <input type="text" name="teacher_advisor" id="editTeacherAdvisor" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-secondary small">ระดับการแข่งขัน <span class="text-danger">*</span></label>
                            <select name="level" id="editLevel" class="form-select form-select-sm">
                                <option value="senior">มัธยมศึกษาตอนปลาย / ปวช. (ม.4 - ม.6 / ปวช.1 - ปวช.3)</option>
                                <option value="junior">มัธยมศึกษาตอนต้น (ม.1 - ม.3)</option>
                            </select>
                        </div>

                        <!-- Team Logo Edit & Preview -->
                        <div class="col-12">
                            <label class="form-label text-secondary small">โลโก้ทีม (เปลี่ยนรูปใหม่ / เว้นว่างหากใช้รูปเดิม)</label>
                            <div class="d-flex align-items-center gap-3 p-2 rounded-2 bg-black bg-opacity-50 border border-secondary border-opacity-25">
                                <div class="rounded-3 border border-secondary border-opacity-50 p-1 bg-black d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                                    <img id="editLogoPreview" src="" alt="Team Logo" class="img-fluid rounded-2" style="max-height: 44px; display: none;">
                                    <i id="editLogoPlaceholder" class="fa-solid fa-shield-halved text-secondary fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="team_logo" id="editTeamLogo" class="form-control form-control-sm" accept="image/png, image/jpeg, image/webp, image/gif">
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="text-muted" style="font-size: 11px;">รองรับไฟล์ภาพ PNG, JPG, WEBP ขนาดไม่เกิน 5MB</span>
                                        <div class="form-check form-check-inline m-0" id="editDeleteLogoContainer" style="display: none;">
                                            <input class="form-check-input" type="checkbox" name="delete_logo" value="1" id="editDeleteLogo">
                                            <label class="form-check-label text-danger small" for="editDeleteLogo" style="font-size: 11px;">ลบโลโก้เดิมออก</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary small">บันทึกเพิ่มเติมของ Admin</label>
                            <input type="text" name="admin_notes" id="editAdminNotes" class="form-control form-control-sm" placeholder="หมายเหตุเพิ่มเติม">
                        </div>
                    </div>

                    <!-- Players Edit Form -->
                    <h6 class="text-warning fw-bold font-chakra mb-3"><i class="fa-solid fa-users me-1"></i> ข้อมูลสมาชิกในทีม (รวมชื่อในเกม IGN)</h6>
                    <div id="editPlayersContainer" class="d-flex flex-column gap-3">
                        <!-- Injected by JavaScript -->
                    </div>

                </div>

                <div class="modal-footer esports-modal-footer justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-secondary font-chakra px-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-sm btn-warning font-chakra px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกการแก้ไขทั้งหมด
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form for SweetAlert2 Trigger -->
<form id="swalDeleteForm" method="POST" action="teams.php?year=<?= urlencode($selected_year) ?>" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="swalDeleteTeamId" value="">
</form>

<!-- Pass All Teams Data to JavaScript for Instant Smooth Popups -->
<script>
    const TEAMS_DATA = <?= json_encode($teams_data, JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        const viewModalEl = document.getElementById('teamViewModal');
        const editModalEl = document.getElementById('teamEditModal');
        if (viewModalEl && viewModalEl.parentNode !== document.body) {
            document.body.appendChild(viewModalEl);
        }
        if (editModalEl && editModalEl.parentNode !== document.body) {
            document.body.appendChild(editModalEl);
        }
        const viewModal = viewModalEl ? new bootstrap.Modal(viewModalEl) : null;
        const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

        let currentActiveTeamId = null;

        // Team Logo preview change listener in Edit Modal
        const editTeamLogoInput = document.getElementById('editTeamLogo');
        if (editTeamLogoInput) {
            editTeamLogoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                const editLogoPreview = document.getElementById('editLogoPreview');
                const editLogoPlaceholder = document.getElementById('editLogoPlaceholder');
                const editDeleteLogo = document.getElementById('editDeleteLogo');
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (re) => {
                        if (editLogoPreview) {
                            editLogoPreview.src = re.target.result;
                            editLogoPreview.style.display = 'block';
                        }
                        if (editLogoPlaceholder) editLogoPlaceholder.style.display = 'none';
                        if (editDeleteLogo) editDeleteLogo.checked = false;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Team Logo Lightbox click listener in View Modal (Fancybox 5)
        const viewTeamLogo = document.getElementById('viewTeamLogo');
        if (viewTeamLogo) {
            viewTeamLogo.addEventListener('click', () => {
                const src = viewTeamLogo.getAttribute('src');
                const teamName = document.getElementById('viewTeamName')?.textContent || 'โลโก้ทีม';
                if (src && typeof Fancybox !== 'undefined') {
                    Fancybox.show([
                        {
                            src: src,
                            type: 'image',
                            caption: `<span class="fw-bold font-chakra text-warning fs-5"><i class="fa-solid fa-shield-halved me-1"></i> ${teamName}</span>`
                        }
                    ], {
                        parentEl: document.body,
                        dragToClose: true,
                        Toolbar: {
                            display: {
                                left: ["infobar"],
                                middle: ["zoomIn", "zoomOut", "toggle1to1"],
                                right: ["fullscreen", "close"]
                            }
                        }
                    });
                }
            });
        }

        // 1. Handle "ดูข้อมูล" (View Popup)
        document.querySelectorAll('.btn-open-view').forEach(btn => {
            btn.addEventListener('click', () => {
                const teamId = btn.getAttribute('data-team-id');
                currentActiveTeamId = teamId;
                showTeamViewModal(teamId);
            });
        });

        function showTeamViewModal(teamId) {
            const data = TEAMS_DATA[teamId];
            if (!data || !viewModal) return;

            const t = data.team;
            const players = data.players;

            // Header info
            document.getElementById('viewTeamName').textContent = t.team_name;
            document.getElementById('viewTeamLevel').textContent = data.level_text;
            document.getElementById('viewAcademicYear').textContent = t.academic_year;
            document.getElementById('viewAdvisor').textContent = t.teacher_advisor;

            // Logo
            const logoImg = document.getElementById('viewTeamLogo');
            const logoPlaceholder = document.getElementById('viewLogoPlaceholder');
            if (t.team_logo) {
                logoImg.src = '../' + t.team_logo;
                logoImg.style.display = 'block';
                logoPlaceholder.style.display = 'none';
            } else {
                logoImg.src = '';
                logoImg.style.display = 'none';
                logoPlaceholder.style.display = 'flex';
            }

            // Players title
            const playersHeaderTitle = document.getElementById('viewPlayersHeaderTitle');
            if (playersHeaderTitle) {
                playersHeaderTitle.textContent = `รายชื่อสมาชิกในทีม (${players.length} คน)`;
            }

            // Notes
            const notesBox = document.getElementById('viewNotesBox');
            const notesText = document.getElementById('viewNotesText');
            if (t.admin_notes && t.admin_notes.trim().length > 0) {
                notesText.textContent = t.admin_notes;
                notesBox.style.display = 'block';
            } else {
                notesBox.style.display = 'none';
            }

            // Players Table
            const tbody = document.getElementById('viewPlayersTableBody');
            tbody.innerHTML = '';

            players.forEach(p => {
                const isSub = (p.player_type === 'substitute' || p.player_order == 5);
                const isCap = (p.player_order == 1);
                
                let roleBadge = '<span class="badge bg-dark border border-secondary font-chakra">ตัวจริง</span>';
                if (isSub) roleBadge = '<span class="badge bg-secondary font-chakra">สำรอง</span>';
                if (isCap) roleBadge = '<span class="badge bg-warning text-dark font-chakra"><i class="fa-solid fa-crown me-1"></i> กัปตัน</span>';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center text-muted">${p.player_order}</td>
                    <td class="text-center">${roleBadge}</td>
                    <td class="fw-bold text-white">${p.title} ${p.first_name} ${p.last_name}</td>
                    <td class="fw-bold text-warning font-chakra">${p.ign}</td>
                    <td class="text-center">${p.class_level === 'ปวช.' ? `ปวช. ปี ${p.room}` : `${p.class_level}/${p.room}`}</td>
                    <td class="text-center">${p.student_no || '—'}</td>
                    <td><a href="tel:${p.phone}" class="text-info text-decoration-none">${p.phone || '—'}</a></td>
                    <td class="text-secondary small">${p.email || '—'}</td>
                `;
                tbody.appendChild(tr);
            });

            viewModal.show();
        }

        // Switch from View Modal to Edit Modal
        const viewBtnSwitch = document.getElementById('viewBtnSwitchToEdit');
        if (viewBtnSwitch) {
            viewBtnSwitch.addEventListener('click', () => {
                if (viewModal) viewModal.hide();
                if (currentActiveTeamId) {
                    setTimeout(() => showTeamEditModal(currentActiveTeamId), 300);
                }
            });
        }

        // 2. Handle "แก้ไข" (Edit Popup)
        document.querySelectorAll('.btn-open-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const teamId = btn.getAttribute('data-team-id');
                currentActiveTeamId = teamId;
                showTeamEditModal(teamId);
            });
        });

        function showTeamEditModal(teamId) {
            const data = TEAMS_DATA[teamId];
            if (!data || !editModal) return;

            const t = data.team;
            const players = data.players;

            document.getElementById('editTeamId').value = t.id;
            document.getElementById('editTeamName').value = t.team_name;
            document.getElementById('editTeacherAdvisor').value = t.teacher_advisor;
            document.getElementById('editLevel').value = t.level;
            document.getElementById('editAdminNotes').value = t.admin_notes || '';

            // Logo population
            const editLogoPreview = document.getElementById('editLogoPreview');
            const editLogoPlaceholder = document.getElementById('editLogoPlaceholder');
            const editTeamLogoInput = document.getElementById('editTeamLogo');
            const editDeleteLogoContainer = document.getElementById('editDeleteLogoContainer');
            const editDeleteLogo = document.getElementById('editDeleteLogo');
            
            if (editTeamLogoInput) editTeamLogoInput.value = '';
            if (editDeleteLogo) editDeleteLogo.checked = false;

            if (t.team_logo) {
                editLogoPreview.src = '../' + t.team_logo;
                editLogoPreview.style.display = 'block';
                editLogoPlaceholder.style.display = 'none';
                if (editDeleteLogoContainer) editDeleteLogoContainer.style.display = 'inline-block';
            } else {
                editLogoPreview.src = '';
                editLogoPreview.style.display = 'none';
                editLogoPlaceholder.style.display = 'block';
                if (editDeleteLogoContainer) editDeleteLogoContainer.style.display = 'none';
            }

            // Render players edit fields
            const container = document.getElementById('editPlayersContainer');
            container.innerHTML = '';

            players.forEach(p => {
                const isSub = (p.player_type === 'substitute' || p.player_order == 5);
                const isCap = (p.player_order == 1);
                const roleTitle = isSub ? 'ผู้เล่นสำรอง' : (isCap ? 'ผู้เล่นคนที่ 1 (กัปตันทีม)' : `ผู้เล่นคนที่ ${p.player_order}`);

                const pCard = document.createElement('div');
                pCard.className = 'p-3 rounded-3 bg-dark border border-secondary border-opacity-25';
                pCard.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-secondary border-opacity-25">
                        <span class="text-white fw-bold font-chakra small">${roleTitle}</span>
                        <span class="badge ${isSub ? 'bg-secondary' : 'bg-warning text-dark'} font-chakra">${isSub ? 'สำรอง' : (isCap ? 'กัปตัน' : 'ตัวจริง')}</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-2 col-4">
                            <label class="form-label text-muted small mb-1">คำนำหน้า</label>
                            <input type="text" name="players[${p.id}][title]" value="${p.title}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3 col-8">
                            <label class="form-label text-muted small mb-1">ชื่อจริง</label>
                            <input type="text" name="players[${p.id}][first_name]" value="${p.first_name}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label text-muted small mb-1">นามสกุล</label>
                            <input type="text" name="players[${p.id}][last_name]" value="${p.last_name}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4 col-6">
                            <label class="form-label text-warning small mb-1"><i class="fa-solid fa-gamepad me-1"></i> ชื่อในเกม (IGN)</label>
                            <input type="text" name="players[${p.id}][ign]" value="${p.ign}" class="form-control form-control-sm fw-bold text-warning bg-black border-warning" required>
                        </div>
                        <div class="col-md-2 col-4">
                            <label class="form-label text-muted small mb-1">ชั้น</label>
                            <input type="text" name="players[${p.id}][class_level]" value="${p.class_level}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2 col-4">
                            <label class="form-label text-muted small mb-1">ห้อง</label>
                            <input type="text" name="players[${p.id}][room]" value="${p.room}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2 col-4">
                            <label class="form-label text-muted small mb-1">เลขที่</label>
                            <input type="number" name="players[${p.id}][student_no]" value="${p.student_no}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label text-muted small mb-1">เบอร์โทร</label>
                            <input type="text" name="players[${p.id}][phone]" value="${p.phone}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label text-muted small mb-1">อีเมล</label>
                            <input type="email" name="players[${p.id}][email]" value="${p.email}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                `;
                container.appendChild(pCard);
            });

            editModal.show();
        }

        // 3. Handle Delete with SweetAlert2
        document.querySelectorAll('.btn-delete-team').forEach(btn => {
            btn.addEventListener('click', () => {
                const teamId = btn.getAttribute('data-id');
                const teamName = btn.getAttribute('data-name');
                const form = document.getElementById('swalDeleteForm');
                const idInput = document.getElementById('swalDeleteTeamId');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'ยืนยันการลบทีม?',
                        html: `<div class="text-start p-2" style="font-size: 15px; color: #cbd5e1;">
                                 <p class="mb-1">คุณต้องการลบ <strong>${teamName}</strong> ใช่หรือไม่?</p>
                                 <p class="text-danger small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i> ข้อมูลทีมและสมาชิกทั้ง 5 คนจะถูกลบถาวร ไม่สามารถกู้คืนได้</p>
                               </div>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> ยืนยันลบ',
                        cancelButtonText: 'ยกเลิก',
                        background: '#0a0f24',
                        color: '#ffffff',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#334155',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (idInput && form) {
                                idInput.value = teamId;
                                form.submit();
                            }
                        }
                    });
                } else {
                    if (confirm(`ยืนยันการลบ ${teamName}? ข้อมูลสมาชิกจะถูกลบทั้งหมด`)) {
                        if (idInput && form) {
                            idInput.value = teamId;
                            form.submit();
                        }
                    }
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
