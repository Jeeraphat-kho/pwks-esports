<?php
/**
 * FREE FIRE ESPORTS - Database Connection & Initialization
 * Target Database: pwks_esports
 * Table Prefix: freefire_
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = empty($http_host) || in_array($http_host, ['localhost', '127.0.0.1', 'localhost:80', 'localhost:8888']) || (php_sapi_name() === 'cli');

if ($is_local) {
    // 1. Local Development (MAMP / XAMPP on Windows/Mac)
    $db_configs = [
        ['host' => 'localhost', 'port' => '3306', 'user' => 'root', 'pass' => 'root', 'name' => 'pwks_esports'],
        ['host' => '127.0.0.1', 'port' => '3306', 'user' => 'root', 'pass' => 'root', 'name' => 'pwks_esports'],
        ['host' => 'localhost', 'port' => '3306', 'user' => 'root', 'pass' => '',     'name' => 'pwks_esports'],
        ['host' => '127.0.0.1', 'port' => '3306', 'user' => 'root', 'pass' => '',     'name' => 'pwks_esports'],
        ['host' => 'localhost', 'port' => '3306', 'user' => 'pwks_jeeraphat', 'pass' => 'jeeraphat12345', 'name' => 'pwks_esport'],
    ];
} else {
    // 2. Production Web Hosting (cPanel / DirectAdmin / Live Host)
    $db_configs = [
        ['host' => 'localhost', 'port' => '3306', 'user' => 'pwks_jeeraphat', 'pass' => 'jeeraphat12345', 'name' => 'pwks_esport'],
        ['host' => '127.0.0.1', 'port' => '3306', 'user' => 'pwks_jeeraphat', 'pass' => 'jeeraphat12345', 'name' => 'pwks_esport'],
        ['host' => 'localhost', 'port' => '3306', 'user' => 'root',           'pass' => 'root',           'name' => 'pwks_esports'],
    ];
}

$pdo = null;
$last_db_error = null;

foreach ($db_configs as $cfg) {
    try {
        $port_str = !empty($cfg['port']) ? ";port={$cfg['port']}" : "";
        $dsn = "mysql:host={$cfg['host']}{$port_str};dbname={$cfg['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 2,
        ]);
        break;
    } catch (PDOException $e) {
        $last_db_error = $e->getMessage();
        // If DB does not exist on local, try to create it
        if ($is_local) {
            try {
                $dsn_srv = "mysql:host={$cfg['host']}{$port_str};charset=utf8mb4";
                $pdo_srv = new PDO($dsn_srv, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
                $pdo_srv->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                break;
            } catch (PDOException $ex) {
                $last_db_error = $ex->getMessage();
                continue;
            }
        }
    }
}

if (!$pdo) {
    die("<div style='background:#070b19;color:#ff5500;padding:40px;font-family:sans-serif;text-align:center;border-radius:16px;margin:60px auto;max-width:650px;border:1px solid rgba(255,85,0,0.5);box-shadow:0 20px 50px rgba(0,0,0,0.8);'>
            <h2 style='margin-bottom:12px;font-size:24px;'>⚠️ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
            <p style='color:#94a3b8;font-size:14px;margin-bottom:0;'>Database Error: " . htmlspecialchars($last_db_error ?? 'Unknown Error') . "</p>
         </div>");
}

initializeFreeFireTables($pdo);

/**
 * Creates all freefire_* tables and initial seed data if not present
 */
