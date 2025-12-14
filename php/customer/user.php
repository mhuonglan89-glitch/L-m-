<?php
require_once '../include/config.php';
require_role(['customer']);

$user_id = $_SESSION['user_id'];

/*=======================================
    1. LẤY THÔNG TIN NGƯỜI DÙNG HIỆN TẠI
=======================================*/
$query = "
    SELECT u.username, u.email, c.phone, c.address 
    FROM users u 
    LEFT JOIN customers c ON u.id = c.user_id 
    WHERE u.id = ? 
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$success = $error = '';

/*=======================================
    2. XỬ LÝ CẬP NHẬT THÔNG TIN (POST)
=======================================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email   = trim($_POST['email'] ?? '');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } else {
        // Cập nhật email trong bảng users
        $upd_user = mysqli_prepare($conn, "UPDATE users SET email = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd_user, "si", $email, $user_id);
        mysqli_stmt_execute($upd_user);

        // Kiểm tra xem khách hàng đã có bản ghi trong bảng customers chưa
        $check = mysqli_query($conn, "SELECT id FROM customers WHERE user_id = '$user_id'");

        if (mysqli_num_rows($check) > 0) {
            // Đã tồn tại → Cập nhật
            $stmt_cust = mysqli_prepare($conn, "UPDATE customers SET phone = ?, address = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt_cust, "ssi", $phone, $address, $user_id);
            mysqli_stmt_execute($stmt_cust);
        } else {
            // Chưa tồn tại → Thêm mới
            $stmt_cust = mysqli_prepare($conn, "INSERT INTO customers (user_id, phone, address) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_cust, "iss", $user_id, $phone, $address);
            mysqli_stmt_execute($stmt_cust);
        }

        // Cập nhật lại dữ liệu hiển thị + thông báo
        $success = "Cập nhật thông tin thành công!";
        $user['email']   = $email;
        $user['phone']   = $phone;
        $user['address'] = $address;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Thông tin tài khoản - Fruitables</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include_header(); ?>

    <!--=======================================
        MODAL TÌM KIẾM
    =======================================-->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title">Tìm kiếm sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div class="input-group w-75 mx-auto">
                        <input type="search" class="form-control p-3" placeholder="Nhập từ khóa...">
                        <button class="input-group-text p-3 btn-primary"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--=======================================
        TIÊU ĐỀ TRANG
    =======================================-->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Thông tin tài khoản</h1>
    </div>

    <!--=======================================
        NỘI DUNG CHÍNH
    =======================================-->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-12">

                    <!-- Tab điều hướng tài khoản -->
                    <ul class="nav nav-tabs mb-5">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= BASE_URL ?>tai-khoan">
                                <i class='bx bx-user me-2'></i>Thông tin tài khoản
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>doi-mat-khau">
                                <i class='bx bx-lock-alt me-2'></i>Đổi mật khẩu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>lich-su-mua-hang">
                                <i class='bx bx-history me-2'></i>Lịch sử đơn hàng
                            </a>
                        </li>
                    </ul>

                    <!-- Thông báo kết quả -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form chỉnh sửa thông tin -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">
                            <form method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="row g-4">

                                    <!-- Tên đăng nhập (không chỉnh sửa được) -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tên đăng nhập</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                        <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>

                                    <!-- Số điện thoại -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Số điện thoại</label>
                                        <input type="text" name="phone" class="form-control"
                                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                            placeholder="Ví dụ: 0905123456">
                                    </div>

                                    <!-- Địa chỉ giao hàng -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Địa chỉ giao hàng</label>
                                        <input type="text" name="address" class="form-control"
                                            value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                                            placeholder="Ví dụ: 470 Trần Đại Nghĩa, Ngũ Hành Sơn, Đà Nẵng">
                                    </div>

                                </div>

                                <!-- Nút hành động -->
                                <div class="mt-5 d-flex flex-wrap gap-3">
                                    <button type="submit" class="btn btn-primary rounded-pill py-3 px-5">
                                        <i class="fa fa-save me-2"></i> Lưu thay đổi
                                    </button>
                                    <button type="button" class="btn btn-outline-danger rounded-pill py-3 px-5"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal">
                                        <i class="fa fa-trash-alt me-2"></i> Xóa tài khoản
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--=======================================
        MODAL XÁC NHẬN XÓA TÀI KHOẢN
    =======================================-->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fa fa-exclamation-triangle"></i> Xác nhận xóa tài khoản
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn <strong>xóa tài khoản</strong> này không?</p>
                    <p class="text-danger">Hành động này <strong>không thể khôi phục</strong>.
                        Toàn bộ đơn hàng và thông tin cá nhân sẽ bị xóa vĩnh viễn.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <a href="delete_account.php" class="btn btn-danger">Có, xóa tài khoản của tôi</a>
                </div>
            </div>
        </div>
    </div>

    <?php include_footer(); ?>

    <!-- Nút back to top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">
        <i class="fa fa-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>