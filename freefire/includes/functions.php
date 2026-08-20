<?php
/**
 * FREE FIRE ESPORTS - Helper Functions & Security Utilities
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape string for secure HTML output (XSS protection)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token for forms
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['ff_csrf_token'])) {
        $_SESSION['ff_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ff_csrf_token'];
}

/**
 * Render hidden CSRF token input field
 */
function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify submitted CSRF Token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['ff_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['ff_csrf_token'], $token);
}

/**
 * Set a flash notification message in session
 */
function set_flash(string $type, string $message): void {
    $_SESSION['ff_flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash notification
 */
function get_flash(): ?array {
    if (!empty($_SESSION['ff_flash'])) {
        $flash = $_SESSION['ff_flash'];
        unset($_SESSION['ff_flash']);
        return $flash;
    }
    return null;
}

/**
 * Render SweetAlert2 notification for flash message
 */
function render_flash(): string {
    $flash = get_flash();
    if (!$flash) return '';

    $type = $flash['type']; // 'success', 'danger', 'warning', 'info'
    $icon = match ($type) {
        'danger'  => 'error',
        'warning' => 'warning',
        'info'    => 'info',
        default   => 'success'
    };
    $title = match ($type) {
        'success' => 'สำเร็จเรียบร้อย!',
        'danger'  => 'เกิดข้อผิดพลาด!',
        'warning' => 'แจ้งเตือน',
        default   => 'แจ้งเพื่อทราบ'
    };

    $safe_msg = json_encode($flash['message'], JSON_UNESCAPED_UNICODE);
    $safe_title = json_encode($title, JSON_UNESCAPED_UNICODE);

    return "<script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '{$icon}',
                    title: {$safe_title},
                    text: {$safe_msg},
                    background: '#0a0f24',
                    color: '#ffffff',
                    confirmButtonColor: '#ff5500',
                    confirmButtonText: 'ตกลง',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>";
}

/**
 * Get setting value from freefire_settings table
 */
function get_setting(string $key, string $default = ''): string {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT `setting_value` FROM `freefire_settings` WHERE `setting_key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Update or insert setting value in freefire_settings
 */
function update_setting(string $key, string $value): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO `freefire_settings` (`setting_key`, `setting_value`) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = CURRENT_TIMESTAMP");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get currently active academic year
 */
function get_active_academic_year(): string {
    return get_setting('current_academic_year', '2569');
}

/**
 * Get list of all available academic years in the system
 */
function get_available_academic_years(): array {
    global $pdo;
    $years_from_settings = json_decode(get_setting('available_academic_years', '["2569", "2570"]'), true) ?: ['2569'];

    // Also merge distinct years from teams, rules, brackets
    try {
        $years_db = $pdo->query("SELECT DISTINCT `academic_year` FROM `freefire_teams`
                                  UNION SELECT DISTINCT `academic_year` FROM `freefire_rules`
                                  UNION SELECT DISTINCT `academic_year` FROM `freefire_brackets`")->fetchAll(PDO::FETCH_COLUMN);
        $all_years = array_unique(array_merge($years_from_settings, $years_db));
        rsort($all_years);
        return $all_years;
    } catch (Exception $e) {
        return $years_from_settings;
    }
}

/**
 * Generate unique Team Registration Code
 */
function generate_team_reg_code(string $level = 'senior'): string {
    global $pdo;
    $year = get_active_academic_year();
    $prefix = $level === 'junior' ? 'J' : 'S';
    
    // Find highest ID to make neat readable code e.g. FF-2569-S001
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `freefire_teams` WHERE `academic_year` = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn() + 1;

    $code = sprintf("FF-%s-%s%03d", $year, $prefix, $count);
    
    // Ensure uniqueness
    $check = $pdo->prepare("SELECT COUNT(*) FROM `freefire_teams` WHERE `reg_code` = ?");
    $check->execute([$code]);
    if ($check->fetchColumn() > 0) {
        $code = sprintf("FF-%s-%s%03d-%s", $year, $prefix, $count, substr(md5(uniqid()), 0, 3));
    }

    return $code;
}

/**
 * Handle secure team logo upload
 */
function upload_team_logo(array $file): ?string {
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    // 1. Verify file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('ขนาดไฟล์โลโก้ต้องไม่เกิน 5 MB');
    }

    // 2. Determine safe image extension via getimagesize or mime/filename
    $ext = null;
    $img_info = @getimagesize($file['tmp_name']);
    if ($img_info !== false) {
        $allowed_types = [
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF  => 'gif'
        ];
        if (isset($allowed_types[$img_info[2]])) {
            $ext = $allowed_types[$img_info[2]];
        }
    }

    if (!$ext && function_exists('mime_content_type')) {
        $mime = @mime_content_type($file['tmp_name']);
        $mime_map = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif'
        ];
        if (isset($mime_map[$mime])) {
            $ext = $mime_map[$mime];
        }
    }

    if (!$ext) {
        $orig_ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (in_array($orig_ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            $ext = ($orig_ext === 'jpeg') ? 'jpg' : $orig_ext;
        } else {
            throw new Exception('ไฟล์โลโก้ต้องเป็นรูปภาพประเภท PNG, JPG, JPEG หรือ WEBP เท่านั้น');
        }
    }

    $upload_dir = __DIR__ . '/../uploads/logos/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    // 3. Generate random safe filename
    $filename = 'logo_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $destination = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        if (!@copy($file['tmp_name'], $destination)) {
            throw new Exception('เกิดข้อผิดพลาดในการบันทึกไฟล์โลโก้ทีม');
        }
    }

    return 'uploads/logos/' . $filename;
}

/**
 * Safely delete team logo file from storage
 */
function delete_team_logo(?string $logo_path): bool {
    if (empty($logo_path)) {
        return false;
    }
    
    // Normalize path to prevent path traversal
    $clean_filename = basename($logo_path);
    $full_path = __DIR__ . '/../uploads/logos/' . $clean_filename;
    
    if (file_exists($full_path) && is_file($full_path)) {
        return @unlink($full_path);
    }
    
    return false;
}

/**
 * HTML Sanitizer for Summernote content (Allows safe formatting, strips dangerous scripts)
 */
function sanitize_html(?string $html): string {
    if (!$html) return '';

    // Remove script tags and contents
    $clean = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
    // Remove iframe tags unless from approved sources (e.g. challonge, youtube, maps)
    // Remove event handlers like onclick, onload, onerror
    $clean = preg_replace('#\s*on\w+\s*=\s*(["\'])(.*?)\1#is', '', $clean);
    $clean = preg_replace('#\s*on\w+\s*=\s*[^ >]+#is', '', $clean);
    // Remove javascript: pseudo-protocol
    $clean = preg_replace('#href\s*=\s*(["\'])javascript:(.*?)\1#is', 'href="#"', $clean);

    return $clean;
}

/**
 * Format competition level for display
 */
function format_level(string $level): string {
    return match ($level) {
        'junior' => 'มัธยมศึกษาตอนต้น (ม.1 - ม.3)',
        'senior' => 'มัธยมศึกษาตอนปลาย / ปวช. (ม.4 - ม.6 / ปวช.1 - ปวช.3)',
        default  => 'ทุกระดับชั้น / รวม'
    };
}

/**
 * Format registration status badge
 */
function format_status_badge(string $status = 'approved'): string {
    return match ($status) {
        'rejected' => '<span class="badge bg-danger bg-opacity-75 border border-danger"><i class="fa-solid fa-circle-xmark me-1"></i> ยกเลิก/ตัดสิทธิ์</span>',
        default    => '<span class="badge bg-success bg-opacity-75 border border-success"><i class="fa-solid fa-circle-check me-1"></i> ลงทะเบียนเรียบร้อย</span>'
    };
}

/**
 * Format player class and room text (e.g. ม.5/2 or ปวช. 1)
 */
function format_player_class_room(?string $class_level, ?string $room): string {
    $class_level = trim($class_level ?? '');
    $room = trim($room ?? '');
    if (empty($class_level) && empty($room)) return '—';
    if ($class_level === 'ปวช.') {
        return !empty($room) ? "ปวช. {$room}" : "ปวช.";
    }
    return !empty($room) ? "{$class_level}/{$room}" : $class_level;
}
