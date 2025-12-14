<?php
/* ==========================================================================
   LOGOUT SCRIPT - Đăng xuất người dùng hoàn toàn
   - Xóa toàn bộ dữ liệu session
   - Xóa cookie nhớ đăng nhập (nếu có)
   - Hủy session
   - Chuyển hướng về trang chủ với thông báo thành công
   ========================================================================== */

session_start();                                    // Bắt đầu session để có thể thao tác với $_SESSION

// === 1. XÓA TOÀN BỘ DỮ LIỆU SESSION ===
$_SESSION = array();                                // Đặt lại mảng session về rỗng

// === 2. XÓA COOKIE "NHỚ ĐĂNG NHẬP" NẾU TỒN TẠI ===
if (isset($_COOKIE['remember_user'])) {
    // Đặt thời gian hết hạn về quá khứ để xóa cookie
    setcookie('remember_user', '', time() - 3600, "/");
    // Có thể thêm các cookie khác cần xóa ở đây nếu dự án sử dụng nhiều cookie nhớ đăng nhập
}

// === 3. HỦY HOÀN TOÀN SESSION ===
session_destroy();                                  // Xóa session khỏi server

// === 4. CHUYỂN HƯỚNG VỀ TRANG CHỦ VỚI THAM SỐ THÔNG BÁO ===
header("Location: index.php?logout=success");
exit();                                             // Dừng script ngay lập tức sau khi redirect