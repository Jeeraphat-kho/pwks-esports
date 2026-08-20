<?php
/**
 * FREE FIRE ESPORTS - Export Teams & Match Score Sheet (Excel / HTML)
 * Categorized by Junior (ม.ต้น) first, then Senior (ม.ปลาย / ปวช.)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$selected_year = $_GET['year'] ?? get_active_academic_year();
$format = $_GET['format'] ?? 'excel';

// 1. Fetch Junior Teams (มัธยมศึกษาตอนต้น)
$stmt_junior = $pdo->prepare("SELECT * FROM `freefire_teams` 
    WHERE `academic_year` = ? AND `level` = 'junior' AND `status` != 'rejected' 
    ORDER BY `id` ASC");
$stmt_junior->execute([$selected_year]);
$junior_teams = $stmt_junior->fetchAll();

// 2. Fetch Senior Teams (มัธยมศึกษาตอนปลาย / ปวช.)
$stmt_senior = $pdo->prepare("SELECT * FROM `freefire_teams` 
    WHERE `academic_year` = ? AND `level` = 'senior' AND `status` != 'rejected' 
    ORDER BY `id` ASC");
$stmt_senior->execute([$selected_year]);
$senior_teams = $stmt_senior->fetchAll();

// Helper to fetch players for a team
function get_team_players_for_export(PDO $pdo, int $team_id): array {
    $stmt = $pdo->prepare("SELECT * FROM `freefire_players` WHERE `team_id` = ? ORDER BY `player_order` ASC");
    $stmt->execute([$team_id]);
    return $stmt->fetchAll();
}

// If Excel format, send download headers with UTF-8 BOM
if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"freefire_teams_{$selected_year}.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Microsoft Excel Thai language compatibility
}
?>
<!DOCTYPE html>
<html lang="th" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>รายชื่อทีมและการแข่งขัน Free Fire Esports ประจำปีการศึกษา <?= htmlspecialchars($selected_year) ?></title>
    <!--[if gte mso 9]>
    <xml>
     <x:ExcelWorkbook>
      <x:ExcelWorksheets>
       <x:ExcelWorksheet>
        <x:Name>ทีมแข่ง FreeFire <?= htmlspecialchars($selected_year) ?></x:Name>
        <x:WorksheetOptions>
         <x:DisplayGridlines/>
        </x:WorksheetOptions>
       </x:ExcelWorksheet>
      </x:ExcelWorksheets>
     </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: 'TH SarabunPSK', 'TH Sarabun New', 'Cordia New', 'Tahoma', sans-serif;
            font-size: 16pt;
            color: #000000;
            padding: 20px;
        }
        h2 {
            font-family: 'TH SarabunPSK', 'TH Sarabun New', sans-serif;
            font-size: 20pt;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            color: #000000;
        }
        h3 {
            font-family: 'TH SarabunPSK', 'TH Sarabun New', sans-serif;
            font-size: 18pt;
            margin-top: 18px;
            margin-bottom: 6px;
            font-weight: bold;
            color: #000000;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 650px;
            margin-bottom: 15px;
            font-family: 'TH SarabunPSK', 'TH Sarabun New', sans-serif;
        }
        td, th {
            border: 1px solid #000000;
            padding: 4px 8px;
            font-size: 16pt;
            font-family: 'TH SarabunPSK', 'TH Sarabun New', sans-serif;
            vertical-align: middle;
        }
        .header-row td {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .lebron {
            font-weight: bold;
            background-color: #f9f9f9;
            padding: 6px 8px;
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .phone-cell {
            mso-number-format: "\@";
            text-align: left;
        }
    </style>
</head>
<body>

    <!-- ==========================================
         1. ระดับมัธยมศึกษาตอนต้น (ม.ต้น)
         ========================================== -->
    <h2>ระดับมัธยมศึกษาตอนต้น (ม.1 - ม.3)</h2>
    <?php if (empty($junior_teams)): ?>
        <p>— ไม่มีข้อมูลทีมลงทะเบียนในระดับมัธยมศึกษาตอนต้น —</p>
    <?php else: ?>
        <?php foreach ($junior_teams as $t): 
            $players = get_team_players_for_export($pdo, (int)$t['id']);
        ?>
            <h3>ทีม <?= htmlspecialchars($t['team_name']) ?></h3>
            <table border="1">
                <tr class="header-row">
                    <td class="text-center" style="width: 70px;">ที่</td>
                    <td class="text-left" style="width: 260px;">ชื่อ</td>
                    <td class="text-left" style="width: 100px;">ชั้น</td>
                    <td class="text-left" style="width: 140px;">เบอร์โทร</td>
                </tr>
                <?php 
                $p_idx = 0;
                foreach ($players as $p): 
                    $p_idx++;
                    $is_sub = ($p['player_type'] === 'substitute' || (int)$p['player_order'] === 5 || $p_idx === 5);
                    $no_label = $is_sub ? '5 (สำรอง)' : (string)$p['player_order'];
                    $title_prefix = !empty($p['title']) ? trim($p['title']) . ' ' : '';
                    $full_name = trim($title_prefix . $p['first_name'] . ' ' . $p['last_name']);
                    $class_text = format_player_class_room($p['class_level'], $p['room']);
                    $phone_text = trim($p['phone'] ?? '');
                ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars($no_label) ?></td>
                    <td class="text-left"><?= htmlspecialchars($full_name) ?></td>
                    <td class="text-left"><?= htmlspecialchars($class_text) ?></td>
                    <td class="phone-cell"><?= htmlspecialchars($phone_text) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="lebron">ผลการแข่งขัน จำนวน kill:[  ] อันดับทีม: [  ]</td>
                </tr>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>

    <br>

    <!-- ==========================================
         2. ระดับมัธยมศึกษาตอนปลาย / ปวช. (ม.ปลาย)
         ========================================== -->
    <h2>ระดับมัธยมศึกษาตอนปลาย / ปวช. (ม.4 - ม.6 / ปวช.1 - ปวช.3)</h2>
    <?php if (empty($senior_teams)): ?>
        <p>— ไม่มีข้อมูลทีมลงทะเบียนในระดับมัธยมศึกษาตอนปลาย / ปวช. —</p>
    <?php else: ?>
        <?php foreach ($senior_teams as $t): 
            $players = get_team_players_for_export($pdo, (int)$t['id']);
        ?>
            <h3>ทีม <?= htmlspecialchars($t['team_name']) ?></h3>
            <table border="1">
                <tr class="header-row">
                    <td class="text-center" style="width: 70px;">ที่</td>
                    <td class="text-left" style="width: 260px;">ชื่อ</td>
                    <td class="text-left" style="width: 100px;">ชั้น</td>
                    <td class="text-left" style="width: 140px;">เบอร์โทร</td>
                </tr>
                <?php 
                $p_idx = 0;
                foreach ($players as $p): 
                    $p_idx++;
                    $is_sub = ($p['player_type'] === 'substitute' || (int)$p['player_order'] === 5 || $p_idx === 5);
                    $no_label = $is_sub ? '5 (สำรอง)' : (string)$p['player_order'];
                    $title_prefix = !empty($p['title']) ? trim($p['title']) . ' ' : '';
                    $full_name = trim($title_prefix . $p['first_name'] . ' ' . $p['last_name']);
                    $class_text = format_player_class_room($p['class_level'], $p['room']);
                    $phone_text = trim($p['phone'] ?? '');
                ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars($no_label) ?></td>
                    <td class="text-left"><?= htmlspecialchars($full_name) ?></td>
                    <td class="text-left"><?= htmlspecialchars($class_text) ?></td>
                    <td class="phone-cell"><?= htmlspecialchars($phone_text) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="lebron">ผลการแข่งขัน จำนวน kill:[  ] อันดับทีม: [  ]</td>
                </tr>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
