<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===================================================================
// CẤU HÌNH CƠ BẢN
// ===================================================================
// Thiết lập base URL
$base_url = '/shop/php/';
define('BASE_URL', $base_url);
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . $base_url);

// Kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'fruitables_shop';
$username = 'root';
$password = '';
// ... rest of your config.php file ...

try {
    $conn = new mysqli($host, $username, $password, $dbname);

    // Kiểm tra lỗi kết nối
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối database: " . $conn->connect_error);
    }

    // Thiết lập charset để hỗ trợ tiếng Việt và emoji
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Chỉ hiển thị lỗi chi tiết trên các trang đăng nhập/đăng ký/404
    $safe_pages = ['login.php', 'register.php', '404.php'];
    if (!in_array(basename($_SERVER['PHP_SELF']), $safe_pages)) {
        http_response_code(500);
        die("Lỗi Hệ thống: Không thể kết nối cơ sở dữ liệu.");
    }
}
?>

<?php
/* ===================================================================
   HÀM PHÂN QUYỀN TRUY CẬP THEO VAI TRÒ (ROLE-BASED ACCESS CONTROL)
   =================================================================== */
function require_role(array $allowed_roles = [])
{
    $current_role = $_SESSION['role'] ?? 'guest';

    // Nếu không có role nào được phép hoặc người dùng không nằm trong danh sách (trừ admin)
    if (!empty($allowed_roles) && !in_array($current_role, $allowed_roles) && $current_role !== 'admin') {

        $current_page = basename($_SERVER['PHP_SELF']);
        $public_pages = ['login.php', 'register.php', '404.php'];

        // Không chặn trên các trang công khai
        if (in_array($current_page, $public_pages)) {
            return;
        }

        // Chưa đăng nhập → chuyển về trang login
        if ($current_role === 'guest') {
            header("Location: /login"); // Đã sửa: ../login.php -> /login
            exit;
        }

        // Đã đăng nhập nhưng không đủ quyền → về trang chủ
        header("Location: /"); // Đã sửa: ../index.php -> /
        exit;
    }
}
?>


<?php
/* ===================================================================
   HÀM NHÚNG HEADER & FOOTER (TÁI SỬ DỤNG GIAO DIỆN)
   =================================================================== */
function include_header()
{
    global $conn;
    include __DIR__ . '/../partials/header.php';
}

function include_footer()
{
    global $conn, $is_logged_in, $is_admin;
    include __DIR__ . '/../partials/footer.php';
}
?>


<?php
/* ===================================================================
   HỆ THỐNG FLASH MESSAGE (THÔNG BÁO MỘT LẦN)
   Dùng để hiển thị thành công, lỗi, cảnh báo sau khi redirect
   =================================================================== */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type'    => $type,      // success | danger | warning | info
        'message' => $message
    ];
}

function getFlash(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['flash_message'])) {
        return '';
    }

    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    $type    = htmlspecialchars($flash['type']);
    $message = $flash['message'];

    return <<<HTML
    <div class="alert alert-{$type} alert-dismissible fade show" role="alert">
        {$message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    HTML;
}
?>