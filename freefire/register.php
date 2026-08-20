<?php
/**
 * FREE FIRE ESPORTS - Dynamic Team Registration Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$active_page = 'register';
$page_title = 'สมัครการแข่งขัน Free Fire | โรงเรียนพร้าววิทยาคม';

$current_year = get_active_academic_year();
$reg_status = get_setting('registration_status', 'open');
$errors = [];
$success_team = null;

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Verify Registration is Open
    if ($reg_status !== 'open') {
        $errors[] = 'ขออภัย ระบบรับสมัครการแข่งขันสำหรับปีการศึกษา ' . e($current_year) . ' ปิดให้บริการแล้ว';
    }

    // 2. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session หมดอายุหรือไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บแล้วทำรายการใหม่อีกครั้ง';
    }

    // 3. Extract and Validate Team Details
    $level = trim($_POST['education_level'] ?? '');
    $team_name = trim($_POST['team_name'] ?? '');
    $teacher_advisor = trim($_POST['teacher_advisor'] ?? '');
    $confirm_truth = isset($_POST['confirm_truth']);

    if (!in_array($level, ['junior', 'senior'])) {
        $errors[] = 'กรุณาเลือกระดับการแข่งขัน (มัธยมศึกษาตอนต้น หรือ มัธยมศึกษาตอนปลาย)';
    }

    if (empty($team_name)) {
        $errors[] = 'กรุณากรอกชื่อทีม';
    }

    if (empty($teacher_advisor)) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุลครูที่ปรึกษาทีม';
    }

    if (!$confirm_truth) {
        $errors[] = 'กรุณาทำเครื่องหมายยืนยันว่าข้อมูลที่กรอกถูกต้องเป็นความจริง';
    }

    // 4. Validate Players (1 to 4 Main Required, 5 Substitute Optional)
    $players = [];
    for ($i = 1; $i <= 5; $i++) {
        $is_sub = ($i === 5);
        $role_text = $is_sub ? 'ผู้เล่นสำรอง' : "ผู้เล่นคนที่ {$i}";

        $title = trim($_POST["p{$i}_title"] ?? '');
        $fname = trim($_POST["p{$i}_fname"] ?? '');
        $lname = trim($_POST["p{$i}_lname"] ?? '');
        $ign = trim($_POST["p{$i}_ign"] ?? '');
        $class = trim($_POST["p{$i}_class"] ?? '');
        $room = trim($_POST["p{$i}_room"] ?? '');
        $num = (int)($_POST["p{$i}_num"] ?? 0);
        $email = trim($_POST["p{$i}_email"] ?? '');
        $phone = trim($_POST["p{$i}_phone"] ?? '');

        // If player 5 is completely empty, it is optional -> skip
        if ($is_sub && empty($title) && empty($fname) && empty($lname) && empty($ign)) {
            continue;
        }

        if (empty($title)) $errors[] = "กรุณาเลือกคำนำหน้าของ{$role_text}";
        if (empty($fname)) $errors[] = "กรุณากรอกชื่อจริงของ{$role_text}";
        if (empty($lname)) $errors[] = "กรุณากรอกนามสกุลของ{$role_text}";
        if (empty($ign)) $errors[] = "กรุณากรอกชื่อในเกม (IGN) ของ{$role_text}";
        if (empty($class)) $errors[] = "กรุณาเลือกชั้นเรียนของ{$role_text}";
        if (empty($room)) $errors[] = "กรุณาเลือกห้องเรียนของ{$role_text}";
        if ($num <= 0) $errors[] = "กรุณากรอกเลขที่ของ{$role_text}";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "กรุณากรอกอีเมลที่ถูกต้องของ{$role_text}";
        if (empty($phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 9) $errors[] = "กรุณากรอกเบอร์โทรศัพท์ของ{$role_text}";

        $players[] = [
            'player_type' => $is_sub ? 'substitute' : 'main',
            'player_order' => $i,
            'title' => $title,
            'first_name' => $fname,
            'last_name' => $lname,
            'ign' => $ign,
            'class_level' => $class,
            'room' => $room,
            'student_no' => $num,
            'email' => $email,
            'phone' => $phone
        ];
    }

    // 5. Handle Optional Team Logo Upload
    $logo_path = null;
    if (!empty($_FILES['team_logo']['tmp_name'])) {
        try {
            $logo_path = upload_team_logo($_FILES['team_logo']);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    // 6. If no errors -> Save to Database via PDO Transaction
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt_team = $pdo->prepare("INSERT INTO `freefire_teams` 
                (`academic_year`, `team_name`, `level`, `teacher_advisor`, `team_logo`, `status`) 
                VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmt_team->execute([
                $current_year,
                $team_name,
                $level,
                $teacher_advisor,
                $logo_path
            ]);

            $team_id = $pdo->lastInsertId();

            $stmt_player = $pdo->prepare("INSERT INTO `freefire_players` 
                (`team_id`, `player_type`, `player_order`, `title`, `first_name`, `last_name`, `ign`, `class_level`, `room`, `student_no`, `email`, `phone`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($players as $p) {
                $stmt_player->execute([
                    $team_id,
                    $p['player_type'],
                    $p['player_order'],
                    $p['title'],
                    $p['first_name'],
                    $p['last_name'],
                    $p['ign'],
                    $p['class_level'],
                    $p['room'],
                    $p['student_no'],
                    $p['email'],
                    $p['phone']
                ]);
            }

            $pdo->commit();

            $success_team = [
                'team_name' => $team_name,
                'level' => format_level($level),
                'academic_year' => $current_year
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

        <!-- Page Header -->
        <header class="py-5 text-center position-relative">
            <div class="container">
                <div class="school-badge fire-badge">
                    <i class="fa-solid fa-id-card"></i> TOURNAMENT REGISTRATION <?= e($current_year) ?>
                </div>
                <h1 class="page-title mb-3">แบบฟอร์มรับสมัครการแข่งขัน<br>PWKS Freefire Esports Tournament</h1>
                <p class="page-subtitle">
                    กรุณากรอกข้อมูลระดับการแข่งขัน ข้อมูลทีม และผู้เล่นทุกคน (ตัวจริง 4 คน + ตัวสำรอง 1 คน) ประจำปีการศึกษา <?= e($current_year) ?>
                </p>
            </div>
        </header>

        <!-- Main Registration Form Container -->
        <main class="container pb-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">

                    <?php if ($reg_status !== 'open'): ?>
                        <div class="esports-card text-center py-5">
                            <div class="fs-1 text-danger mb-3"><i class="fa-solid fa-lock"></i></div>
                            <h3 class="text-white fw-bold mb-2">ปิดรับสมัครการแข่งขันแล้ว</h3>
                            <p class="text-secondary mb-4">
                                ระบบรับสมัครการแข่งขัน Free Fire ประจำปีการศึกษา <?= e($current_year) ?> ได้ปิดลงเรียบร้อยแล้ว
                            </p>
                            <a href="bracket.php" class="btn-esports btn-esports-primary">
                                <i class="fa-solid fa-trophy"></i> ดูสายการแข่งขัน
                            </a>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4 border-danger" role="alert">
                                <h5 class="alert-heading font-chakra mb-2">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> กรุณาตรวจสอบข้อผิดพลาด:
                                </h5>
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form id="registrationForm" method="POST" enctype="multipart/form-data" novalidate>
                            <?= csrf_field() ?>

                            <!-- SECTION00 -->
                             <div class="form-section-card" id="levelSection">
                                <div class="section-header">
                                    <div class="section-number">00</div>
                                    <div class="section-header-text">
                                        <h3>เข้า Discord ก่อนสมัครการแข่งขัน <span class="req-star text-danger fs-6">*</span></h3>
                                        <p>เพื่อให้ไม่พลาดข่าวสาร และการประชาสัมพันธ์ต่างๆ ขอให้ทุกคนในทีมเข้าร่วม Discord PWKS E-Sports ด้วย</p>
                                    </div>
                                </div>
                                        <div class="d-flex justify-content-center">
                                            <a href="https://sc.pwks.ac.th/main/to/disesports" class="btn-esports btn-esports-secondary" target="blank_"><i class="fa-brands fa-discord"></i> Discord</a>
                                        </div>
                            </div>

                            <!-- ===================================================
                                 SECTION 01: ข้อมูลการแข่งขัน (ระดับการแข่งขัน)
                                 =================================================== -->
                            <div class="form-section-card" id="levelSection">
                                <div class="section-header">
                                    <div class="section-number">01</div>
                                    <div class="section-header-text">
                                        <h3>ระดับการแข่งขัน <span class="req-star text-danger fs-6">*</span></h3>
                                        <p>เลือกระดับชั้นการศึกษาของทีม (สมาชิกในทีมทุกคนต้องอยู่ในระดับชั้นเดียวกัน)</p>
                                    </div>
                                </div>

                                <div class="level-select-grid">
                                    <!-- Option 1: มัธยมศึกษาตอนต้น -->
                                    <label class="level-card-label">
                                        <input type="radio" name="education_level" value="junior" id="levelJunior" <?= (isset($_POST['education_level']) && $_POST['education_level'] === 'junior') ? 'checked' : '' ?> required>
                                        <div class="level-card-box">
                                            <div class="level-icon">
                                                <i class="fa-solid fa-graduation-cap"></i>
                                            </div>
                                            <div class="level-title">มัธยมศึกษาตอนต้น</div>
                                            <div class="level-desc">ระดับชั้น ม.1 - ม.3 โรงเรียนพร้าววิทยาคม</div>
                                        </div>
                                    </label>

                                    <!-- Option 2: มัธยมศึกษาตอนปลาย / ปวช. -->
                                    <label class="level-card-label">
                                        <input type="radio" name="education_level" value="senior" id="levelSenior" <?= (isset($_POST['education_level']) && $_POST['education_level'] === 'senior') ? 'checked' : '' ?> required>
                                        <div class="level-card-box">
                                            <div class="level-icon">
                                                <i class="fa-solid fa-award"></i>
                                            </div>
                                            <div class="level-title">มัธยมศึกษาตอนปลาย / ปวช.</div>
                                            <div class="level-desc">ระดับชั้น ม.4 - ม.6 และ ปวช.1 - ปวช.3 โรงเรียนพร้าววิทยาคม</div>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-feedback-error" id="levelError">
                                    <i class="fa-solid fa-circle-exclamation"></i> กรุณาเลือกระดับการแข่งขัน
                                </div>
                            </div>

                            <!-- ===================================================
                                 SECTION 02: ข้อมูลทีม
                                 =================================================== -->
                            <div class="form-section-card">
                                <div class="section-header">
                                    <div class="section-number">02</div>
                                    <div class="section-header-text">
                                        <h3>ข้อมูลทีม (Team Profile)</h3>
                                        <p>ระบุชื่อทีม อัปโหลดตราสัญลักษณ์ทีม และครูที่ปรึกษา</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- ชื่อทีม -->
                                    <div class="col-md-6">
                                        <label for="teamName" class="form-label">
                                            <i class="fa-solid fa-shield"></i> ชื่อทีม <span class="req-star">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="teamName" name="team_name" value="<?= e($_POST['team_name'] ?? '') ?>" placeholder="เช่น PWKS Phoenix, Fire Storm" required>
                                        <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรุณากรอกชื่อทีม</div>
                                    </div>

                                    <!-- ครูที่ปรึกษาทีม -->
                                    <div class="col-md-6">
                                        <label for="teacherAdvisor" class="form-label">
                                            <i class="fa-solid fa-chalkboard-user"></i> ชื่อ-นามสกุลครูที่ปรึกษาทีม <span class="req-star">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="teacherAdvisor" name="teacher_advisor" value="<?= e($_POST['teacher_advisor'] ?? '') ?>" placeholder="เช่น ครูสมชาย ใจดี" required>
                                        <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรุณากรอกชื่อ-นามสกุลครูที่ปรึกษาทีม</div>
                                    </div>

                                    <!-- โลโก้ทีม -->
                                    <div class="col-12 mt-3">
                                        <label class="form-label">
                                            <i class="fa-solid fa-image"></i> โลโก้ทีม (Team Logo)
                                        </label>
                                        <input type="file" id="teamLogoInput" name="team_logo" accept="image/png, image/jpeg, image/jpg, image/webp" class="d-none">
                                        
                                        <div class="logo-upload-dropzone" id="logoDropzone">
                                            <div class="fs-1 text-warning mb-2">
                                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                            </div>
                                            <h5 class="text-white mb-1">คลิกหรือลากไฟล์รูปภาพมาวางที่นี่</h5>
                                            <p class="text-secondary small mb-0">รองรับไฟล์ PNG, JPG, JPEG, WEBP (ขนาดไม่เกิน 5 MB)</p>
                                        </div>

                                        <!-- Logo Preview Box -->
                                        <div class="logo-preview-box" id="logoPreviewBox">
                                            <img id="logoPreviewImg" class="logo-preview-img" src="" alt="Team Logo Preview">
                                            <div class="logo-preview-info">
                                                <div id="logoPreviewName" class="logo-preview-name">logo.png</div>
                                                <div id="logoPreviewSize" class="logo-preview-size">120 KB</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeLogoBtn">
                                                <i class="fa-solid fa-trash-can"></i> เปลี่ยนรูปภาพ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Players 1 to 5 Sections -->
                            <?php for ($i = 1; $i <= 5; $i++): 
                                $is_sub = ($i === 5);
                                $p_title = $_POST["p{$i}_title"] ?? '';
                                $p_fname = $_POST["p{$i}_fname"] ?? '';
                                $p_lname = $_POST["p{$i}_lname"] ?? '';
                                $p_ign = $_POST["p{$i}_ign"] ?? '';
                                $p_class = $_POST["p{$i}_class"] ?? '';
                                $p_room = $_POST["p{$i}_room"] ?? '';
                                $p_num = $_POST["p{$i}_num"] ?? '';
                                $p_email = $_POST["p{$i}_email"] ?? '';
                                $p_phone = $_POST["p{$i}_phone"] ?? '';
                            ?>
                                <div class="form-section-card">
                                    <div class="section-header">
                                        <div class="section-number"><?= sprintf("%02d", $i + 2) ?></div>
                                        <div class="section-header-text">
                                            <h3>
                                                ผู้เล่นคนที่ <?= $i ?> 
                                                <?php if ($is_sub): ?>
                                                    <span class="player-badge-sub"><i class="fa-solid fa-user-plus"></i> ผู้เล่นสำรอง (ไม่บังคับ / มีหรือไม่มีก็ได้)</span>
                                                <?php elseif ($i === 1): ?>
                                                    <span class="player-badge-main"><i class="fa-solid fa-crown text-warning"></i> กัปตันทีม • ตัวจริง</span>
                                                <?php else: ?>
                                                    <span class="player-badge-main">ผู้เล่นตัวจริง</span>
                                                <?php endif; ?>
                                            </h3>
                                            <p><?= $is_sub ? 'ข้อมูลผู้เล่นสำรองประจำทีม (ไม่บังคับ สามารถเว้นว่างไว้ได้หากไม่มีตัวสำรอง)' : ($i === 1 ? 'ข้อมูลหัวหน้าทีมสำหรับการติดต่อและเข้ากลุ่มแข่งขัน' : "ข้อมูลผู้เล่นตัวจริงคนที่ {$i}") ?></p>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label">คำนำหน้า <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <select class="form-select" id="p<?= $i ?>_title" name="p<?= $i ?>_title" <?= $is_sub ? '' : 'required' ?>>
                                                <option value="">เลือก</option>
                                                <option value="เด็กชาย" <?= $p_title === 'เด็กชาย' ? 'selected' : '' ?>>เด็กชาย</option>
                                                <option value="เด็กหญิง" <?= $p_title === 'เด็กหญิง' ? 'selected' : '' ?>>เด็กหญิง</option>
                                                <option value="นาย" <?= $p_title === 'นาย' ? 'selected' : '' ?>>นาย</option>
                                                <option value="นางสาว" <?= $p_title === 'นางสาว' ? 'selected' : '' ?>>นางสาว</option>
                                            </select>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> เลือกคำนำหน้า</div>
                                        </div>
                                        <div class="col-md-5 col-sm-8">
                                            <label class="form-label">ชื่อ <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <input type="text" class="form-control" id="p<?= $i ?>_fname" name="p<?= $i ?>_fname" value="<?= e($p_fname) ?>" placeholder="ชื่อจริง" <?= $is_sub ? '' : 'required' ?>>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรุณากรอกชื่อ</div>
                                        </div>
                                        <div class="col-md-5 col-sm-12">
                                            <label class="form-label">นามสกุล <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <input type="text" class="form-control" id="p<?= $i ?>_lname" name="p<?= $i ?>_lname" value="<?= e($p_lname) ?>" placeholder="นามสกุล" <?= $is_sub ? '' : 'required' ?>>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรุณากรอกนามสกุล</div>
                                        </div>

                                        <!-- ชื่อในเกม (IGN) -->
                                        <div class="col-md-5 col-12">
                                            <label class="form-label">
                                                <i class="fa-solid fa-gamepad text-warning me-1"></i> ชื่อในเกม (IGN) <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?>
                                            </label>
                                            <input type="text" class="form-control" id="p<?= $i ?>_ign" name="p<?= $i ?>_ign" value="<?= e($p_ign) ?>" placeholder="เช่น ꧁༺SHADOW༻꧂, PWKS_Killer" <?= $is_sub ? '' : 'required' ?>>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรุณากรอกชื่อในเกม (IGN)</div>
                                        </div>

                                        <div class="col-md-3 col-4">
                                            <label class="form-label">ชั้น <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <select class="form-select player-class-select" id="p<?= $i ?>_class" name="p<?= $i ?>_class" data-prefill="<?= e($p_class) ?>" disabled <?= $is_sub ? '' : 'required' ?>>
                                                <option value="">-- เลือกระดับก่อน --</option>
                                            </select>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> เลือกชั้นเรียน</div>
                                        </div>
                                        <div class="col-md-2 col-4">
                                            <label class="form-label">ห้อง / ปี <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <select class="form-select" id="p<?= $i ?>_room" name="p<?= $i ?>_room" <?= $is_sub ? '' : 'required' ?>>
                                                <option value="">-- เลือก --</option>
                                                <?php for ($r = 1; $r <= 6; $r++): ?>
                                                    <option value="<?= $r ?>" <?= ($p_room == $r) ? 'selected' : '' ?>><?= $r ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> เลือกห้อง/ปี</div>
                                        </div>
                                        <div class="col-md-2 col-4">
                                            <label class="form-label">เลขที่ <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <input type="number" class="form-control" id="p<?= $i ?>_num" name="p<?= $i ?>_num" min="1" max="60" value="<?= e($p_num) ?>" placeholder="เช่น 15" <?= $is_sub ? '' : 'required' ?>>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรอกเลขที่</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">อีเมล <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <input type="email" class="form-control" id="p<?= $i ?>_email" name="p<?= $i ?>_email" value="<?= e($p_email) ?>" placeholder="example@email.com" <?= $is_sub ? '' : 'required' ?>>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรอกอีเมลที่ถูกต้อง</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">เบอร์โทรติดต่อ <?= $is_sub ? '<span class="text-secondary small fw-normal">(ไม่บังคับ)</span>' : '<span class="req-star">*</span>' ?></label>
                                            <input type="tel" class="form-control" id="p<?= $i ?>_phone" name="p<?= $i ?>_phone" value="<?= e($p_phone) ?>" placeholder="08xxxxxxxx (10 หลัก)" <?= $is_sub ? '' : 'required' ?>>
                                            <div class="form-feedback-error"><i class="fa-solid fa-circle-exclamation"></i> กรอกเบอร์โทร 10 หลัก</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>

                            <!-- ===================================================
                                 SECTION 08: ตรวจสอบและยืนยันข้อมูล
                                 =================================================== -->
                            <div class="form-section-card">
                                <div class="section-header">
                                    <div class="section-number">08</div>
                                    <div class="section-header-text">
                                        <h3>ตรวจสอบและยืนยันข้อมูล</h3>
                                        <p>สรุปข้อมูลที่กรอกทั้งหมดก่อนส่งใบสมัครเข้าร่วมการแข่งขัน</p>
                                    </div>
                                </div>

                                <!-- Live Summary Box -->
                                <div class="summary-card mb-4">
                                    <h5 class="text-warning mb-3 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-list-check"></i> ข้อมูลสรุปของทีม
                                    </h5>

                                    <div class="summary-item">
                                        <span class="summary-label">ระดับการแข่งขัน:</span>
                                        <span class="summary-value text-info" id="summaryLevel">ยังไม่ได้เลือก</span>
                                    </div>

                                    <div class="summary-item">
                                        <span class="summary-label">ชื่อทีม:</span>
                                        <span class="summary-value" id="summaryTeamName">—</span>
                                    </div>

                                    <div class="summary-item">
                                        <span class="summary-label">ครูที่ปรึกษา:</span>
                                        <span class="summary-value" id="summaryAdvisor">—</span>
                                    </div>

                                    <div class="pt-3">
                                        <div class="summary-label mb-2 fw-semibold text-white">รายชื่อสมาชิกในทีม:</div>
                                        <ul class="player-summary-list">
                                            <li id="summaryPlayer1">
                                                <span><strong>ผู้เล่น 1 (กัปตัน):</strong> (ยังไม่ได้กรอกข้อมูล)</span>
                                                <span class="badge bg-warning text-dark">—</span>
                                            </li>
                                            <li id="summaryPlayer2">
                                                <span><strong>ผู้เล่น 2:</strong> (ยังไม่ได้กรอกข้อมูล)</span>
                                                <span class="badge bg-warning text-dark">—</span>
                                            </li>
                                            <li id="summaryPlayer3">
                                                <span><strong>ผู้เล่น 3:</strong> (ยังไม่ได้กรอกข้อมูล)</span>
                                                <span class="badge bg-warning text-dark">—</span>
                                            </li>
                                            <li id="summaryPlayer4">
                                                <span><strong>ผู้เล่น 4:</strong> (ยังไม่ได้กรอกข้อมูล)</span>
                                                <span class="badge bg-warning text-dark">—</span>
                                            </li>
                                            <li id="summaryPlayer5">
                                                <span><strong>ผู้เล่นสำรอง:</strong> (ยังไม่ได้กรอกข้อมูล)</span>
                                                <span class="badge bg-secondary">—</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Confirmation Checkbox -->
                                <div class="custom-checkbox-wrapper">
                                    <input type="checkbox" id="confirmCheck" name="confirm_truth" value="1" required>
                                    <label for="confirmCheck" class="checkbox-label">
                                        ข้าพเจ้าตรวจสอบข้อมูลทั้งหมดแล้ว และยืนยันว่าข้อมูลที่กรอกเป็นความจริงทุกประการ สมาชิกทุกคนยินยอมปฏิบัติตามกฎกติกาการแข่งขัน Free Fire Esports ของโรงเรียนพร้าววิทยาคม ประจำปีการศึกษา <?= e($current_year) ?>
                                    </label>
                                </div>
                                <b style="color: yellow;">หากต้องการเปลี่ยนแปลงแก้ไขรายละเอียดทีม สามารถติดต่อได้ที่ ห้องพักครูหมวดคอมพิวเตอร์ 437 หรือ Admin: <a href="https://instagram.com/jeeraphxt51.gg" style="color: #fff;" target="_blank">Jeeraphat K. (SC67)</a></b>
                                <div class="form-feedback-error mt-2" id="confirmCheckError">
                                    <i class="fa-solid fa-circle-exclamation"></i> กรุณาทำเครื่องหมายยินยอมเพื่อยืนยันความถูกต้องของข้อมูล
                                </div>

                                <!-- Submit Button -->
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn-esports btn-esports-primary btn-lg w-100 py-3 fs-5">
                                        <i class="fa-solid fa-paper-plane"></i> ยืนยันการสมัครแข่งขัน
                                        <span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                                    </button>
                                </div>

                            </div>

                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </main>

        <!-- Real Database Success Alert via SweetAlert2 -->
        <?php if ($success_team): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '<span style="font-family: \'Chakra Petch\', sans-serif; color: #ff5500;"><b>สมัครการแข่งขันสำเร็จ!</b></span>',
                            html: `<div class="p-3 text-start rounded-3 bg-dark border border-warning border-opacity-25" style="color:#cbd5e1; font-family: 'Kanit', sans-serif;">
                                     <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                         <span class="text-secondary small">ชื่อทีม:</span>
                                         <span class="text-white fw-bold fs-5"><?= e($success_team['team_name']) ?></span>
                                     </div>
                                     <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                         <span class="text-secondary small">ระดับการแข่งขัน:</span>
                                         <span class="text-info fw-bold"><?= e($success_team['level']) ?></span>
                                     </div>
                                     <div class="d-flex justify-content-between mb-2">
                                         <span class="text-secondary small">ปีการศึกษา:</span>
                                         <span class="text-warning font-orbitron"><?= e($success_team['academic_year']) ?></span>
                                     </div>
                                     <div class="alert alert-success py-2 px-3 small mt-3 mb-0 border-0 bg-success bg-opacity-10 text-success text-center">
                                         <i class="fa-solid fa-circle-check me-1"></i> บันทึกข้อมูลเข้าสู่ระบบเรียบร้อยแล้ว
                                     </div>
                                     <p>อย่าลืมเข้า Discord!!!</p>
                                   </div>`,
                            background: '#0a0f24',
                            color: '#ffffff',
                            confirmButtonColor: '#ff5500',
                            confirmButtonText: '<i class="fa-solid fa-trophy me-1"></i> ดูสายการแข่งขัน',
                            showCancelButton: true,
                            cancelButtonText: '<i class="fa-solid fa-house me-1"></i> กลับหน้าแรก',
                            cancelButtonColor: '#334155',
                            reverseButtons: true,
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'bracket.php';
                            } else {
                                window.location.href = 'index.php';
                            }
                        });
                    }
                });
            </script>
        <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
