<?php
require_once 'include/config.php';
require_role(['admin', 'customer', 'guest']);

/* ========================================
   1. XỬ LÝ GỬI FORM LIÊN HỆ
   ======================================== */
$success = "";
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và làm sạch dữ liệu từ form
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation dữ liệu đầu vào
    if (empty($name)) {
        $errors[] = "Vui lòng nhập họ và tên.";
    }

    if (empty($email)) {
        $errors[] = "Vui lòng nhập email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không đúng định dạng.";
    }

    if (empty($message)) {
        $errors[] = "Vui lòng nhập nội dung liên hệ.";
    } elseif (strlen($message) < 10) {
        $errors[] = "Nội dung quá ngắn, vui lòng nhập ít nhất 10 ký tự.";
    }

    // Nếu không có lỗi → lưu vào CSDL
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            $success = "Cảm ơn bạn! Tin nhắn đã được gửi thành công. Chúng tôi sẽ phản hồi sớm nhất có thể.";
            $_POST = []; // Xóa dữ liệu form sau khi gửi thành công
        } else {
            $errors[] = "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Liên hệ - Fruitables Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />

    <!-- Bootstrap & Custom CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
        .back-to-top {
            bottom: 30px;
            right: 30px;
        }
    </style>
</head>

<body>
    <?php include_header(); ?>

    <!-- ========================================
         2. PAGE HEADER (Breadcrumb)
         ======================================== -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Liên hệ với chúng tôi</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Cửa hàng</li>
            <li class="breadcrumb-item active text-white">Liên hệ</li>
        </ol>
    </div>

    <!-- ========================================
         3. NỘI DUNG CHÍNH TRANG LIÊN HỆ
         ======================================== -->
    <div class="container-fluid contact py-5">
        <div class="container py-5">
            <div class="p-5 bg-light rounded shadow">

                <!-- Thông báo thành công -->
                <?php if ($success): ?>
                    <div class="alert alert-success text-center py-4">
                        <h4 class="text-success"><?= $success ?></h4>
                    </div>
                <?php endif; ?>

                <!-- Hiển thị lỗi (nếu có) -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Tiêu đề giới thiệu -->
                <div class="text-center mx-auto mb-5" style="max-width: 700px;">
                    <h1 class="text-primary mb-3">Liên hệ ngay</h1>
                    <p>Bạn có thắc mắc về sản phẩm, đơn hàng, giao hàng hay bất kỳ vấn đề nào? Đội ngũ Fruitables luôn sẵn sàng hỗ trợ bạn 24/7!</p>
                </div>

                <div class="row g-5">

                    <!-- ====================================
                         3.1. Form liên hệ (cột trái)
                         ==================================== -->
                    <div class="col-lg-7">
                        <form method="POST" action="">
                            <input type="text" name="name" class="w-100 form-control border-0 py-3 mb-4"
                                placeholder="Họ và tên của bạn"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

                            <input type="email" name="email" class="w-100 form-control border-0 py-3 mb-4"
                                placeholder="Email của bạn"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                            <textarea name="message" class="w-100 form-control border-0 mb-4" rows="7"
                                placeholder="Viết tin nhắn của bạn tại đây..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

                            <button type="submit" class="w-100 btn btn-primary py-3 rounded-pill fw-bold">
                                Gửi tin nhắn
                            </button>
                        </form>
                    </div>

                    <!-- ====================================
                         3.2. Thông tin liên hệ & Bản đồ (cột phải)
                         ==================================== -->
                    <div class="col-lg-5">

                        <!-- Địa chỉ -->
                        <div class="d-flex p-4 rounded mb-4 bg-white shadow-sm">
                            <i class="fas fa-map-marker-alt fa-2x text-primary me-4"></i>
                            <div>
                                <h5>Địa chỉ cửa hàng</h5>
                                <p class="mb-0">470 Trần Đại Nghĩa, Ngũ Hành Sơn<br>TP. Đà Nẵng, Việt Nam</p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="d-flex p-4 rounded mb-4 bg-white shadow-sm">
                            <i class="fas fa-envelope fa-2x text-primary me-4"></i>
                            <div>
                                <h5>Email</h5>
                                <p class="mb-0">fruitables.shop@gmail.com</p>
                            </div>
                        </div>

                        <!-- Hotline -->
                        <div class="d-flex p-4 rounded bg-white shadow-sm">
                            <i class="fa fa-phone-alt fa-2x text-primary me-4"></i>
                            <div>
                                <h5>Hotline</h5>
                                <p class="mb-0">1900 1234 (8h30 – 21h30)</p>
                            </div>
                        </div>

                        <!-- Google Maps -->
                        <div class="mt-4">
                            <iframe class="rounded w-100" style="height: 300px; border:0;"
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3834.961248948375!2d108.24969597417755!3d16.060500941942176!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31421088d956ac8d%3A0x1c7f56e40c8525c7!2zNDcwIFRy4bqnbiDEkOG6oWkgTmdoxKlhLCBOZ8WpIEjDoG5oIFPGoW4sIEjhu5MgQ2jDrW5oLCDEkMOgIE7hurVuZyA1NTAwMDAsIFZpZXRuYW0!5e0!3m2!1svi!2s!4v1735680000000!5m2!1svi!2s"
                                allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_footer(); ?>

    <!-- ========================================
         4. NÚT BACK TO TOP
         ======================================== -->
    <a href="#" class="btn btn-primary rounded-circle back-to-top position-fixed">
        <i class="fa fa-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Đóng kết nối cơ sở dữ liệu
mysqli_close($conn);
?>