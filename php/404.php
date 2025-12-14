<?php
// ========================================
// Trang lỗi 404 - Không tìm thấy trang
// ========================================

// Gửi header HTTP 404 để trình duyệt và công cụ tìm kiếm nhận biết đây là trang không tồn tại
header("HTTP/1.0 404 Not Found");

// Kết nối cấu hình hệ thống và kiểm tra quyền truy cập
require_once 'include/config.php';

// Cho phép truy cập trang 404 với mọi vai trò (admin, customer, guest)
require_role(['admin', 'customer', 'guest']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <!-- Thiết lập ký tự và responsive -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tiêu đề và mô tả trang -->
    <title>404 - Không tìm thấy trang | Fruitables</title>

    <!-- Font Awesome và Bootstrap Icons -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS chính của giao diện -->
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- ======================================== -->
    <!-- Header chung của website -->
    <!-- ======================================== -->
    <?php include_header(); ?>

    <!-- ======================================== -->
    <!-- Banner tiêu đề trang lỗi 404 -->
    <!-- ======================================== -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Lỗi 404</h1>
    </div>

    <!-- ======================================== -->
    <!-- Nội dung thông báo lỗi chính -->
    <!-- ======================================== -->
    <div class="container-fluid py-5">
        <div class="container py-5 text-center">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <!-- Biểu tượng cảnh báo lớn -->
                    <i class="bi bi-exclamation-triangle display-1 text-secondary"></i>

                    <!-- Mã lỗi và tiêu đề -->
                    <h1 class="display-1">404</h1>
                    <h1 class="mb-4">Không tìm thấy trang</h1>

                    <!-- Thông báo chi tiết -->
                    <p class="mb-4">
                        Xin lỗi, trang bạn đang tìm kiếm không tồn tại trên website của chúng tôi!<br>
                        Bạn có thể quay về trang chủ hoặc thử tìm kiếm nhé?
                    </p>

                    <!-- Nút trở về trang chủ -->
                    <a class="btn border-secondary rounded-pill py-3 px-5" href="<?php echo $base_url; ?>">
                        Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- Footer chung của website -->
    <!-- ======================================== -->
    <?php include_footer(); ?>

    <!-- ======================================== -->
    <!-- Nút Back to Top -->
    <!-- ======================================== -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">
        <i class="fa fa-arrow-up"></i>
    </a>

    <!-- ======================================== -->
    <!-- JavaScript Bootstrap -->
    <!-- ======================================== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>