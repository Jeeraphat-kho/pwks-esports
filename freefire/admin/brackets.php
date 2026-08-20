<?php
/**
 * FREE FIRE ESPORTS - Admin Brackets & Embed Management
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$active_admin_page = 'brackets';
$page_title = 'จัดการสายการแข่งขัน | Free Fire Esports';
$current_year = get_active_academic_year();
$selected_year = $_GET['year'] ?? $current_year;

// Handle Actions (Create, Update, Toggle, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'CSRF Token ไม่ถูกต้องหรือหมดอายุ');
        header("Location: brackets.php?year=" . urlencode($selected_year));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $bracket_id = (int)($_POST['id'] ?? 0);

    if ($action === 'save_bracket') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'all');
        $embed_url = trim($_POST['embed_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $academic_year = trim($_POST['academic_year'] ?? $selected_year);

        if (empty($title) || empty($embed_url)) {
            set_flash('danger', 'กรุณากรอกชื่อหัวข้อและลิงก์ Embed สายการแข่งขัน');
        } else {
            if ($bracket_id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE `freefire_brackets` SET 
                    `title` = ?, 
                    `category` = ?, 
                    `embed_url` = ?, 
                    `description` = ?, 
                    `sort_order` = ?, 
                    `is_active` = ?, 
                    `academic_year` = ?, 
                    `updated_at` = NOW() 
                    WHERE `id` = ?");
                $stmt->execute([$title, $category, $embed_url, $description, $sort_order, $is_active, $academic_year, $bracket_id]);
                set_flash('success', 'บันทึกการแก้ไขสายการแข่งขันเรียบร้อยแล้ว');
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO `freefire_brackets` 
                    (`academic_year`, `title`, `category`, `embed_url`, `description`, `sort_order`, `is_active`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$academic_year, $title, $category, $embed_url, $description, $sort_order, $is_active]);
                set_flash('success', 'เพิ่มสายการแข่งขันใหม่เรียบร้อยแล้ว');
            }
        }
        header("Location: brackets.php?year=" . urlencode($academic_year));
        exit;
    }

    if ($action === 'toggle_active') {
        if ($bracket_id > 0) {
            $stmt = $pdo->prepare("UPDATE `freefire_brackets` SET `is_active` = NOT `is_active`, `updated_at` = NOW() WHERE `id` = ?");
            $stmt->execute([$bracket_id]);
            set_flash('success', 'เปลี่ยนสถานะการแสดงผลสายการแข่งขันเรียบร้อยแล้ว');
        }
        header("Location: brackets.php?year=" . urlencode($selected_year));
        exit;
    }

    if ($action === 'delete') {
        if ($bracket_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM `freefire_brackets` WHERE `id` = ?");
            $stmt->execute([$bracket_id]);
            set_flash('success', 'ลบรายการสายการแข่งขันเรียบร้อยแล้ว');
        }
        header("Location: brackets.php?year=" . urlencode($selected_year));
        exit;
    }
}

// Fetch all brackets for selected academic year
$stmt_brackets = $pdo->prepare("SELECT * FROM `freefire_brackets` WHERE `academic_year` = ? ORDER BY `sort_order` ASC, `id` ASC");
$stmt_brackets->execute([$selected_year]);
$brackets = $stmt_brackets->fetchAll();

// Edit mode check
$edit_bracket = null;
if (!empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_e = $pdo->prepare("SELECT * FROM `freefire_brackets` WHERE `id` = ?");
    $stmt_e->execute([$edit_id]);
    $edit_bracket = $stmt_e->fetch();
}
$is_creating = isset($_GET['action']) && $_GET['action'] === 'create';

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid px-lg-4 py-4">
    
    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1 font-orbitron">
                <i class="fa-solid fa-sitemap text-warning me-2"></i> จัดการสายการแข่งขัน (Brackets)
            </h2>
            <p class="text-secondary small mb-0">
                กำหนดและจัดการลิงก์ Embed ผังสายการแข่งขันแยกตามหมวดหมู่ ประจำปีการศึกษา <strong class="text-warning">ปี <?= e($selected_year) ?></strong>
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="brackets.php?year=<?= urlencode($selected_year) ?>&action=create" class="btn btn-sm btn-warning font-chakra">
                <i class="fa-solid fa-plus me-1"></i> เพิ่มสายการแข่งขันใหม่
            </a>
        </div>
    </div>

    <?= render_flash() ?>

    <!-- Add / Edit Bracket Form -->
    <?php if ($edit_bracket || $is_creating): ?>
        <div class="admin-table-wrapper mb-4 border-warning">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                <h5 class="text-white fw-bold mb-0 font-chakra text-warning">
                    <i class="fa-solid fa-pen-to-square me-2"></i> <?= $edit_bracket ? 'แก้ไขสายการแข่งขัน' : 'เพิ่มสายการแข่งขันใหม่' ?> (ปีการศึกษา <?= e($selected_year) ?>)
                </h5>
                <a href="brackets.php?year=<?= urlencode($selected_year) ?>" class="btn btn-sm btn-outline-secondary font-chakra">
                    <i class="fa-solid fa-xmark me-1"></i> ปิดฟอร์ม
                </a>
            </div>

            <form method="POST" action="brackets.php?year=<?= urlencode($selected_year) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_bracket">
                <input type="hidden" name="id" value="<?= $edit_bracket['id'] ?? '' ?>">
                <input type="hidden" name="academic_year" value="<?= e($selected_year) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label text-secondary small">ชื่อหัวข้อสายการแข่งขัน <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="เช่น สายการแข่งขัน ม.ปลาย, รอบคัดเลือกกลุ่ม A" value="<?= e($edit_bracket['title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-secondary small">หมวดหมู่ / ระดับการแข่งขัน <span class="text-danger">*</span></label>
                        <select name="category" class="form-select">
                            <option value="senior" <?= (isset($edit_bracket['category']) && $edit_bracket['category'] === 'senior') ? 'selected' : '' ?>>มัธยมศึกษาตอนปลาย (Senior)</option>
                            <option value="junior" <?= (isset($edit_bracket['category']) && $edit_bracket['category'] === 'junior') ? 'selected' : '' ?>>มัธยมศึกษาตอนต้น (Junior)</option>
                            <option value="all" <?= (isset($edit_bracket['category']) && $edit_bracket['category'] === 'all') ? 'selected' : '' ?>>สายการแข่งขันรวม (All)</option>
                            <option value="qualifier" <?= (isset($edit_bracket['category']) && $edit_bracket['category'] === 'qualifier') ? 'selected' : '' ?>>รอบคัดเลือก (Qualifier)</option>
                            <option value="final" <?= (isset($edit_bracket['category']) && $edit_bracket['category'] === 'final') ? 'selected' : '' ?>>รอบชิงชนะเลิศ (Final)</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-6">
                        <label class="form-label text-secondary small">ลำดับแสดงผล (Sort Order)</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e($edit_bracket['sort_order'] ?? (count($brackets) + 1)) ?>" min="0">
                    </div>

                    <div class="col-md-2 col-6 d-flex align-items-end pb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveBracketCheck" value="1" <?= (!isset($edit_bracket) || !empty($edit_bracket['is_active'])) ? 'checked' : '' ?> style="accent-color: #ff5500;">
                            <label class="form-check-label text-white small" for="isActiveBracketCheck">
                                เปิดการแสดงผล
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-secondary small">
                            ลิงก์ Embed หรือ iframe URL <span class="text-danger">*</span>
                        </label>
                        <textarea name="embed_url" class="form-control font-monospace text-warning" rows="2" placeholder="เช่น https://challonge.com/tournament/bracket_generator หรือโค้ด iframe..." required><?= e($edit_bracket['embed_url'] ?? '') ?></textarea>
                        <div class="form-text text-secondary small">รองรับทั้ง URL โดยตรง หรือโค้ด &lt;iframe src="..."&gt; ระบบจะดึง URL มาแสดงผลให้อัตโนมัติ</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-secondary small">คำอธิบายเพิ่มเติม (Optional)</label>
                        <input type="text" name="description" class="form-control" placeholder="เช่น ตารางและสายการแข่งขันรอบคัดเลือกและรอบชิงชนะเลิศ" value="<?= e($edit_bracket['description'] ?? '') ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="brackets.php?year=<?= urlencode($selected_year) ?>" class="btn btn-secondary font-chakra px-4">ยกเลิก</a>
                    <button type="submit" class="btn btn-warning font-chakra px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูลสายการแข่งขัน
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Brackets List Table -->
    <div class="admin-table-wrapper">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="text-white fw-bold mb-0 font-chakra">
                <i class="fa-solid fa-list-ol text-warning me-2"></i> รายการสายการแข่งขันที่ลงทะเบียน (<?= count($brackets) ?> รายการ)
            </h5>
        </div>

        <?php if (empty($brackets)): ?>
            <div class="empty-state-box">
                <div class="empty-icon">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <h5 class="text-white fs-5 fw-bold font-chakra">ยังไม่มีสายการแข่งขันสำหรับปีการศึกษา <?= e($selected_year) ?></h5>
                <p class="text-light small mb-3">คุณสามารถคลิกปุ่มด้านล่างเพื่อเพิ่มสายการแข่งขันรายการแรก</p>
                <a href="brackets.php?year=<?= urlencode($selected_year) ?>&action=create" class="btn btn-sm btn-outline-warning font-chakra">
                    <i class="fa-solid fa-plus me-1"></i> เพิ่มสายการแข่งขันใหม่
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-esports align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;" class="text-center">ลำดับ</th>
                            <th>ชื่อหัวข้อสายการแข่งขัน</th>
                            <th>หมวดหมู่</th>
                            <th>ลิงก์ Embed</th>
                            <th class="text-center" style="width: 130px;">สถานะ</th>
                            <th class="text-end" style="width: 170px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brackets as $b): 
                            $embed = trim($b['embed_url']);
                            if (preg_match('/src=["\']([^"\']+)["\']/i', $embed, $matches)) {
                                $embed_src = $matches[1];
                            } else {
                                $embed_src = $embed;
                            }
                        ?>
                            <tr>
                                <td class="text-center font-orbitron text-warning fw-bold">
                                    <?= sprintf("%02d", $b['sort_order']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-white fs-6"><?= e($b['title']) ?></div>
                                    <?php if (!empty($b['description'])): ?>
                                        <div class="small" style="color: #fff;"><?= e($b['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary font-chakra">
                                        <?= match ($b['category']) {
                                            'senior' => 'ม.ปลาย (Senior)',
                                            'junior' => 'ม.ต้น (Junior)',
                                            'all' => 'รวมทั้งหมด (All)',
                                            'qualifier' => 'รอบคัดเลือก',
                                            'final' => 'รอบชิงชนะเลิศ',
                                            default => e($b['category'])
                                        } ?>
                                    </span>
                                </td>
                                <td class="small font-monospace" style="max-width: 250px;">
                                    <a href="<?= e($embed_src) ?>" target="_blank" class="text-info text-truncate d-inline-block" style="max-width: 240px;">
                                        <?= e($embed_src) ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="brackets.php?year=<?= urlencode($selected_year) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $b['is_active'] ? 'btn-outline-success' : 'btn-outline-secondary' ?> py-0 px-2 font-chakra" title="คลิกเพื่อเปลี่ยนสถานะ">
                                            <?= $b['is_active'] ? '<i class="fa-solid fa-eye me-1"></i> แสดงผล' : '<i class="fa-solid fa-eye-slash me-1"></i> ซ่อน' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Test View Modal Trigger -->
                                        <button type="button" class="btn btn-outline-info btn-preview-bracket" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#previewBracketModal" 
                                            data-title="<?= e($b['title']) ?>" 
                                            data-src="<?= e($embed_src) ?>" 
                                            title="ดูตัวอย่าง Embed">
                                            <i class="fa-solid fa-play"></i>
                                        </button>

                                        <a href="brackets.php?year=<?= urlencode($selected_year) ?>&edit=<?= $b['id'] ?>" class="btn btn-outline-warning" title="แก้ไข">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <button type="button" class="btn btn-outline-danger btn-confirm-delete"
                                            data-action="brackets.php?year=<?= urlencode($selected_year) ?>" 
                                            data-id="<?= $b['id'] ?>" 
                                            data-name="สายการแข่ง: <?= e($b['title']) ?>">
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

<!-- Bracket Embed Preview Modal -->
<div class="modal fade" id="previewBracketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content esports-modal">
            <div class="modal-header esports-modal-header">
                <h5 class="modal-title font-chakra fw-bold text-warning" id="previewBracketModalTitle">
                    <i class="fa-solid fa-sitemap me-2"></i> ตัวอย่างสายการแข่งขัน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden border border-warning border-opacity-25" style="min-height: 550px;">
                    <iframe id="previewBracketIframe" src="" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const previewModal = document.getElementById('previewBracketModal');
        if (previewModal) {
            previewModal.addEventListener('show.bs.modal', (e) => {
                const button = e.relatedTarget;
                const title = button.getAttribute('data-title');
                const src = button.getAttribute('data-src');

                document.getElementById('previewBracketModalTitle').innerHTML = '<i class="fa-solid fa-sitemap me-2"></i> ' + title;
                document.getElementById('previewBracketIframe').src = src;
            });

            previewModal.addEventListener('hidden.bs.modal', () => {
                document.getElementById('previewBracketIframe').src = '';
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
