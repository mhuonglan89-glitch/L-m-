<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the configuration file
require_once __DIR__ . '/../include/config.php';

// Define base URL if not already defined
if (!isset($base_url)) {
    $base_url = '/shop/php/';
}

// Check login status and role
$is_logged_in = $_SESSION['logged_in'] ?? false;
$is_admin = $is_logged_in && ($_SESSION['role'] ?? 'customer') === 'admin';
$username = $_SESSION['username'] ?? 'guest';

// ===================================================================
// 2. TÍNH SỐ LƯỢNG SẢN PHẨM TRONG GIỎ HÀNG (CHỈ DÀNH CHO KHÁCH HÀNG)
// ===================================================================
$cart_count = 0;

if (!$is_admin && $is_logged_in) {
    global $conn;

    // Kiểm tra kết nối DB còn sống
    if (isset($conn) && $conn->ping()) {
        $user_id = $_SESSION['user_id'];

        // Bước 1: Lấy customer_id từ user_id
        $stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $customer_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();

        if ($customer_id) {
            // Bước 2: Lấy cart_id của khách hàng
            $stmt = $conn->prepare("SELECT id FROM carts WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $cart = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($cart) {
                $cart_id = $cart['id'];

                // Bước 3: Tính tổng số lượng sản phẩm trong giỏ
                $stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE cart_id = ?");
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $cart_count = (int)($result['total'] ?? 0);
                $stmt->close();
            }
        }
    }
}
?>

<!-- =================================================================== -->
<!-- HEADER TOÀN BỘ (Fixed Top)                                          -->
<!-- =================================================================== -->
<div class="container-fluid fixed-top">

    <!-- ============================================================== -->
    <!-- TOP BAR (Chỉ hiển thị trên màn hình lớn)                      -->
    <!-- ============================================================== -->
    <div class="container topbar bg-primary d-none d-lg-block">
        <div class="d-flex justify-content-between">
            <div class="top-info ps-2">
                <small class="me-3">
                    <i class="fas fa-map-marker-alt me-2 text-secondary"></i>
                    <a href="#" class="text-white">470 Trần Đại Nghĩa, Đà Nẵng</a>
                </small>
                <small class="me-3">
                    <i class="fas fa-envelope me-2 text-secondary"></i>
                    <a href="mailto:fruitables@gmail.com" class="text-white">fruitables@gmail.com</a>
                </small>
            </div>
            <div class="top-link pe-2">
                <small class="text-white mx-2">
                    <i class="fas fa-apple-alt me-2"></i>Ăn tươi – Sống khỏe
                </small>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MAIN NAVIGATION BAR                                          -->
    <!-- ============================================================== -->
    <div class="container px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-xl">
            <!-- Logo -->
            <a href="<?php echo $base_url; ?>" class="navbar-brand">
                <h1 class="text-primary display-6">Fruitables</h1>
            </a>

            <!-- Nút toggle cho mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>

            <!-- Nội dung menu -->
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <!-- Menu chính (trái) -->
                <div class="navbar-nav mx-auto">
                    <a href="<?php echo $base_url; ?>" class="nav-item nav-link">
                        <i class="fas fa-home me-2"></i>Trang chủ
                    </a>
                    <a href="<?php echo $base_url; ?>cua-hang" class="nav-item nav-link">
                        <i class="fas fa-store me-2"></i>Cửa hàng
                    </a>
                    <a href="<?php echo $base_url; ?>lien-he" class="nav-item nav-link">
                        <i class="fas fa-envelope me-2"></i>Liên hệ
                    </a>

                    <!-- Link quản trị chỉ hiện với admin -->
                    <?php if ($is_admin): ?>
                        <a href="<?php echo $base_url; ?>quan-tri/danh-muc" class="nav-item nav-link text-danger fw-bold">
                            <i class="fas fa-cog me-2"></i>Quản trị
                        </a>
                    <?php endif; ?>
                </div>

                <!-- ============================================================== -->
                <!-- Khu vực hành động người dùng (giỏ hàng + tài khoản)          -->
                <!-- ============================================================== -->
                <div class="d-flex m-3 me-0 align-items-center">

                    <!-- Giỏ hàng (chỉ khách hàng thường) -->
                    <?php if ($is_logged_in && !$is_admin): ?>
                        <a href="<?php echo $base_url; ?>gio-hang" class="position-relative me-4 my-auto text-dark">
                            <i class="fas fa-shopping-bag fa-2x"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1"
                                    style="top: -5px; left: 20px; height: 20px; min-width: 20px; font-size: 12px;">
                                    <?= $cart_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Dropdown tài khoản -->
                    <?php if ($is_logged_in): ?>
                        <div class="dropdown">
                            <a href="#" class="dropdown-toggle text-dark d-flex align-items-center" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle fa-2x"></i>
                                <span class="ms-2 d-none d-lg-inline"><?= htmlspecialchars($username) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($is_admin): ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>quan-tri/danh-muc">
                                            <i class="fas fa-tachometer-alt me-2"></i>Bảng quản trị
                                        </a></li>
                                    <hr class="dropdown-divider">
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>tai-khoan">
                                            <i class="fas fa-user me-2"></i>Thông tin cá nhân
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>lich-su-mua-hang">
                                            <i class="fas fa-clipboard-list me-2"></i>Đơn hàng
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>gio-hang">
                                            <i class="fas fa-shopping-cart me-2"></i>Giỏ hàng
                                        </a></li>
                                    <hr class="dropdown-divider">
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="<?php echo $base_url; ?>dang-xuat">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Chưa đăng nhập -->
                        <a href="<?php echo $base_url; ?>dang-nhap" class="text-dark text-decoration-none">
                            <i class="fas fa-sign-in-alt fa-2x"></i>
                            <span class="ms-2 d-none d-lg-inline">Đăng nhập</span>
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </nav>
    </div>
</div>