function initializeFreeFireTables(PDO $pdo) {
    // 1. Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `freefire_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) NOT NULL UNIQUE,
        `setting_value` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `freefire_admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `role` VARCHAR(20) DEFAULT 'admin',
        `last_login` DATETIME NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3. Teams Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `freefire_teams` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `academic_year` VARCHAR(10) NOT NULL DEFAULT '2569',
        `reg_code` VARCHAR(30) NULL,
        `team_name` VARCHAR(100) NOT NULL,
        `school_name` VARCHAR(150) NULL,
        `level` ENUM('junior', 'senior') NOT NULL,
        `teacher_advisor` VARCHAR(100) NOT NULL,
        `team_logo` VARCHAR(255) NULL,
        `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
        `admin_notes` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_year_level_status (`academic_year`, `level`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 4. Players Table (4 Main + 1 Optional Substitute)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `freefire_players` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `team_id` INT NOT NULL,
        `player_type` ENUM('main', 'substitute') NOT NULL DEFAULT 'main',
        `player_order` TINYINT NOT NULL,
        `title` VARCHAR(20) NULL,
        `first_name` VARCHAR(100) NULL,
        `last_name` VARCHAR(100) NULL,
        `ign` VARCHAR(100) NULL,
        `class_level` VARCHAR(10) NULL,
        `room` VARCHAR(10) NULL,
        `student_no` INT NULL,
        `email` VARCHAR(150) NULL,
        `phone` VARCHAR(30) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`team_id`) REFERENCES `freefire_teams`(`id`) ON DELETE CASCADE,
        INDEX idx_team_player (`team_id`, `player_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 5. Rules Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `freefire_rules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `academic_year` VARCHAR(10) NOT NULL DEFAULT '2569',
        `title` VARCHAR(255) NOT NULL,
        `content` LONGTEXT NOT NULL,
        `sort_order` INT NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rules_year_sort (`academic_year`, `is_active`, `sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 6. Brackets Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `freefire_brackets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `academic_year` VARCHAR(10) NOT NULL DEFAULT '2569',
        `title` VARCHAR(255) NOT NULL,
        `category` VARCHAR(50) NOT NULL DEFAULT 'all',
        `embed_url` TEXT NOT NULL,
        `description` TEXT NULL,
        `sort_order` INT NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_brackets_year_sort (`academic_year`, `is_active`, `sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Seed default settings if empty
    $check_settings = $pdo->query("SELECT COUNT(*) FROM `freefire_settings`")->fetchColumn();
    if ($check_settings == 0) {
        $default_settings = [
            ['current_academic_year', '2569'],
            ['competition_name', 'FREE FIRE ESPORTS TOURNAMENT โรงเรียนพร้าววิทยาคม'],
            ['registration_status', 'open'],
            ['registration_start_date', date('Y-m-d')],
            ['registration_end_date', date('Y-m-d', strtotime('+30 days'))],
            ['school_name', 'โรงเรียนพร้าววิทยาคม'],
            ['available_academic_years', json_encode(['2569', '2570'])]
        ];
        $stmt_set = $pdo->prepare("INSERT INTO `freefire_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
        foreach ($default_settings as $s) {
            $stmt_set->execute([$s[0], $s[1]]);
        }
    }

    // Seed default admin if empty (admin / admin1234)
    $check_admin = $pdo->query("SELECT COUNT(*) FROM `freefire_admins`")->fetchColumn();
    if ($check_admin == 0) {
        $admin_pass_hash = password_hash('admin1234', PASSWORD_DEFAULT);
        $stmt_adm = $pdo->prepare("INSERT INTO `freefire_admins` (`username`, `password_hash`, `name`, `role`) VALUES (?, ?, ?, ?)");
        $stmt_adm->execute(['admin', $admin_pass_hash, 'ผู้ดูแลระบบ Esports', 'superadmin']);
    }

    // Seed initial rules for 2569 if empty
    $check_rules = $pdo->query("SELECT COUNT(*) FROM `freefire_rules` WHERE `academic_year` = '2569'")->fetchColumn();
    if ($check_rules == 0) {
        $initial_rules = [
            [
                'title' => 'คุณสมบัติผู้เข้าแข่งขัน (Player Eligibility)',
                'content' => '<ul><li>ต้องเป็นนักเรียนที่กำลังศึกษาอยู่ในโรงเรียนพร้าววิทยาคม ประจำปีการศึกษาปัจจุบัน</li><li>สมาชิกภายในทีมทุกคนต้องอยู่ในระดับชั้นเดียวกันตามที่เลือกลงสมัคร (ระดับมัธยมศึกษาตอนต้น ม.1 - ม.3 หรือ มัธยมศึกษาตอนปลาย ม.4 - ม.6)</li><li>ผู้เล่นแต่ละคนสามารถลงสมัครได้เพียง 1 ทีมเท่านั้น ไม่อนุญาตให้ลงซ้ำทีมหรือเล่นแทนผู้อื่น</li><li>แต่ละทีมต้องมีครูที่ปรึกษาประจำทีมอย่างน้อย 1 ท่าน เพื่อการประสานงานและดูแลความเรียบร้อย</li></ul>',
                'sort_order' => 1
            ],
            [
                'title' => 'การสมัครแข่งขัน (Registration)',
                'content' => '<ul><li>กรอกแบบฟอร์มรับสมัครผ่านเว็บไซต์ระบบ Free Fire Esports ของโรงเรียนพร้าววิทยาคมให้ครบถ้วน</li><li>ข้อมูลชื่อ-นามสกุล ชื่อในเกม (IGN) เลขที่ ชั้น ห้อง อีเมล และเบอร์โทรศัพท์ ต้องเป็นข้อมูลจริงที่สามารถติดต่อได้</li><li>การตั้งชื่อทีมและชื่อในเกม (IGN) ต้องไม่มีคำหยาบคาย ส่อเสียด หรือขัดต่อศีลธรรมอันดี</li><li>การสมัครจะถือว่าเสร็จสมบูรณ์เมื่อกัปตันทีมได้รับรหัสการสมัครและได้รับการอนุมัติจากฝ่ายจัดการแข่งขัน</li></ul>',
                'sort_order' => 2
            ],
            [
                'title' => 'รูปแบบการแข่งขันและการคิดคะแนน (Tournament Format & Scoring)',
                'content' => '<ul><li><strong>โหมดการแข่งขัน:</strong> Battle Royale (Squad 4 คน) ใน Custom Room</li><li><strong>แผนที่ที่ใช้:</strong> Bermuda, Purgatory, Alpine หรือตามที่กรรมการกำหนด</li><li><strong>ระบบการคิดคะแนน (Standard Esports Scoring):</strong><br>อันดับที่ 1 (Booyah): 12 คะแนน | อันดับที่ 2: 9 คะแนน | อันดับที่ 3: 8 คะแนน | อันดับที่ 4: 7 คะแนน | คะแนน Kill: 1 คะแนน / Kill</li><li>ทีมที่ได้คะแนนรวมสูงสุดในแต่ละรอบจะผ่านเข้าสู่รอบชิงชนะเลิศ (Grand Final)</li></ul>',
                'sort_order' => 3
            ],
            [
                'title' => 'กติกาและข้อห้ามภายในเกม (In-Game Rules)',
                'content' => '<ul><li><strong>อุปกรณ์ที่ใช้:</strong> อนุญาตให้ใช้เฉพาะสมาร์ตโฟน (Mobile Phone) หรือแท็บเล็ต (Tablet) เท่านั้น</li><li><strong>ข้อห้ามเด็ดขาด:</strong> ไม่อนุญาตให้ใช้โปรแกรมจำลองคอมพิวเตอร์ (Emulator เช่น BlueStacks, Nox, LDPlayer) หรืออุปกรณ์เสริมช่วยกดใด ๆ ทั้งสิ้น</li><li>ห้ามใช้โปรแกรมโกง (Hacks / Cheats / Mod APK / Scripts) หรือการดัดแปลงไฟล์เกมโดยเด็ดขาด</li><li>ห้ามเจตนาใช้ประโยชน์จากข้อผิดพลาดของเกม (Bug Abuse / Glitch) ในการแข่งขัน</li><li>ห้ามสมรู้ร่วมคิดหรือแกล้งยอมแพ้ระหว่างทีม (Teaming) โดยเด็ดขาด</li></ul>',
                'sort_order' => 4
            ],
            [
                'title' => 'กำหนดการและการรายงานตัว (Match Day & Check-in)',
                'content' => '<ul><li>กัปตันทีมต้องเข้ารายงานตัวในกลุ่มประสานงานการแข่งขันก่อนเวลาเริ่มแมตช์อย่างน้อย 20 นาที</li><li>ฝ่ายจัดการแข่งขันจะส่งหมายเลขห้อง (Room ID) และรหัสผ่าน (Password) ให้กัปตันทีม</li><li>สมาชิกทุกคนต้องเข้าห้องแข่งขันและนั่งในช่อง (Slot) ประจำทีมของตนเองให้เสร็จสิ้นก่อนเริ่มแข่งขัน 5 นาที</li><li>หากทีมใดมาไม่ทันเวลาเริ่มแข่งขันเกิน 10 นาที กรรมการจะเริ่มเกมทันทีโดยไม่มีการรอ และทีมดังกล่าวจะไม่ได้คะแนนในรอบนั้น</li></ul>',
                'sort_order' => 5
            ],
            [
                'title' => 'การเปลี่ยนตัวผู้เล่น (Substitutions)',
                'content' => '<ul><li>อนุญาตให้เปลี่ยนตัวผู้เล่นสำรองที่ลงทะเบียนไว้ในระบบล่วงหน้าเท่านั้น</li><li>ต้องแจ้งกรรมการจัดการแข่งขันก่อนเริ่มรอบการแข่งขันอย่างน้อย 15 นาที</li><li>ไม่อนุญาตให้นำบุคคลภายนอกที่ไม่มีรายชื่อในใบสมัครลงทำการแข่งขันโดยเด็ดขาด</li></ul>',
                'sort_order' => 6
            ],
            [
                'title' => 'การประท้วงและการตัดสิน (Disputes & Referees)',
                'content' => '<ul><li>หากต้องการประท้วงผลการแข่งขันหรือการกระทำผิดกติกา กัปตันทีมต้องยื่นเรื่องพร้อมหลักฐานภาพถ่ายหรือวิดีโอคลิป ภายใน 15 นาทีหลังจบแมตช์</li><li>คณะกรรมการจะตรวจสอบหลักฐานอย่างละเอียดและแจ้งผลการพิจารณาให้ทราบ</li><li>การตัดสินของคณะกรรมการจัดการแข่งขันโรงเรียนพร้าววิทยาคมถือเป็นที่สิ้นสุด</li></ul>',
                'sort_order' => 7
            ],
            [
                'title' => 'บทลงโทษและการตัดสิทธิ์ (Penalties & Disqualification)',
                'content' => '<ul><li><strong>ตักเตือนและตัดคะแนน:</strong> สำหรับพฤติกรรมไม่เหมาะสม การมาสาย หรือการใช้วาจาไม่สุภาพ</li><li><strong>ปรับแพ้ในแมตช์:</strong> สำหรับกรณีสมาชิกไม่พร้อม หรือไม่ปฏิบัติตามคำสั่งของกรรมการ</li><li><strong>ตัดสิทธิ์ออกจากการแข่งขัน (DQ):</strong> หากพบการใช้โปรแกรมโกง Emulator การสวมสิทธิ์ผู้เล่น หรือการล้มมวย พร้อมรายงานฝ่ายปกครองของโรงเรียนเพื่อพิจารณาโทษตามระเบียบโรงเรียนต่อไป</li></ul>',
                'sort_order' => 8
            ],
            [
                'title' => 'ข้อกำหนดอื่น ๆ และช่องทางการติดต่อ (Additional Terms & Support)',
                'content' => '<ul><li>ฝ่ายจัดการแข่งขันขอสงวนสิทธิ์ในการปรับเปลี่ยนรายละเอียดกติกาตามความเหมาะสม เพื่อให้การแข่งขันดำเนินไปอย่างยุติธรรมและราบรื่น</li><li>หากมีข้อสงสัยหรือต้องการสอบถามข้อมูลเพิ่มเติม สามารถติดต่อคณะกรรมการจัดการแข่งขันได้ที่ ศูนย์เทคโนโลยีสารสนเทศ โรงเรียนพร้าววิทยาคม หรือ Discord ของการแข่งขัน</li></ul>',
                'sort_order' => 9
            ]
        ];

        $stmt_r = $pdo->prepare("INSERT INTO `freefire_rules` (`academic_year`, `title`, `content`, `sort_order`, `is_active`) VALUES ('2569', ?, ?, ?, 1)");
        foreach ($initial_rules as $r) {
            $stmt_r->execute([$r['title'], $r['content'], $r['sort_order']]);
        }
    }

    // Seed initial brackets for 2569 if empty
    $check_brackets = $pdo->query("SELECT COUNT(*) FROM `freefire_brackets` WHERE `academic_year` = '2569'")->fetchColumn();
    if ($check_brackets == 0) {
        $initial_brackets = [
            [
                'title' => 'สายการแข่งขันระดับมัธยมศึกษาตอนปลาย',
                'category' => 'senior',
                'embed_url' => 'https://challonge.com/tournament/bracket_generator',
                'description' => 'ตารางและสายการแข่งขันรอบคัดเลือกและรอบชิงชนะเลิศ ระดับ ม.4 - ม.6',
                'sort_order' => 1
            ],
            [
                'title' => 'สายการแข่งขันระดับมัธยมศึกษาตอนต้น',
                'category' => 'junior',
                'embed_url' => 'https://challonge.com/tournament/bracket_generator',
                'description' => 'ตารางและสายการแข่งขันรอบคัดเลือกและรอบชิงชนะเลิศ ระดับ ม.1 - ม.3',
                'sort_order' => 2
            ]
        ];

        $stmt_b = $pdo->prepare("INSERT INTO `freefire_brackets` (`academic_year`, `title`, `category`, `embed_url`, `description`, `sort_order`, `is_active`) VALUES ('2569', ?, ?, ?, ?, ?, 1)");
        foreach ($initial_brackets as $b) {
            $stmt_b->execute([$b['title'], $b['category'], $b['embed_url'], $b['description'], $b['sort_order']]);
        }
    }
}
