<?php
/**
 * FREE FIRE ESPORTS - Dynamic Rules Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$active_page = 'rules';
$page_title = 'กติกาการแข่งขัน Free Fire | โรงเรียนพร้าววิทยาคม';

$current_year = get_active_academic_year();

// Query all active rules for currently active academic year
$stmt_rules = $pdo->prepare("SELECT * FROM `freefire_rules` WHERE `academic_year` = ? AND `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC");
$stmt_rules->execute([$current_year]);
$rules = $stmt_rules->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

        <!-- Header -->
        <header class="py-5 text-center position-relative">
            <div class="container">
                <div class="school-badge fire-badge">
                    <i class="fa-solid fa-scale-balanced"></i> OFFICIAL TOURNAMENT RULES
                </div>
                <h1 class="page-title mb-3">กฎและกติกาการแข่งขัน</h1>
                <p class="page-subtitle">
                    ข้อกำหนด ระเบียบการ และเงื่อนไขการแข่งขัน Free Fire Esports โรงเรียนพร้าววิทยาคม ประจำปีการศึกษา <?= e($current_year) ?>
                </p>
            </div>
        </header>

        <!-- Rules Content Container -->
        <main class="container pb-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">

                    <!-- Top Action / Notice Banner -->
                    <div class="esports-card mb-4 p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-2 text-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h5 class="text-white mb-1">โปรดอ่านและทำความเข้าใจกติกา (ปีการศึกษา <?= e($current_year) ?>)</h5>
                                <p class="text-secondary small mb-0">ผู้เข้าแข่งขันทุกคนถือว่ายอมรับกฎกติกาการแข่งขันทันทีเมื่อส่งใบสมัคร</p>
                            </div>
                        </div>
                        <a href="register.php" class="btn-esports btn-esports-primary">
                            <i class="fa-solid fa-pen-nib"></i> ไปหน้าสมัครแข่ง
                        </a>
                    </div>

                    <?php if (empty($rules)): ?>
                        <div class="esports-card text-center py-5">
                            <div class="fs-1 text-muted mb-3"><i class="fa-solid fa-file-circle-question"></i></div>
                            <h4 class="text-white">ยังไม่มีการประกาศกติกาสำหรับปีการศึกษา <?= e($current_year) ?></h4>
                            <p class="text-secondary">คณะกรรมการจะทำการอัปเดตกติกาการแข่งขันให้ทราบในเร็ว ๆ นี้</p>
                        </div>
                    <?php else: ?>
                        <!-- Dynamic Accordion: Rules loaded from Database -->
                        <div class="accordion rules-accordion" id="rulesAccordion">
                            <?php foreach ($rules as $idx => $rule): 
                                $collapse_id = 'collapse_' . $rule['id'];
                                $heading_id = 'heading_' . $rule['id'];
                                $is_first = ($idx === 0);
                            ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="<?= $heading_id ?>">
                                        <button class="accordion-button <?= $is_first ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>" aria-controls="<?= $collapse_id ?>">
                                            <span class="rules-num-badge"><?= sprintf("%02d", $rule['sort_order'] ?: ($idx + 1)) ?></span>
                                            <span><?= e($rule['title']) ?></span>
                                        </button>
                                    </h2>
                                    <div id="<?= $collapse_id ?>" class="accordion-collapse collapse <?= $is_first ? 'show' : '' ?>" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#rulesAccordion">
                                        <div class="accordion-body">
                                            <?= sanitize_html($rule['content']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bottom CTA -->
                    <div class="text-center mt-5">
                        <a href="register.php" class="btn-esports btn-esports-primary btn-lg">
                            <i class="fa-solid fa-gamepad"></i> เข้าใจกติกาแล้ว สมัครการแข่งขันเลย
                            <span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>

                </div>
            </div>
        </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
