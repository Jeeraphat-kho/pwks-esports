<?php
/**
 * FREE FIRE ESPORTS - Dynamic Bracket & Embed Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$active_page = 'bracket';
$page_title = 'สายการแข่งขัน Free Fire | โรงเรียนพร้าววิทยาคม';

$current_year = get_active_academic_year();

// Query all active brackets for currently active academic year
$stmt_brackets = $pdo->prepare("SELECT * FROM `freefire_brackets` WHERE `academic_year` = ? AND `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC");
$stmt_brackets->execute([$current_year]);
$brackets = $stmt_brackets->fetchAll();

// 2. Query registered teams for current academic year
$stmt_teams = $pdo->prepare("SELECT `id`, `academic_year`, `team_name`, `level`, `teacher_advisor`, `team_logo`, `status` 
    FROM `freefire_teams` 
    WHERE `academic_year` = ? AND `status` != 'rejected' 
    ORDER BY `id` ASC");
$stmt_teams->execute([$current_year]);
$all_registered_teams = $stmt_teams->fetchAll();

// 3. Query all public players (Safe Public Fields ONLY: NO email, NO phone)
$stmt_players = $pdo->prepare("SELECT `team_id`, `player_type`, `player_order`, `title`, `first_name`, `last_name`, `ign`, `class_level`, `room`, `student_no` 
    FROM `freefire_players` 
    ORDER BY `team_id` ASC, `player_order` ASC");
$stmt_players->execute();
$all_players_raw = $stmt_players->fetchAll();

$players_by_team = [];
foreach ($all_players_raw as $p) {
    $players_by_team[$p['team_id']][] = $p;
}

$junior_teams = [];
$senior_teams = [];
$teams_json_data = [];

foreach ($all_registered_teams as $t) {
    $tid = (int)$t['id'];
    $t_players = $players_by_team[$tid] ?? [];
    
    $teams_json_data[$tid] = [
        'id' => $t['id'],
        'team_name' => $t['team_name'],
        'level' => $t['level'],
        'level_text' => format_level($t['level']),
        'teacher_advisor' => $t['teacher_advisor'],
        'team_logo' => $t['team_logo'],
        'academic_year' => $t['academic_year'],
        'players' => $t_players
    ];

    if ($t['level'] === 'junior') {
        $junior_teams[] = $t;
    } else {
        $senior_teams[] = $t;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

        <!-- Header -->
        <header class="py-5 text-center position-relative">
            <div class="container">
                <div class="school-badge fire-badge">
                    <i class="fa-solid fa-trophy"></i> TOURNAMENT BRACKET & TEAMS
                </div>
                <h1 class="page-title mb-3">สายการแข่งขันและทีมที่เข้าร่วม</h1>
                <p class="page-subtitle">
                    ติดตามการแบ่งสาย ตารางการแข่งขัน และรายชื่อทีมผู้เข้าแข่งขัน Free Fire Esports โรงเรียนพร้าววิทยาคม ประจำปีการศึกษา <?= e($current_year) ?>
                </p>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container pb-5">

            <!-- =========================================================================
                 SECTION 1: TOURNAMENT BRACKETS
                 ========================================================================= -->
            <?php if (empty($brackets)): ?>
                <div class="esports-card text-center py-5 mb-5">
                    <div class="fs-1 text-warning mb-3"><i class="fa-solid fa-sitemap"></i></div>
                    <h3 class="text-white fw-bold mb-2">ยังไม่มีการประกาศสายการแข่งขันสำหรับปีการศึกษา <?= e($current_year) ?></h3>
                    <p class="text-secondary mb-4 max-w-500 mx-auto">
                        ระบบจะแสดงแผนผังสายการแข่งขัน (Tournament Bracket Embed) ทันทีหลังการจับสลากแบ่งสายเสร็จสิ้น
                    </p>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-dark border border-warning border-opacity-50 text-warning font-chakra small">
                        <i class="fa-solid fa-clock-rotate-left"></i> สถานะ: อยู่ระหว่างเปิดรับสมัคร / รอประกาศสายแข่ง
                    </div>
                </div>
            <?php else: ?>
                <!-- Category Tabs if multiple brackets exist -->
                <?php if (count($brackets) > 1): ?>
                    <div class="d-flex justify-content-center mb-4">
                        <ul class="nav bracket-nav-pills gap-2 flex-wrap" id="bracketTabs" role="tablist">
                            <?php foreach ($brackets as $idx => $b): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $idx === 0 ? 'active' : '' ?>" id="tab_btn_<?= $b['id'] ?>" data-bs-toggle="pill" data-bs-target="#tab_content_<?= $b['id'] ?>" type="button" role="tab">
                                        <i class="fa-solid fa-trophy me-1"></i> <?= e($b['title']) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="tab-content mb-5" id="bracketTabContent">
                    <?php foreach ($brackets as $idx => $b): 
                        $embed = trim($b['embed_url']);
                        if (preg_match('/src=["\']([^"\']+)["\']/i', $embed, $matches)) {
                            $embed_src = $matches[1];
                        } else {
                            $embed_src = $embed;
                        }
                    ?>
                        <div class="tab-pane fade <?= $idx === 0 ? 'show active' : '' ?>" id="tab_content_<?= $b['id'] ?>" role="tabpanel">
                            
                            <div class="bracket-embed-container">
                                <?php if (!empty($b['description'])): ?>
                                    <div class="text-center mb-3">
                                        <h4 class="text-white fw-bold mb-1"><?= e($b['title']) ?></h4>
                                        <p class="text-secondary small mb-0"><?= e($b['description']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($embed_src) && filter_var($embed_src, FILTER_VALIDATE_URL)): ?>
                                    <div class="w-100 position-relative rounded-4 overflow-hidden" style="min-height: 600px; border: 1px solid rgba(255, 85, 0, 0.2);">
                                        <iframe src="<?= e($embed_src) ?>" width="100%" height="650" frameborder="0" scrolling="auto" allowtransparency="true" style="border:none; width:100%; min-height:650px;"></iframe>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <a href="<?= e($embed_src) ?>" target="_blank" class="btn btn-sm btn-outline-warning">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> เปิดสายการแข่งขันในหน้าต่างใหม่
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="bracket-placeholder-box">
                                        <div class="fs-1 text-warning mb-3">
                                            <i class="fa-solid fa-sitemap"></i>
                                        </div>
                                        <h3 class="text-white fw-bold mb-2"><?= e($b['title']) ?></h3>
                                        <p class="text-secondary mb-3">
                                            <?= !empty($b['description']) ? e($b['description']) : 'กำลังจัดเตรียมสายการแข่งขัน' ?>
                                        </p>
                                        <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-dark border border-warning border-opacity-50 text-warning font-chakra small">
                                            <i class="fa-solid fa-clock-rotate-left"></i> สถานะ: รอการอัปเดต Embed จากผู้ดูแลระบบ
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- =========================================================================
                 SECTION 2: REGISTERED TEAMS ROSTER (Categorized by Junior & Senior)
                 ========================================================================= -->
            <div class="esports-card mb-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                    <div>
                        <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-warning"></i> ทีมที่ลงทะเบียนเข้าร่วมการแข่งขัน
                        </h3>
                        <p class="text-secondary small mb-0">ประจำปีการศึกษา <?= e($current_year) ?> • คลิกที่ทีมเพื่อดูรายชื่อสมาชิก</p>
                    </div>
                    <span class="badge bg-warning text-dark font-chakra fs-6 px-3 py-2">
                        รวมทั้งหมด <?= count($all_registered_teams) ?> ทีม
                    </span>
                </div>

                <!-- Level Tabs -->
                <ul class="nav bracket-nav-pills gap-2 mb-4 justify-content-center justify-content-md-start" id="teamCategoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab_junior_btn" data-bs-toggle="pill" data-bs-target="#tab_junior_teams" type="button" role="tab">
                            <i class="fa-solid fa-graduation-cap me-1"></i> มัธยมศึกษาตอนต้น (ม.1 - ม.3)
                            <span class="badge bg-info text-dark ms-2"><?= count($junior_teams) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab_senior_btn" data-bs-toggle="pill" data-bs-target="#tab_senior_teams" type="button" role="tab">
                            <i class="fa-solid fa-award me-1"></i> มัธยมศึกษาตอนปลาย / ปวช.
                            <span class="badge bg-warning text-dark ms-2"><?= count($senior_teams) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab_all_btn" data-bs-toggle="pill" data-bs-target="#tab_all_teams" type="button" role="tab">
                            <i class="fa-solid fa-users me-1"></i> ดูทีมทั้งหมด
                            <span class="badge bg-secondary ms-2"><?= count($all_registered_teams) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="teamCategoryTabContent">
                    
                    <!-- TAB 1: JUNIOR TEAMS (ม.ต้น) -->
                    <div class="tab-pane fade show active" id="tab_junior_teams" role="tabpanel">
                        <?php if (empty($junior_teams)): ?>
                            <div class="empty-state-box text-center py-5">
                                <i class="fa-solid fa-graduation-cap fs-1 text-info mb-2"></i>
                                <h5 class="text-white">ยังไม่มีทีมระดับ ม.ต้น ลงทะเบียน</h5>
                                <p class="text-secondary small">สมัครแข่งขันเป็นทีมแรกของระดับชั้นมัธยมศึกษาตอนต้นได้เลย!</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($junior_teams as $t): 
                                    $p_count = count($players_by_team[$t['id']] ?? []);
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between hover-glow-card">
                                            <div>
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <div class="rounded-3 border border-secondary border-opacity-50 p-1 bg-black d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; min-width: 48px;">
                                                        <?php if (!empty($t['team_logo'])): ?>
                                                            <img src="<?= e($t['team_logo']) ?>" alt="Logo" class="img-fluid rounded-2" style="max-height: 40px;">
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-shield-halved text-info fs-4"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <h5 class="fw-bold text-white mb-0 text-truncate"><?= e($t['team_name']) ?></h5>
                                                        <span class="badge bg-info text-dark font-chakra" style="font-size: 11px;">มัธยมศึกษาตอนต้น</span>
                                                    </div>
                                                </div>
                                                <div class="text-secondary small mb-3">
                                                    <i class="fa-solid fa-chalkboard-user text-warning me-1"></i> ครูที่ปรึกษา: <strong class="text-light"><?= e($t['teacher_advisor']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
                                                <span class="text-muted small font-chakra"><i class="fa-solid fa-user-group me-1"></i> <?= $p_count ?> คน</span>
                                                <button type="button" class="btn btn-sm btn-outline-warning font-chakra btn-open-public-team" data-team-id="<?= $t['id'] ?>">
                                                    <i class="fa-solid fa-users me-1"></i> ดูสมาชิกทีม
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 2: SENIOR TEAMS (ม.ปลาย / ปวช.) -->
                    <div class="tab-pane fade" id="tab_senior_teams" role="tabpanel">
                        <?php if (empty($senior_teams)): ?>
                            <div class="empty-state-box text-center py-5">
                                <i class="fa-solid fa-award fs-1 text-warning mb-2"></i>
                                <h5 class="text-white">ยังไม่มีทีมระดับ ม.ปลาย / ปวช. ลงทะเบียน</h5>
                                <p class="text-secondary small">สมัครแข่งขันเป็นทีมแรกของระดับชั้นมัธยมศึกษาตอนปลาย / ปวช. ได้เลย!</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($senior_teams as $t): 
                                    $p_count = count($players_by_team[$t['id']] ?? []);
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between hover-glow-card">
                                            <div>
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <div class="rounded-3 border border-secondary border-opacity-50 p-1 bg-black d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; min-width: 48px;">
                                                        <?php if (!empty($t['team_logo'])): ?>
                                                            <img src="<?= e($t['team_logo']) ?>" alt="Logo" class="img-fluid rounded-2" style="max-height: 40px;">
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-shield-halved text-warning fs-4"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <h5 class="fw-bold text-white mb-0 text-truncate"><?= e($t['team_name']) ?></h5>
                                                        <span class="badge bg-warning text-dark font-chakra" style="font-size: 11px;">ม.ปลาย / ปวช.</span>
                                                    </div>
                                                </div>
                                                <div class="text-secondary small mb-3">
                                                    <i class="fa-solid fa-chalkboard-user text-warning me-1"></i> ครูที่ปรึกษา: <strong class="text-light"><?= e($t['teacher_advisor']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
                                                <span class="text-muted small font-chakra"><i class="fa-solid fa-user-group me-1"></i> <?= $p_count ?> คน</span>
                                                <button type="button" class="btn btn-sm btn-outline-warning font-chakra btn-open-public-team" data-team-id="<?= $t['id'] ?>">
                                                    <i class="fa-solid fa-users me-1"></i> ดูสมาชิกทีม
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 3: ALL TEAMS -->
                    <div class="tab-pane fade" id="tab_all_teams" role="tabpanel">
                        <?php if (empty($all_registered_teams)): ?>
                            <div class="empty-state-box text-center py-5">
                                <i class="fa-solid fa-users-slash fs-1 text-secondary mb-2"></i>
                                <h5 class="text-white">ยังไม่มีทีมลงทะเบียนเข้าร่วมการแข่งขัน</h5>
                                <p class="text-secondary small">สามารถสมัครแข่งขันได้ที่ปุ่มด้านล่างนี้</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($all_registered_teams as $t): 
                                    $p_count = count($players_by_team[$t['id']] ?? []);
                                    $is_jun = ($t['level'] === 'junior');
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between hover-glow-card">
                                            <div>
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <div class="rounded-3 border border-secondary border-opacity-50 p-1 bg-black d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; min-width: 48px;">
                                                        <?php if (!empty($t['team_logo'])): ?>
                                                            <img src="<?= e($t['team_logo']) ?>" alt="Logo" class="img-fluid rounded-2" style="max-height: 40px;">
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-shield-halved <?= $is_jun ? 'text-info' : 'text-warning' ?> fs-4"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <h5 class="fw-bold text-white mb-0 text-truncate"><?= e($t['team_name']) ?></h5>
                                                        <span class="badge <?= $is_jun ? 'bg-info text-dark' : 'bg-warning text-dark' ?> font-chakra" style="font-size: 11px;">
                                                            <?= $is_jun ? 'ม.ต้น' : 'ม.ปลาย / ปวช.' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-secondary small mb-3">
                                                    <i class="fa-solid fa-chalkboard-user text-warning me-1"></i> ครูที่ปรึกษา: <strong class="text-light"><?= e($t['teacher_advisor']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
                                                <span class="text-muted small font-chakra"><i class="fa-solid fa-user-group me-1"></i> <?= $p_count ?> คน</span>
                                                <button type="button" class="btn btn-sm btn-outline-warning font-chakra btn-open-public-team" data-team-id="<?= $t['id'] ?>">
                                                    <i class="fa-solid fa-users me-1"></i> ดูสมาชิกทีม
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Bottom CTA -->
            <div class="text-center mt-4">
                <a href="register.php" class="btn-esports btn-esports-primary btn-lg">
                    <i class="fa-solid fa-gamepad"></i> สมัครเข้าร่วมการแข่งขัน
                    <span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                </a>
            </div>

        </main>

        <!-- =========================================================================
             PUBLIC TEAM ROSTER POPUP MODAL (No Phone, No Email)
             ========================================================================= -->
        <div class="modal fade" id="publicTeamModal" tabindex="-1" aria-labelledby="publicTeamModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content esports-modal">
                    <div class="modal-header esports-modal-header border-warning border-opacity-50">
                        <h5 class="modal-title font-chakra fw-bold text-warning d-flex align-items-center gap-2" id="publicTeamModalLabel">
                            <i class="fa-solid fa-shield"></i> รายละเอียดทีมและสมาชิก
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        
                        <!-- Team Summary Header Card -->
                        <div class="d-flex flex-wrap align-items-center gap-3 p-3 rounded-3 bg-dark border border-secondary border-opacity-25 mb-4">
                            <div id="pubLogoWrapper" style="width: 70px; height: 70px; min-width: 70px;">
                                <img id="pubTeamLogo" src="" alt="Logo" class="rounded-3" style="width: 70px; height: 70px; object-fit: cover; border: 2px solid #ff5500; display: none; cursor: pointer; transition: transform 0.2s;" title="คลิกเพื่อดูรูปภาพขนาดใหญ่" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                                <div id="pubLogoPlaceholder" class="rounded-3 bg-dark border border-secondary align-items-center justify-content-center text-muted" style="width: 70px; height: 70px; font-size: 32px; display: none;">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                            </div>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h3 class="text-white fw-bold mb-0" id="pubTeamName">—</h3>
                                    <span class="badge bg-success bg-opacity-75 font-chakra"><i class="fa-solid fa-circle-check me-1"></i> ลงทะเบียนแล้ว</span>
                                </div>
                                <div class="text-secondary small">
                                    <span class="text-info fw-bold font-chakra" id="pubTeamLevel">—</span> • 
                                    ปีการศึกษา <span class="text-warning font-orbitron" id="pubAcademicYear">—</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="fa-solid fa-chalkboard-user text-warning me-1"></i> ครูที่ปรึกษาทีม: <strong class="text-white" id="pubAdvisor">—</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Players Table -->
                        <h5 class="text-white fw-bold mb-3 font-chakra d-flex align-items-center gap-2">
                            <i class="fa-solid fa-users text-warning"></i> <span id="pubPlayersHeaderTitle">รายชื่อสมาชิกในทีม</span>
                        </h5>

                        <div class="table-responsive mb-2">
                            <table class="table table-dark table-bordered table-sm align-middle font-kanit">
                                <thead class="table-active text-center small font-chakra">
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th style="width: 95px;">บทบาท</th>
                                        <th>ชื่อ - นามสกุล</th>
                                        <th style="width: 170px;">ชื่อในเกม (IGN)</th>
                                        <th style="width: 110px;">ชั้น / ห้อง</th>
                                        <th style="width: 60px;">เลขที่</th>
                                    </tr>
                                </thead>
                                <tbody id="pubPlayersTableBody">
                                    <!-- Injected via JavaScript -->
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="modal-footer esports-modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm btn-secondary font-chakra px-4" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> ปิดหน้าต่าง
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pass Public Teams Data to Client JS -->
        <script>
            const PUBLIC_TEAMS_DATA = <?= json_encode($teams_json_data, JSON_UNESCAPED_UNICODE) ?>;

            document.addEventListener('DOMContentLoaded', () => {
                const modalEl = document.getElementById('publicTeamModal');
                if (modalEl && modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                const pubModal = modalEl ? new bootstrap.Modal(modalEl) : null;

                // Handle click on team cards
                document.querySelectorAll('.btn-open-public-team').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const teamId = btn.getAttribute('data-team-id');
                        const data = PUBLIC_TEAMS_DATA[teamId];
                        if (!data || !pubModal) return;

                        const t = data;
                        const players = data.players || [];

                        // Header Info
                        document.getElementById('pubTeamName').textContent = t.team_name;
                        document.getElementById('pubTeamLevel').textContent = t.level_text;
                        document.getElementById('pubAcademicYear').textContent = t.academic_year;
                        document.getElementById('pubAdvisor').textContent = t.teacher_advisor;

                        // Logo
                        const logoImg = document.getElementById('pubTeamLogo');
                        const logoPlaceholder = document.getElementById('pubLogoPlaceholder');
                        if (t.team_logo) {
                            logoImg.src = t.team_logo;
                            logoImg.style.display = 'block';
                            logoPlaceholder.style.display = 'none';
                        } else {
                            logoImg.src = '';
                            logoImg.style.display = 'none';
                            logoPlaceholder.style.display = 'flex';
                        }

                        // Title
                        const titleEl = document.getElementById('pubPlayersHeaderTitle');
                        if (titleEl) {
                            titleEl.textContent = `รายชื่อสมาชิกในทีม (${players.length} คน)`;
                        }

                        // Render Players (Safe Public Fields Only)
                        const tbody = document.getElementById('pubPlayersTableBody');
                        tbody.innerHTML = '';

                        players.forEach(p => {
                            const isSub = (p.player_type === 'substitute' || p.player_order == 5);
                            const isCap = (p.player_order == 1);

                            let roleBadge = '<span class="badge bg-dark border border-secondary font-chakra">ตัวจริง</span>';
                            if (isSub) roleBadge = '<span class="badge bg-secondary font-chakra">สำรอง</span>';
                            if (isCap) roleBadge = '<span class="badge bg-warning text-dark font-chakra"><i class="fa-solid fa-crown me-1"></i> กัปตัน</span>';

                            const classRoomText = (p.class_level === 'ปวช.') ? `ปวช. ปี ${p.room}` : `${p.class_level}/${p.room}`;

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="text-center text-muted">${p.player_order}</td>
                                <td class="text-center">${roleBadge}</td>
                                <td class="fw-bold text-white">${p.title} ${p.first_name} ${p.last_name}</td>
                                <td class="fw-bold text-warning font-chakra"><span class="badge bg-dark text-warning border border-warning border-opacity-50 px-2 py-1">${p.ign}</span></td>
                                <td class="text-center">${classRoomText}</td>
                                <td class="text-center">${p.student_no || '—'}</td>
                            `;
                            tbody.appendChild(tr);
                        });

                        pubModal.show();
                    });
                });

                // Public Logo Lightbox Popup (Fancybox 5)
                const pubTeamLogo = document.getElementById('pubTeamLogo');
                if (pubTeamLogo) {
                    pubTeamLogo.addEventListener('click', () => {
                        const src = pubTeamLogo.getAttribute('src');
                        const teamName = document.getElementById('pubTeamName')?.textContent || 'โลโก้ทีม';
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
            });
        </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
