<?php
require_once 'include/config.php';
require_role(['admin', 'customer', 'guest']);

/* ==================== LẤY DỮ LIỆU DANH MỤC ==================== */
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY id");

/* ==================== HÀM LẤY SẢN PHẨM THEO DANH MỤC ==================== */
function getProductsByCategory($conn, $category_id = null, $limit = null)
{
    $sql = "SELECT p.*, c.name as category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.stock > 0";

    if ($category_id) {
        $sql .= " AND p.category_id = " . intval($category_id);
    }

    $sql .= " ORDER BY p.id DESC";

    if ($limit !== null && is_numeric($limit) && $limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }

    $result = mysqli_query($conn, $sql);
    return $result ?: mysqli_query($conn, "SELECT * FROM products WHERE 1=0");
}

/* ==================== DỮ LIỆU CÁC PHẦN CHÍNH ==================== */

// Tất cả sản phẩm (hiển thị ở tab "Tất cả")
$all_products = getProductsByCategory($conn);

// Sản phẩm bán chạy (top 6 theo đánh giá và số lượng review)
$bestsellers = mysqli_query($conn, "
    SELECT
        p.*,
        c.name AS category_name,
        COALESCE(AVG(r.rating), 5) AS avg_rating,
        COUNT(r.id) AS review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN review r ON p.id = r.product_id
    WHERE p.stock > 0
    GROUP BY p.id
    ORDER BY avg_rating DESC, review_count DESC, p.id DESC
    LIMIT 6
");

// Đánh giá khách hàng mới nhất (có nội dung và rating)
$testimonials = mysqli_query($conn, "
    SELECT r.*, u.username
    FROM review r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE r.rating > 0 AND r.message != ''
    ORDER BY r.id DESC
    LIMIT 3
");

// Thống kê cửa hàng
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT customer_id) as total FROM orders WHERE status != 'Cancelled'"))['total'] ?? 0;

$rating_result   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM review WHERE rating > 0"));
$service_quality = ($rating_result && $rating_result['total_reviews'] > 0) ? round($rating_result['avg_rating'] * 20) : 99;

$total_products  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock > 0"))['total'] ?? 0;
$delivered_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'Delivered'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Fruitables - Rau Củ Quả Hữu Cơ Tươi Sạch</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>

<body>

    <?php include_header(); ?>

    <!-- ====================== HERO HEADER ====================== -->
    <div class="container-fluid py-5 mb-5 hero-header">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-md-12 col-lg-7">
                    <h4 class="mb-3 text-secondary">100% Hữu cơ tự nhiên</h4>
                    <h1 class="mb-5 display-3 text-primary">Rau củ & Trái cây hữu cơ tươi sạch</h1>
                </div>
                <div class="col-md-12 col-lg-5">
                    <div id="carouselId" class="carousel slide position-relative" data-bs-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active rounded">
                                <img src="../img/hero-img-1.png" class="img-fluid w-100 h-100 bg-secondary rounded" alt="Trái cây">
                                <a href="#" class="btn px-4 py-2 text-white rounded">Trái cây</a>
                            </div>
                            <div class="carousel-item rounded">
                                <img src="../img/hero-img-2.jpg" class="img-fluid w-100 h-100 rounded" alt="Rau củ">
                                <a href="#" class="btn px-4 py-2 text-white rounded">Rau củ</a>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Trước</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Tiếp</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== TÍNH NĂNG NỔI BẬT ====================== -->
    <div class="container-fluid featurs py-5">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="featurs-item text-center rounded bg-light p-4">
                        <div class="featurs-icon btn-square rounded-circle bg-secondary mb-5 mx-auto">
                            <i class="fas fa-car-side fa-3x text-white"></i>
                        </div>
                        <h5>Miễn phí vận chuyển</h5>
                        <p class="mb-0">Miễn phí với đơn hàng trên 300.000đ</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="featurs-item text-center rounded bg-light p-4">
                        <div class="featurs-icon btn-square rounded-circle bg-secondary mb-5 mx-auto">
                            <i class="fas fa-user-shield fa-3x text-white"></i>
                        </div>
                        <h5>Thanh toán an toàn</h5>
                        <p class="mb-0">Bảo mật 100%</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="featurs-item text-center rounded bg-light p-4">
                        <div class="featurs-icon btn-square rounded-circle bg-secondary mb-5 mx-auto">
                            <i class="fas fa-exchange-alt fa-3x text-white"></i>
                        </div>
                        <h5>Đổi trả 30 ngày</h5>
                        <p class="mb-0">Hoàn tiền trong 30 ngày</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="featurs-item text-center rounded bg-light p-4">
                        <div class="featurs-icon btn-square rounded-circle bg-secondary mb-5 mx-auto">
                            <i class="fa fa-phone-alt fa-3x text-white"></i>
                        </div>
                        <h5>Hỗ trợ 24/7</h5>
                        <p class="mb-0">Hỗ trợ nhanh mọi lúc</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== DANH MỤC & SẢN PHẨM ====================== -->
    <div class="container-fluid fruite py-5">
        <div class="container py-5">
            <div class="tab-class text-center">
                <div class="row g-4">
                    <div class="col-lg-4 text-start">
                        <h1>Sản phẩm hữu cơ của chúng tôi</h1>
                    </div>
                    <div class="col-lg-8 text-end">
                        <ul class="nav nav-pills d-inline-flex text-center mb-5">
                            <li class="nav-item">
                                <a class="d-flex m-2 py-2 bg-light rounded-pill active" data-bs-toggle="pill" href="#tab-all">
                                    <span class="text-dark" style="width: 130px;">Tất cả sản phẩm</span>
                                </a>
                            </li>
                            <?php mysqli_data_seek($categories_result, 0);
                            while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                <li class="nav-item">
                                    <a class="d-flex py-2 m-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-<?= $cat['id'] ?>">
                                        <span class="text-dark" style="width: 130px;"><?= htmlspecialchars($cat['name']) ?></span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <div class="tab-content">

                    <!-- Tab: Tất cả sản phẩm -->
                    <div id="tab-all" class="tab-pane fade show p-0 active">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <div class="row g-4">
                                    <?php while ($p = mysqli_fetch_assoc($all_products)): ?>
                                        <div class="col-md-6 col-lg-4 col-xl-3">
                                            <div class="rounded position-relative fruite-item">
                                                <div class="fruite-img">
                                                    <a href="<?= BASE_URL ?>san-pham/<?= $p['id'] ?>/">
                                                        <img src="../img/products/<?= $p['image'] ?: 'fruite-item-5.jpg' ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($p['name']) ?>">
                                                    </a>
                                                </div>
                                                <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">
                                                    <?= htmlspecialchars($p['category_name']) ?>
                                                </div>
                                                <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                                    <h4><a href="<?= BASE_URL ?>san-pham/<?= $p['id'] ?>/" class="text-dark text-decoration-none"><?= htmlspecialchars($p['name']) ?></a></h4>
                                                    <p><?= htmlspecialchars($p['description'] ?: 'Sản phẩm hữu cơ tươi sạch') ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <p class="text-dark fs-5 fw-bold mb-0"><?= number_format($p['price'], 0, ',', '.') ?>đ / <?= htmlspecialchars($p['unit']) ?></p>
                                                        <a href="customer/add_to_cart.php?product_id=<?= $p['id'] ?>&quantity=1" class="btn border border-secondary rounded-pill px-3 text-primary">
                                                            <i class="fa fa-shopping-bag me-2 text-primary"></i> Thêm vào giỏ
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Theo từng danh mục -->
                    <?php mysqli_data_seek($categories_result, 0);
                    while ($cat = mysqli_fetch_assoc($categories_result)):
                        $cat_products = getProductsByCategory($conn, $cat['id']);
                    ?>
                        <div id="tab-<?= $cat['id'] ?>" class="tab-pane fade show p-0">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="row g-4">
                                        <?php while ($p = mysqli_fetch_assoc($cat_products)): ?>
                                            <div class="col-md-6 col-lg-4 col-xl-3">
                                                <div class="rounded position-relative fruite-item">
                                                    <div class="fruite-img">
                                                        <a href="<?= BASE_URL ?>san-pham/<?= $p['id'] ?>/">
                                                            <img src="../img/products/<?= $p['image'] ?: 'fruite-item-1.jpg' ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($p['name']) ?>">
                                                        </a>
                                                    </div>
                                                    <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">
                                                        <?= htmlspecialchars($cat['name']) ?>
                                                    </div>
                                                    <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                                        <h4><a href="<?= BASE_URL ?>san-pham/<?= $p['id'] ?>/" class="text-dark text-decoration-none"><?= htmlspecialchars($p['name']) ?></a></h4>
                                                        <p><?= htmlspecialchars($p['description'] ?: 'Tươi ngon từ nông trại') ?></p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <p class="text-dark fs-5 fw-bold mb-0"><?= number_format($p['price'], 0, ',', '.') ?>đ / <?= htmlspecialchars($p['unit']) ?></p>
                                                            <a href="customer/add_to_cart.php?product_id=<?= $p['id'] ?>&quantity=1" class="btn border border-secondary rounded-pill px-3 text-primary">
                                                                <i class="fa fa-shopping-bag me-2 text-primary"></i> Thêm vào giỏ
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- ====================== BANNER GIỮA TRANG ====================== -->
    <div class="container-fluid banner bg-secondary my-5">
        <div class="container py-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="py-4">
                        <h1 class="display-3 text-white">Trái cây ngoại nhập tươi ngon</h1>
                        <p class="fw-normal display-3 text-dark mb-4">tại cửa hàng chúng tôi</p>
                        <p class="mb-4 text-dark">Tất cả sản phẩm đều được tuyển chọn kỹ lưỡng, đảm bảo chất lượng hữu cơ, không hóa chất, tốt cho sức khỏe.</p>
                        <a href="<?php echo BASE_URL; ?>cua-hang" class="banner-btn btn border-2 border-white rounded-pill text-dark py-3 px-5">MUA NGAY</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img src="../img/baner-1.png" class="img-fluid w-100 rounded" alt="">
                        <div class="d-flex align-items-center justify-content-center bg-white rounded-circle position-absolute" style="width: 140px; height: 140px; top: 0; left: 0;">
                            <h1 style="font-size: 100px;">1</h1>
                            <div class="d-flex flex-column">
                                <span class="h2 mb-0">50K</span>
                                <span class="h4 text-muted mb-0">kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== SẢN PHẨM BÁN CHẠY ====================== -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="text-center mx-auto mb-5" style="max-width: 700px;">
                <h1 class="display-4">Sản phẩm bán chạy</h1>
                <p class="text-muted">Những sản phẩm được khách hàng đánh giá cao nhất</p>
            </div>
            <div class="row g-4">
                <?php while ($bs = mysqli_fetch_assoc($bestsellers)):
                    $rating = round($bs['avg_rating']); ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="p-4 rounded bg-light">
                            <div class="row align-items-center">
                                <div class="col-6">
                                    <a href="<?= BASE_URL ?>san-pham/<?= $bs['id'] ?>">
                                        <img src="../img/products/<?= $bs['image'] ?: 'best-product-1.jpg' ?>" class="img-fluid rounded-circle w-100" alt="<?= htmlspecialchars($bs['name']) ?>">
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= BASE_URL ?>san-pham/<?= $bs['id'] ?>" class="h5 text-dark text-decoration-none">
                                        <?= htmlspecialchars($bs['name']) ?>
                                    </a>
                                    <div class="d-flex my-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $rating ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                        <small class="ms-2 text-muted">(<?= $bs['review_count'] ?>)</small>
                                    </div>
                                    <h4 class="mb-3"><?= number_format($bs['price'], 0, ',', '.') ?>đ</h4>
                                    <a href="customer/add_to_cart.php?product_id=<?= $bs['id'] ?>" class="btn border border-secondary rounded-pill px-3 text-primary">
                                        <i class="fa fa-shopping-bag me-2 text-primary"></i> Thêm vào giỏ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- ====================== THỐNG KÊ CỬA HÀNG ====================== -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="bg-light p-5 rounded">
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="counter bg-white rounded p-5">
                            <i class="fa fa-users text-secondary"></i>
                            <h4>Khách hàng hài lòng</h4>
                            <h1><?= $total_customers ?></h1>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="counter bg-white rounded p-5">
                            <i class="fas fa-star text-secondary"></i>
                            <h4>Chất lượng dịch vụ</h4>
                            <h1><?= $service_quality ?>%</h1>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="counter bg-white rounded p-5">
                            <i class="fa fa-box-open text-secondary"></i>
                            <h4>Sản phẩm sẵn có</h4>
                            <h1><?= $total_products ?></h1>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="counter bg-white rounded p-5">
                            <i class="fas fa-truck text-secondary"></i>
                            <h4>Đơn hàng thành công</h4>
                            <h1><?= $delivered_orders ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== ĐÁNH GIÁ KHÁCH HÀNG ====================== -->
    <div class="container-fluid testimonial py-5">
        <div class="container py-5">
            <div class="testimonial-header text-center">
                <h4 class="text-primary">Đánh giá khách hàng</h4>
                <h1 class="display-5 mb-5 text-dark">Khách hàng nói gì về chúng tôi</h1>
            </div>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php $active = 'active';
                    mysqli_data_seek($testimonials, 0);
                    while ($t = mysqli_fetch_assoc($testimonials)): ?>
                        <div class="carousel-item <?= $active ?>">
                            <div class="testimonial-item img-border-radius bg-light rounded p-4">
                                <div class="position-relative">
                                    <i class="fa fa-quote-right fa-2x text-secondary position-absolute" style="bottom: 30px; right: 0;"></i>
                                    <div class="mb-4 pb-4 border-bottom border-secondary">
                                        <p class="mb-0"><?= htmlspecialchars($t['message']) ?></p>
                                    </div>
                                    <div class="d-flex align-items-center flex-nowrap">
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                            <i class="fas fa-user fa-4x text-white"></i>
                                        </div>
                                        <div class="ms-4 d-block">
                                            <h4 class="text-dark"><?= htmlspecialchars($t['username'] ?: 'Khách hàng thân thiết') ?></h4>
                                            <div class="d-flex pe-5">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $t['rating'] ? 'text-primary' : 'text-secondary' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php $active = '';
                    endwhile; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Trước</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Tiếp</span>
                </button>
            </div>
        </div>
    </div>

    <?php include_footer(); ?>

    <!-- Nút back to top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php mysqli_close($conn); ?>