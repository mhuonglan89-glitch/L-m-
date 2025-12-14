<?php
require_once '../include/config.php';
require_role(['customer']);

// ==================== 1. LẤY THÔNG TIN NGƯỜI DÙNG HIỆN TẠI ====================
$user_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Lấy mật khẩu đã băm của người dùng từ CSDL
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    // Nếu không tìm thấy user → session lỗi → đăng xuất
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// ==================== 2. XỬ LÝ FORM ĐỔI MẬT KHẨU (khi submit) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Kiểm tra mật khẩu hiện tại
    if (!password_verify($current, $user['password'])) {
        $errors[] = "Mật khẩu hiện tại không chính xác.";
    }

    // Kiểm tra độ mạnh mật khẩu mới
    if (strlen($new) < 8) {
        $errors[] = "Mật khẩu mới phải có ít nhất 8 ký tự.";
    } elseif (!preg_match("/[A-Z]/", $new)) {
        $errors[] = "Mật khẩu phải chứa ít nhất một chữ cái in hoa.";
    } elseif (!preg_match("/\d/", $new)) {
        $errors[] = "Mật khẩu phải chứa ít nhất một chữ số.";
    } elseif (!preg_match("/[!@#$%^&*()_+\-=\[\]{};:'\"\\|,.<>\/?]/", $new)) {
        $errors[] = "Mật khẩu phải chứa ít nhất một ký tự đặc biệt.";
    }

    // Kiểm tra xác nhận mật khẩu
    if ($new !== $confirm) {
        $errors[] = "Nhập lại mật khẩu mới không khớp.";
    }

    // Nếu không có lỗi → cập nhật mật khẩu
    if (empty($errors)) {
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($update, "si", $new_hashed, $user_id);

        if (mysqli_stmt_execute($update)) {
            $success = "Đổi mật khẩu thành công!";
        } else {
            $errors[] = "Đã xảy ra lỗi. Vui lòng thử lại.";
        }
        mysqli_stmt_close($update);
    }
}
mysqli_stmt_close($stmt);

// ==================== 3. TÍNH TOÁN ĐỘ MẠNH MẬT KHẨU (hiển thị realtime) ====================
$new = $_POST['new_password'] ?? ''; // Lấy lại để hiển thị thanh strength

$checks = [
    'length'   => strlen($new) >= 8,
    'uppercase' => preg_match("/[A-Z]/", $new),
    'number'   => preg_match("/\d/", $new),
    'special'  => preg_match("/[!@#$%^&*()_+\-=\[\]{};:'\"\\|,.<>\/?]/", $new)
];

$strength = array_sum($checks);
$strength_percent = $strength * 25;
$strength_text = match ($strength) {
    0, 1 => 'Yếu',
    2    => 'Trung bình',
    3    => 'Mạnh',
    4    => 'Rất mạnh',
};
$strength_color = match ($strength) {
    0, 1 => '#ff4d4f',
    2    => '#ffa940',
    3    => '#f5a425',
    4    => '#52c41a',
};
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đổi mật khẩu - Fruitables</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">

    <style>
        /* ==================== CSS CHO HIỂN THỊ/ẨN MẬT KHẨU ==================== */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 45px;
        }

        .password-wrapper i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.3rem;
            color: #6c757d;
        }

        /* ==================== THANH ĐỘ MẠNH MẬT KHẨU ==================== */
        .strength-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            transition: all .4s;
        }

        /* ==================== DANH SÁCH YÊU CẦU MẬT KHẨU ==================== */
        .req-item::before {
            content: "✕";
            color: #ff4d4f;
            margin-right: 8px;
            font-weight: bold;
        }

        .req-item.valid::before {
            content: "✓";
            color: #52c41a;
        }

        .req-item.valid {
            color: var(--bs-success);
        }
    </style>
</head>

<body>
    <?php include_header(); ?>

    <!-- ==================== TIÊU ĐỀ TRANG ==================== -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Đổi mật khẩu</h1>
    </div>

    <!-- ==================== NỘI DUNG CHÍNH ==================== -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-12">

                    <!-- Tabs điều hướng tài khoản -->
                    <ul class="nav nav-tabs mb-5">
                        <li class="nav-item"><a class="nav-link" href="user.php"><i class='bx bx-user me-2'></i>Thông tin tài khoản</a></li>
                        <li class="nav-item"><a class="nav-link active" href="setPass.php"><i class='bx bx-lock-alt me-2'></i>Đổi mật khẩu</a></li>
                        <li class="nav-item"><a class="nav-link" href="order_history.php"><i class='bx bx-history me-2'></i>Lịch sử đơn hàng</a></li>
                    </ul>

                    <!-- Thông báo thành công -->
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <!-- Thông báo lỗi -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Form đổi mật khẩu -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">
                            <form method="POST">
                                <div class="row g-4">

                                    <!-- Mật khẩu hiện tại -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" name="current_password" class="form-control" required>
                                            <i class='bx bx-hide toggle-password' onclick="toggleVisibility(this)"></i>
                                        </div>
                                    </div>

                                    <!-- Mật khẩu mới + thanh độ mạnh -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Mật khẩu mới <span class="text-danger">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" name="new_password" class="form-control" required>
                                            <i class='bx bx-hide toggle-password' onclick="toggleVisibility(this)"></i>
                                        </div>

                                        <div class="password-strength mt-2 d-flex align-items-center gap-2">
                                            <div class="strength-bar flex-fill" style="width: <?= $strength_percent ?>%; background: <?= $strength_color ?>"></div>
                                            <small class="strength-text"><?= $strength_text ?></small>
                                        </div>
                                    </div>

                                    <!-- Nhập lại mật khẩu mới -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" name="confirm_password" class="form-control" required>
                                            <i class='bx bx-hide toggle-password' onclick="toggleVisibility(this)"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Yêu cầu mật khẩu (hiển thị tick/xanh/đỏ) -->
                                <div class="password-requirements bg-light p-4 rounded mt-4">
                                    <p class="mb-2 fw-bold">Yêu cầu mật khẩu:</p>
                                    <ul class="list-unstyled mb-0 ps-3">
                                        <li class="req-item <?= $checks['length'] ? 'valid' : '' ?>">Ít nhất 8 ký tự</li>
                                        <li class="req-item <?= $checks['uppercase'] ? 'valid' : '' ?>">Có ít nhất một chữ cái in hoa (A-Z)</li>
                                        <li class="req-item <?= $checks['number'] ? 'valid' : '' ?>">Có ít nhất một chữ số (0-9)</li>
                                        <li class="req-item <?= $checks['special'] ? 'valid' : '' ?>">Có ít nhất một ký tự đặc biệt (!@#$%^&*...)</li>
                                    </ul>
                                </div>

                                <!-- Nút submit -->
                                <div class="mt-5 d-flex flex-wrap gap-3">
                                    <button type="submit" class="btn btn-primary rounded-pill py-3 px-5">
                                        <i class="fa fa-save me-2"></i> Cập nhật mật khẩu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_footer(); ?>

    <!-- Nút back to top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">↑</a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/shop/js/bootstrap.bundle.min.js"></script>

    <!-- Hàm hiển thị/ẩn mật khẩu -->
    <script>
        function toggleVisibility(icon) {
            const input = icon.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bx-hide', 'bx-show');
            } else {
                input.type = 'password';
                icon.classList.replace('bx-show', 'bx-hide');
            }
        }
    </script>
</body>

</html>

<?php mysqli_close($conn); ?>