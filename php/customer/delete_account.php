<?php
// ================================================================
// FILE: delete_account.php
// MÔ TẢ: Xử lý yêu cầu XÓA TÀI KHOẢN NGƯỜI DÙNG (khách hàng)
// Người dùng phải đăng nhập và có role 'customer' mới được thực hiện
// ================================================================

// ----------------------------------------------------------------
// 1. Khởi tạo và kiểm tra phiên đăng nhập
// ----------------------------------------------------------------
require_once '../include/config.php';

// Kiểm tra quyền truy cập: chỉ khách hàng mới được vào
require_role(['customer']);

// Nếu chưa đăng nhập → chuyển hướng về trang login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: " . SITE_URL . "dang-nhap");
    exit();
}

// Lấy ID người dùng hiện tại từ session (đảm bảo đã được set khi login)
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    // Trường hợp hiếm: session bị lỗi, không có user_id
    session_destroy();
    header("Location: " . SITE_URL . "dang-nhap?error=session");
    exit();
}

// ----------------------------------------------------------------
// 2. Kết nối CSDL và chuẩn bị câu lệnh an toàn (Prepared Statement)
// ----------------------------------------------------------------
$conn = $GLOBALS['conn']; // config.php đã tạo biến $conn toàn cục

// Bắt đầu transaction để đảm bảo xóa đồng bộ cả 2 bảng
mysqli_begin_transaction($conn);

try {
    // Xóa dữ liệu trong bảng customers trước (do có khóa ngoại tham chiếu đến users)
    $sql1 = "DELETE FROM customers WHERE user_id = ?";
    $stmt1 = mysqli_prepare($conn, $sql1);
    mysqli_stmt_bind_param($stmt1, "i", $user_id);
    mysqli_stmt_execute($stmt1);

    // Xóa tài khoản trong bảng users
    $sql2 = "DELETE FROM users WHERE id = ?";
    $stmt2 = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt2, "i", $user_id);
    mysqli_stmt_execute($stmt2);

    // Nếu mọi thứ thành công → commit transaction
    mysqli_commit($conn);

    // Đóng các statement
    mysqli_stmt_close($stmt1);
    mysqli_stmt_close($stmt2);

} catch (Exception $e) {
    // Nếu có lỗi → rollback, không thay đổi dữ liệu
    mysqli_rollback($conn);
    
    // Ghi log lỗi (tùy chọn)
    error_log("Lỗi xóa tài khoản user_id=$user_id: " . $e->getMessage());
    
    // Chuyển hướng với thông báo lỗi
    header("Location: " . SITE_URL . "tai-khoan?delete_error=1");
    exit();
}

// ----------------------------------------------------------------
// 3. Hủy session và chuyển hướng sau khi xóa thành công
// ----------------------------------------------------------------
session_destroy();                           // Hủy toàn bộ session
header("Location: " . SITE_URL . "?deleted=1");
exit();