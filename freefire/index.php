<?php
/**
 * FREE FIRE ESPORTS - Dynamic Home Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$active_page = 'home';
$page_title = 'Free Fire Esports | โรงเรียนพร้าววิทยาคม';

$current_year = get_active_academic_year();
$reg_status = get_setting('registration_status', 'open');
$comp_name = get_setting('competition_name', 'FREE FIRE ESPORTS TOURNAMENT โรงเรียนพร้าววิทยาคม');

// Query count of registered teams in active academic year
$stmt_teams = $pdo->prepare("SELECT COUNT(*) FROM `freefire_teams` WHERE `academic_year` = ?");
$stmt_teams->execute([$current_year]);
$team_count = (int)$stmt_teams->fetchColumn();

// Query count of rules
$stmt_rules = $pdo->prepare("SELECT COUNT(*) FROM `freefire_rules` WHERE `academic_year` = ? AND `is_active` = 1");
$stmt_rules->execute([$current_year]);
$rules_count = (int)$stmt_rules->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

        <!-- Hero Section -->
        <section class="py-5 text-center position-relative">
            <div class="container py-lg-4">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-9">
                        
                        <div class="school-badge fire-badge">
                            <i class="fa-solid fa-graduation-cap"></i> โรงเรียนพร้าววิทยาคม
                        </div>

                        <div class="mb-3">
                            <span class="section-tag">
                                <i class="fa-solid fa-crosshairs"></i> FREEFIRE ESPORT <?= e($current_year) ?>
                            </span>
                        </div>

                        <h1 class="hero-title mb-3">
                            FREE FIRE ESPORTS<br>
                            ปีการศึกษา <?= e($current_year) ?>
                        </h1>

                        <p class="page-subtitle mb-4">
                            การแข่งขันเอาชีวิตรอดแห่งสมรภูมิครั้งยิ่งใหญ่ ชิงความเป็นหนึ่งของโรงเรียนพร้าววิทยาคม
                        </p>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
                            <?php if ($reg_status === 'open'): ?>
                                <a href="register.php" class="btn-esports btn-esports-primary">
                                    <i class="fa-solid fa-gamepad"></i> สมัครการแข่งขัน
                                    <span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                                </a>
                            <?php else: ?>
                                <button class="btn-esports btn-esports-secondary" disabled>
                                    <i class="fa-solid fa-lock"></i> ปิดรับสมัครแล้ว
                                </button>
                            <?php endif; ?>
                            <a href="rules.php" class="btn-esports btn-esports-secondary">
                                <i class="fa-solid fa-book-open"></i> กติกาการแข่งขัน
                            </a>
                            <a href="https://sc.pwks.ac.th/main/to/disesports" class="btn-esports btn-esports-secondary">
                                <i class="fa-brands fa-discord"></i> Discord
                            </a>
                            <a href="bracket.php" class="btn-esports btn-esports-outline">
                                <i class="fa-solid fa-trophy"></i> สายการแข่งขัน
                            </a>
                        </div>

                        <!-- Stats / Highlights -->
                        <div class="row g-3 justify-content-center">
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-4 bg-dark bg-opacity-50 border border-secondary border-opacity-25">
                                    <div class="fs-4 fw-bold text-white font-orbitron text-warning"><?= $team_count ?> ทีม</div>
                                    <div class="small text-secondary">ทีมที่ลงทะเบียนปี <?= e($current_year) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-4 bg-dark bg-opacity-50 border border-secondary border-opacity-25">
                                    <div class="fs-4 fw-bold text-white font-orbitron text-warning">4 + 1</div>
                                    <div class="small text-secondary">ผู้เล่นตัวจริง + สำรอง</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-4 bg-dark bg-opacity-50 border border-secondary border-opacity-25">
                                    <div class="fs-4 fw-bold text-white font-orbitron text-warning">2 ระดับ</div>
                                    <div class="small text-secondary">ม.ต้น และ ม.ปลาย</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded-4 bg-dark bg-opacity-50 border border-secondary border-opacity-25">
                                    <div class="fs-4 fw-bold text-white font-orbitron text-warning">
                                        <?= $reg_status === 'open' ? 'OPEN' : 'CLOSED' ?>
                                    </div>
                                    <div class="small text-secondary">สถานะการรับสมัคร</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <!-- Tournament Information Section -->
        <section class="py-5 position-relative">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-tag">
                        <i class="fa-solid fa-circle-info"></i> TOURNAMENT INFORMATION
                    </span>
                    <h2 class="page-title">ข้อมูลและรายละเอียดการแข่งขัน</h2>
                    <p class="page-subtitle">ทำความเข้าใจรายละเอียดและคุณสมบัติก่อนส่งรายชื่อเข้าร่วมการแข่งขัน</p>
                </div>

                <div class="row g-4">
                    <!-- Card 1: Game Mode -->
                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100">
                            <div class="feature-icon-wrapper">
                                <i class="fa-solid fa-gamepad"></i>
                            </div>
                            <h3 class="feature-title">ประเภทการแข่งขัน</h3>
                            <p class="feature-desc">
                                แข่งขันในโหมด <strong>Battle Royale (Squad)</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: Team Roster -->
                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100">
                            <div class="feature-icon-wrapper">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <h3 class="feature-title">จำนวนผู้เล่นต่อทีม</h3>
                            <p class="feature-desc">
                                ทีมละ 5 คน ประกอบด้วย <strong>ผู้เล่นตัวจริง 4 คน</strong> และ <strong>ผู้เล่นสำรอง 1 คน</strong> พร้อมครูที่ปรึกษาประจำทีม 1 ท่าน
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Eligible Grades -->
                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100">
                            <div class="feature-icon-wrapper">
                                <i class="fa-solid fa-school"></i>
                            </div>
                            <h3 class="feature-title">ระดับชั้นที่เปิดรับ</h3>
                            <p class="feature-desc">
                                แบ่งการแข่งขันออกเป็น 2 ระดับ:<br>
                                • ระดับมัธยมศึกษาตอนต้น (ม.1 - ม.3)<br>
                                • ระดับมัธยมศึกษาตอนปลาย (ม.4 - ม.6)
                            </p>
                        </div>
                    </div>

                    <!-- Card 4: Status -->
                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100">
                            <div class="feature-icon-wrapper">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h3 class="feature-title mb-0">สถานะการสมัคร</h3>
                                <?php if ($reg_status === 'open'): ?>
                                    <span class="pulse-badge">
                                        <span class="pulse-dot"></span> เปิดรับสมัคร
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-75 font-chakra py-1 px-2">
                                        <i class="fa-solid fa-lock me-1"></i> ปิดรับสมัคร
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="feature-desc">
                                <?= $reg_status === 'open' 
                                    ? 'เปิดรับสมัครทีมตัวแทนนักเรียนโรงเรียนพร้าววิทยาคม ประจำปีการศึกษา ' . e($current_year) . ' รีบลงทะเบียนก่อนปิดระบบ' 
                                    : 'ระบบรับสมัครการแข่งขันสำหรับปีการศึกษา ' . e($current_year) . ' ปิดเรียบร้อยแล้ว ติดตามสายการแข่งขันได้ที่หน้า Bracket' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tournament Timeline & Stages -->
        <!-- <section class="py-5 position-relative">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-tag">
                        <i class="fa-solid fa-calendar-days"></i> TOURNAMENT SCHEDULE
                    </span>
                    <h2 class="page-title">กำหนดการและไทม์ไลน์การแข่งขัน</h2>
                    <p class="page-subtitle">ขั้นตอนสำคัญตั้งแต่การเปิดรับสมัครจนถึงวันชิงชนะเลิศ</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100 text-center">
                            <div class="section-number mx-auto mb-3">01</div>
                            <h4 class="text-white fw-bold mb-2">เปิดรับสมัคร</h4>
                            <p class="small text-warning mb-2">ระบบรับสมัครออนไลน์</p>
                            <p class="text-secondary small mb-0">กรอกข้อมูลทีมและผู้เล่นทั้ง 5 คน (รวม IGN) ให้ครบถ้วน</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100 text-center">
                            <div class="section-number mx-auto mb-3">02</div>
                            <h4 class="text-white fw-bold mb-2">ประกาศสายแข่ง</h4>
                            <p class="small text-warning mb-2">หลังปิดรับสมัคร</p>
                            <p class="text-secondary small mb-0">ประกาศตารางการแข่งขันและแบ่งกลุ่มผ่านหน้าเว็บไซต์และ Discord</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100 text-center">
                            <div class="section-number mx-auto mb-3">03</div>
                            <h4 class="text-white fw-bold mb-2">รอบคัดเลือก</h4>
                            <p class="small text-warning mb-2">รอบเก็บคะแนน Group Stage</p>
                            <p class="text-secondary small mb-0">แข่งขันออนไลน์ คัดเลือกทีมคะแนนสูงสุดเข้าสู่รอบชิงชนะเลิศ</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="esports-card h-100 text-center">
                            <div class="section-number mx-auto mb-3">04</div>
                            <h4 class="text-white fw-bold mb-2">Grand Final</h4>
                            <p class="small text-warning mb-2">รอบชิงชนะเลิศ Booyah!</p>
                            <p class="text-secondary small mb-0">การแข่งขันรอบสุดท้ายเพื่อเฟ้นหาแชมป์ Free Fire โรงเรียนพร้าววิทยาคม</p>
                        </div>
                    </div>
                </div>
            </div>
        </section> -->

        <!-- Registration CTA Section -->
        <section class="py-5 position-relative">
            <div class="container">
                <div class="esports-card text-center p-4 p-md-5 border-warning border-opacity-50">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="school-badge fire-badge mb-3">
                                <i class="fa-solid fa-trophy"></i> สมรภูมิกำลังรอคุณอยู่
                            </div>
                            <h2 class="hero-title mb-3" style="font-size: clamp(26px, 4vw, 42px);">
                                พร้อมลงสนามแล้วหรือยัง?
                            </h2>
                            <p class="text-secondary mb-4 fs-6">
                                รวบรวมทีมของคุณให้พร้อม กรอกข้อมูลให้ครบถ้วน และร่วมเป็นส่วนหนึ่งของประวัติศาสตร์การแข่งขัน Esports โรงเรียนพร้าววิทยาคม
                            </p>
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <?php if ($reg_status === 'open'): ?>
                                    <a href="register.php" class="btn-esports btn-esports-primary btn-lg">
                                        <i class="fa-solid fa-pen-nib"></i> สมัครการแข่งขันทันที
                                        <span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                                    </a>
                                <?php endif; ?>
                                <a href="rules.php" class="btn-esports btn-esports-secondary btn-lg">
                                    <i class="fa-solid fa-file-lines"></i> ศึกษากติกาการแข่ง
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
