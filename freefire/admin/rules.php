<?php
/**
 * FREE FIRE ESPORTS - Admin Rules Management (WYSIWYG Editor)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$active_admin_page = 'rules';
$page_title = 'จัดการกติกาการแข่งขัน | Free Fire Esports';
$current_year = get_active_academic_year();
$selected_year = $_GET['year'] ?? $current_year;

// Handle Actions (Create, Update, Toggle, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'CSRF Token ไม่ถูกต้องหรือหมดอายุ');
        header("Location: rules.php?year=" . urlencode($selected_year));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $rule_id = (int)($_POST['id'] ?? 0);

    if ($action === 'save_rule') {
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $academic_year = trim($_POST['academic_year'] ?? $selected_year);

        if (empty($title)) {
            set_flash('danger', 'กรุณากรอกชื่อหัวข้อกติกา');
        } else {
            $clean_content = sanitize_html($content);

            if ($rule_id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE `freefire_rules` SET 
                    `title` = ?, 
                    `content` = ?, 
                    `sort_order` = ?, 
                    `is_active` = ?, 
                    `academic_year` = ?, 
                    `updated_at` = NOW() 
                    WHERE `id` = ?");
                $stmt->execute([$title, $clean_content, $sort_order, $is_active, $academic_year, $rule_id]);
                set_flash('success', 'บันทึกการแก้ไขกติกาเรียบร้อยแล้ว');
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO `freefire_rules` 
                    (`academic_year`, `title`, `content`, `sort_order`, `is_active`) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$academic_year, $title, $clean_content, $sort_order, $is_active]);
                set_flash('success', 'เพิ่มหัวข้อกติกาใหม่เรียบร้อยแล้ว');
            }
        }
        header("Location: rules.php?year=" . urlencode($academic_year));
        exit;
    }

    if ($action === 'toggle_active') {
        if ($rule_id > 0) {
            $stmt = $pdo->prepare("UPDATE `freefire_rules` SET `is_active` = NOT `is_active`, `updated_at` = NOW() WHERE `id` = ?");
            $stmt->execute([$rule_id]);
            set_flash('success', 'เปลี่ยนสถานะการแสดงผลกติกาเรียบร้อยแล้ว');
        }
        header("Location: rules.php?year=" . urlencode($selected_year));
        exit;
    }

    if ($action === 'delete') {
        if ($rule_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM `freefire_rules` WHERE `id` = ?");
            $stmt->execute([$rule_id]);
            set_flash('success', 'ลบหัวข้อกติกาเรียบร้อยแล้ว');
        }
        header("Location: rules.php?year=" . urlencode($selected_year));
        exit;
    }
}

// Fetch all rules for selected academic year
$stmt_rules = $pdo->prepare("SELECT * FROM `freefire_rules` WHERE `academic_year` = ? ORDER BY `sort_order` ASC, `id` ASC");
$stmt_rules->execute([$selected_year]);
$rules = $stmt_rules->fetchAll();

// Edit mode check
$edit_rule = null;
if (!empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_e = $pdo->prepare("SELECT * FROM `freefire_rules` WHERE `id` = ?");
    $stmt_e->execute([$edit_id]);
    $edit_rule = $stmt_e->fetch();
}
$is_creating = isset($_GET['action']) && $_GET['action'] === 'create';

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid px-lg-4 py-4">
    
    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1 font-orbitron">
                <i class="fa-solid fa-scroll text-warning me-2"></i> จัดการกติกาการแข่งขัน
            </h2>
            <p class="text-secondary small mb-0">
                กำหนดและแก้ไขระเบียบการแข่งขัน Free Fire ด้วย WYSIWYG Editor ประจำปีการศึกษา <strong class="text-warning">ปี <?= e($selected_year) ?></strong>
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="rules.php?year=<?= urlencode($selected_year) ?>&action=create" class="btn btn-sm btn-warning font-chakra">
                <i class="fa-solid fa-plus me-1"></i> เพิ่มหัวข้อกติกาใหม่
            </a>
        </div>
    </div>

    <?= render_flash() ?>

    <!-- Add / Edit Rule Form (WYSIWYG) -->
    <?php if ($edit_rule || $is_creating): ?>
        <div class="admin-table-wrapper mb-4 border-warning">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                <h5 class="text-white fw-bold mb-0 font-chakra text-warning">
                    <i class="fa-solid fa-pen-to-square me-2"></i> <?= $edit_rule ? 'แก้ไขหัวข้อกติกา' : 'เพิ่มหัวข้อกติกาใหม่' ?> (ปีการศึกษา <?= e($selected_year) ?>)
                </h5>
                <a href="rules.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-secondary font-chakra">
                    <i class="fa-solid fa-xmark me-1"></i> ปิดฟอร์ม
                </a>
            </div>

            <form method="POST" action="rules.php?year=<?= urlencode($selected_year) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_rule">
                <input type="hidden" name="id" value="<?= $edit_rule['id'] ?? '' ?>">
                <input type="hidden" name="academic_year" value="<?= e($selected_year) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label text-secondary small">ชื่อหัวข้อกติกา <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="เช่น คุณสมบัติผู้เข้าแข่งขัน, รูปแบบการแข่งขันและการคิดคะแนน" value="<?= e($edit_rule['title'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label text-secondary small">ลำดับการแสดงผล (Sort Order)</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e($edit_rule['sort_order'] ?? (count($rules) + 1)) ?>" min="0">
                    </div>
                    <div class="col-md-2 col-6 d-flex align-items-end pb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" value="1" <?= (!isset($edit_rule) || !empty($edit_rule['is_active'])) ? 'checked' : '' ?> style="accent-color: #ff5500;">
                            <label class="form-check-label text-white small" for="isActiveCheck">
                                เปิดการแสดงผล
                            </label>
                        </div>
                    </div>
                </div>

                <!-- WYSIWYG Content Area (Summernote) -->
                <div class="mb-4">
                    <label class="form-label text-secondary small">เนื้อหากติกา (WYSIWYG Editor) <span class="text-danger">*</span></label>
                    <textarea name="content" class="wysiwyg-editor"><?= htmlspecialchars($edit_rule['content'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="rules.php?year=<?= urlencode($selected_year) ?>" class="btn btn-secondary font-chakra px-4">ยกเลิก</a>
                    <button type="submit" class="btn btn-warning font-chakra px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูลกติกา
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Rules List Table -->
    <div class="admin-table-wrapper">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="text-white fw-bold mb-0 font-chakra">
                <i class="fa-solid fa-list-ol text-warning me-2"></i> รายการกติกาการแข่งขัน (<?= count($rules) ?> หัวข้อ)
            </h5>
        </div>

        <?php if (empty($rules)): ?>
            <div class="empty-state-box">
                <div class="empty-icon">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
                <h5 class="text-white fs-5 fw-bold font-chakra">ยังไม่มีหัวข้อกติกาสำหรับปีการศึกษา <?= e($selected_year) ?></h5>
                <p class="text-light small mb-3">คุณสามารถคลิกปุ่มด้านล่างเพื่อเพิ่มกติกาข้อแรกของปีนี้</p>
                <a href="rules.php?year=<?= urlencode($selected_year) ?>&action=create" class="btn btn-sm btn-outline-warning font-chakra">
                    <i class="fa-solid fa-plus me-1"></i> เพิ่มกติกาใหม่
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-esports align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;" class="text-center">ลำดับ</th>
                            <th>ชื่อหัวข้อกติกา</th>
                            <th>ตัวอย่างเนื้อหา</th>
                            <th class="text-center" style="width: 130px;">สถานะ</th>
                            <th>แก้ไขล่าสุด</th>
                            <th class="text-end" style="width: 140px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $r): ?>
                            <tr>
                                <td class="text-center font-orbitron text-warning fw-bold">
                                    <?= sprintf("%02d", $r['sort_order']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-white fs-6"><?= e($r['title']) ?></div>
                                </td>
                                <td class="text-secondary small" style="max-width: 320px;">
                                    <?= mb_strimwidth(strip_tags($r['content']), 0, 90, '...') ?>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="rules.php?year=<?= urlencode($selected_year) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $r['is_active'] ? 'btn-outline-success' : 'btn-outline-secondary' ?> py-0 px-2 font-chakra" title="คลิกเพื่อเปลี่ยนสถานะ">
                                            <?= $r['is_active'] ? '<i class="fa-solid fa-eye me-1"></i> แสดงผล' : '<i class="fa-solid fa-eye-slash me-1"></i> ซ่อน' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="small font-chakra" style="color: #fff;">
                                    <?= date('d/m/Y H:i', strtotime($r['updated_at'])) ?> น.
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="rules.php?year=<?= urlencode($selected_year) ?>&edit=<?= $r['id'] ?>" class="btn btn-outline-warning" title="แก้ไข">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-confirm-delete"
                                            data-action="rules.php?year=<?= urlencode($selected_year) ?>" 
                                            data-id="<?= $r['id'] ?>" 
                                            data-name="กติกา: <?= e($r['title']) ?>">
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

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
