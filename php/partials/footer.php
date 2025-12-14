<?php
// Define base URL if not already defined
if (!isset($base_url)) {
    $base_url = '/shop/';
}
?>

<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">

        <!-- Header Footer -->
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5);">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="<?php echo $base_url; ?>">
                        <h1 class="text-primary mb-0">Fruitables</h1>
                        <p class="text-secondary mb-0">Trái cây tươi ngon mỗi ngày</p>
                    </a>
                </div>
                <div class="col-lg-9">
                    <div class="d-flex justify-content-end pt-3">
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-secondary btn-md-square rounded-circle" href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nội dung chính Footer -->
        <div class="row g-5">

            <!-- Cột 1: Giới thiệu -->
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-3">Tại sao khách hàng yêu thích chúng tôi!</h4>
                <p class="mb-4 text-white-50">Từ vườn nhà đến bàn ăn của bạn,<br>giao trái cây tươi ngon và giàu dinh dưỡng</p>
            </div>

            <!-- Cột 2: Thông tin cửa hàng -->
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-3">Thông tin cửa hàng</h4>
                <a class="btn-link text-white-50" href="<?php echo $base_url; ?>">Trang chủ</a><br>
                <a class="btn-link text-white-50" href="<?php echo $base_url; ?>cua-hang">Cửa hàng</a><br>
                <a class="btn-link text-white-50" href="<?php echo $base_url; ?>lien-he">Liên hệ</a><br>
            </div>

            <!-- Cột 3: Tài khoản người dùng -->
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-3">Tài khoản</h4>
                <?php if ($is_logged_in): ?>
                    <?php if ($is_admin): ?>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>admin/danh-muc">Quản lý danh mục</a><br>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>admin/don-hang">Quản lý đơn hàng</a><br>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>admin/danh-gia">Quản lý đánh giá</a><br>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>admin/san-pham">Quản lý sản phẩm</a><br>
                    <?php else: ?>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>tai-khoan">Tài khoản của tôi</a><br>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>gio-hang">Giỏ hàng</a><br>
                        <a class="btn-link text-white-50" href="<?php echo $base_url; ?>lich-su-mua-hang">Đơn hàng của tôi</a><br>
                    <?php endif; ?>
                    <a class="btn-link text-white-50" href="<?php echo $base_url; ?>dang-xuat">Đăng xuất</a>
                <?php else: ?>
                    <a class="btn-link text-white-50" href="<?php echo $base_url; ?>dang-nhap">Đăng nhập</a><br>
                    <a class="btn-link text-white-50" href="<?php echo $base_url; ?>dang-ky">Đăng ký</a>
                <?php endif; ?>
            </div>

            <!-- Cột 4: Thông tin liên hệ -->
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-3">Liên hệ</h4>
                <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>470 Trần Đại Nghĩa, Ngũ Hành Sơn, Đà Nẵng</p>
                <p class="mb-2"><i class="fa fa-envelope me-3"></i>fruitables@gmail.com</p>
                <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>0123 456 7890</p>
                <p class="mb-3">Phương thức thanh toán</p>
                <img src="<?php echo $base_url; ?>img/payment.png" class="img-fluid rounded" alt="Phương thức thanh toán" style="max-width: 220px;">
            </div>

        </div>
    </div>
</div>

<!-- Bản quyền -->
<div class="container-fluid copyright bg-dark py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-light">
                    <a href="#" class="text-primary text-decoration-none">Copyright Fruitables</a> - Bản quyền thuộc về chúng tôi.
                </span>
            </div>
        </div>
    </div>
</div>