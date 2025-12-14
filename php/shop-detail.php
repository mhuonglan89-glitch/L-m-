<?php
require_once 'include/config.php';
require_role(['admin', 'customer', 'guest']);

// ==================== LẤY ID SẢN PHẨM TỪ URL ====================
$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    die("Sản phẩm không tồn tại.");
}

// ==================== LẤY THÔNG TIN CHI TIẾT SẢN PHẨM ====================
$sql = "SELECT p.*, 
               c.name AS category_name,
               COALESCE(AVG(r.rating), 5) AS avg_rating,
               COUNT(r.id) AS review_count
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN review r ON p.id = r.product_id
        WHERE p.id = ?
        GROUP BY p.id";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    die("Sản phẩm không tồn tại.");
}

// Tính toán đánh giá trung bình và số lượng đánh giá
$avg_rating   = round($product['avg_rating']);
$review_count = (int)$product['review_count'];

// ==================== LẤY DANH SÁCH ĐÁNH GIÁ CỦA SẢN PHẨM ====================
$reviews_sql = "SELECT r.*, u.username, c.phone
                FROM review r
                LEFT JOIN customers c ON r.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE r.product_id = ?
                ORDER BY r.id DESC";

$reviews_stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($reviews_stmt, "i", $product_id);
mysqli_stmt_execute($reviews_stmt);
$reviews = mysqli_stmt_get_result($reviews_stmt);

// ==================== LẤY SẢN PHẨM LIÊN QUAN (CÙNG DANH MỤC) ====================
$related_sql = "SELECT p.*, 
                       c.name AS category_name,
                       COALESCE(AVG(r.rating), 5) AS avg_rating,
                       COUNT(r.id) AS review_count
                FROM products p
                JOIN categories c ON p.category_id = c.id
                LEFT JOIN review r ON p.id = r.product_id
                WHERE p.category_id = ?
                  AND p.id != ?
                  AND p.stock > 0
                GROUP BY p.id
                ORDER BY RAND() 
                LIMIT 8";

$related_stmt = mysqli_prepare($conn, $related_sql);
mysqli_stmt_bind_param($related_stmt, "ii", $product['category_id'], $product['id']);
mysqli_stmt_execute($related_stmt);
$related = mysqli_stmt_get_result($related_stmt);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($product['name']) ?> - Fruitables</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">

    <style>
        .star-rating i {
            cursor: pointer;
            font-size: 1.5rem;
        }

        .star-rating i.active {
            color: #ffc107;
        }
    </style>
</head>

<body>

    <?php include_header(); ?>

    <!-- ==================== PAGE HEADER ==================== -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Chi tiết sản phẩm</h1>
    </div>

    <!-- ==================== NỘI DUNG CHÍNH ==================== -->
    <div class="container-fluid py-5 mt-5">
        <div class="container py-5">
            <div class="row g-4 mb-5">

                <!-- ==================== CỘT TRÁI: HÌNH ẢNH + THÔNG TIN + ĐÁNH GIÁ ==================== -->
                <div class="col-lg-8 col-xl-9">

                    <!-- Hình ảnh & Thông tin sản phẩm -->
                    <div class="row g-4">
                        <!-- Hình ảnh sản phẩm -->
                        <div class="col-lg-6">
                            <div class="border rounded">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#imageModalMain">
                                    <img src="<?= BASE_URL ?>img/products/<?= htmlspecialchars($product['image'] ?: 'single-item.jpg') ?>"
                                        class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']) ?>">
                                </a>
                            </div>
                        </div>

                        <!-- Thông tin chi tiết -->
                        <div class="col-lg-6">
                            <h4 class="fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h4>
                            <p class="mb-3">Danh mục: <?= htmlspecialchars($product['category_name']) ?></p>
                            <h5 class="fw-bold mb-3">
                                $<?= number_format($product['price'], 2) ?> / <?= htmlspecialchars($product['unit']) ?>
                            </h5>

                            <!-- Đánh giá sao trung bình -->
                            <div class="d-flex align-items-center mb-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa fa-star <?= ($i <= $avg_rating ? 'text-warning' : 'text-muted') ?> fa-lg"></i>
                                <?php endfor; ?>
                                <small class="text-muted ms-2">(<?= $review_count ?> đánh giá)</small>
                            </div>

                            <p class="mb-4"><?= nl2br(htmlspecialchars($product['description'] ?: 'Chưa có mô tả cho sản phẩm này.')) ?></p>

                            <!-- Form thêm vào giỏ hàng -->
                            <form action="<?= BASE_URL ?>them-vao-gio-hang" method="GET" class="d-inline">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                                <div class="input-group quantity mb-5 d-inline-flex" style="width:100px;">
                                    <div class="input-group-btn">
                                        <button type="button" class="btn btn-sm btn-minus rounded-circle bg-light border">-</button>
                                    </div>
                                    <input type="text" name="quantity" class="form-control text-center border-0" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                                    <div class="input-group-btn">
                                        <button type="button" class="btn btn-sm btn-plus rounded-circle bg-light border">+</button>
                                    </div>
                                </div>

                                <button type="submit" class="btn border border-secondary rounded-pill px-4 py-2 text-primary ms-3" <?= $product['stock'] < 1 ? 'disabled' : '' ?>>
                                    <i class="fa fa-shopping-bag me-2 text-primary"></i> 
                                    <?= $product['stock'] < 1 ? 'Hết hàng' : 'Thêm vào giỏ' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ==================== TAB ĐÁNH GIÁ ==================== -->
                    <div class="col-lg-12 mt-5">
                        <nav>
                            <div class="nav nav-tabs mb-3">
                                <button class="nav-link active border-white border-bottom-0"
                                    data-bs-toggle="tab" data-bs-target="#nav-reviews">
                                    Đánh giá (<?= $review_count ?>)
                                </button>
                            </div>
                        </nav>

                        <div class="tab-content mb-5">
                            <div class="tab-pane fade show active" id="nav-reviews">

                                <!-- Danh sách đánh giá -->
                                <?php if (mysqli_num_rows($reviews) > 0): ?>
                                    <?php mysqli_data_seek($reviews, 0); ?>
                                    <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
                                        <div class="d-flex mb-4">
                                            <img src="<?= BASE_URL ?>img/avatar.jpg" class="img-fluid rounded-circle p-3"
                                                style="width:100px;height:100px;" alt="Avatar">
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-2" style="font-size:14px;">
                                                    <?= date('d/m/Y', strtotime($rev['created_at'])) ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5><?= htmlspecialchars($rev['username'] ?: 'Khách') ?></h5>
                                                    <div class="d-flex mb-2">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fa fa-star <?= $i <= $rev['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <p><?= nl2br(htmlspecialchars($rev['message'])) ?></p>

                                                <?php if ($rev['image'] && file_exists('../img/reviews/' . $rev['image'])): ?>
                                                    <img src="<?php echo BASE_URL; ?>img/reviews/<?= htmlspecialchars($rev['image']) ?>"
                                                        class="img-fluid rounded mt-2" style="max-width:400px;" alt="Ảnh đánh giá">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p>Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                                <?php endif; ?>

                                <!-- Form viết đánh giá (chỉ hiển thị khi đã đăng nhập) -->
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <div class="mt-5 p-4 border rounded bg-light">
                                        <form action="/shop/gui-danh-gia" method="POST"
                                            enctype="multipart/form-data" id="reviewForm">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="rating" value="5" id="rating-value">

                                            <h4 class="mb-4 fw-bold">Viết đánh giá của bạn</h4>
                                            <div class="row g-4">

                                                <div class="col-12">
                                                    <textarea name="message" class="form-control" rows="6"
                                                        placeholder="Nội dung đánh giá *" required></textarea>
                                                </div>

                                                <div class="col-12">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <p class="mb-0 me-3">Chọn số sao:</p>
                                                        <div class="star-rating d-flex">
                                                            <i class="fa fa-star active" data-value="1"></i>
                                                            <i class="fa fa-star active" data-value="2"></i>
                                                            <i class="fa fa-star active" data-value="3"></i>
                                                            <i class="fa fa-star active" data-value="4"></i>
                                                            <i class="fa fa-star active" data-value="5"></i>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label">Tải lên ảnh (tùy chọn)</label>
                                                    <input type="file" name="image" accept="image/*" class="form-control" id="imageInput">
                                                    <div id="image-preview" class="mt-3"></div>
                                                </div>

                                                <div class="col-12 text-end">
                                                    <button type="submit" class="btn btn-primary rounded-pill px-5">
                                                        Gửi đánh giá
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <p>Vui lòng <a href="<?= BASE_URL ?>dang-nhap">đăng nhập</a> để viết đánh giá.</p>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== SẢN PHẨM LIÊN QUAN ==================== -->
                <div class="col-lg-12 mt-5">
                    <h1 class="fw-bold mb-4">Sản phẩm liên quan</h1>
                    <div class="row g-4">
                        <?php
                        mysqli_data_seek($related, 0);
                        $has_related = false;
                        ?>
                        <?php while ($rel = mysqli_fetch_assoc($related)): $has_related = true; ?>
                            <?php
                            $rel_rating  = round($rel['avg_rating'] ?? 5);
                            $rel_reviews = (int)$rel['review_count'];
                            ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="rounded position-relative fruite-item h-100 d-flex flex-column">
                                    <div class="fruite-img overflow-hidden">
                                        <a href="<?= BASE_URL ?>san-pham/<?= $rel['id'] ?>">
                                            <img src="<?= BASE_URL ?>img/products/<?= htmlspecialchars($rel['image'] ?: 'fruite-item-5.jpg') ?>"
                                                class="img-fluid w-100 rounded-top" style="height:200px;object-fit:cover;"
                                                alt="<?= htmlspecialchars($rel['name']) ?>">
                                        </a>
                                    </div>

                                    <div class="text-white bg-secondary px-3 py-1 rounded position-absolute"
                                        style="top:10px;left:10px;font-size:0.8rem;">
                                        <?= htmlspecialchars($rel['category_name']) ?>
                                    </div>

                                    <div class="p-3 border border-secondary border-top-0 rounded-bottom d-flex flex-column flex-grow-1">
                                        <h5 class="mb-2">
                                            <a href="<?= BASE_URL ?>san-pham/<?= $rel['id'] ?>/"
                                                class="text-dark text-decoration-none">
                                                <?= htmlspecialchars($rel['name']) ?>
                                            </a>
                                        </h5>

                                        <div class="d-flex align-items-center mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa fa-star <?= ($i <= $rel_rating ? 'text-warning' : 'text-muted') ?> fa-xs"></i>
                                            <?php endfor; ?>
                                            <small class="text-muted ms-1">(<?= $rel_reviews ?>)</small>
                                        </div>

                                        <p class="text-muted small mb-3 flex-grow-1">
                                            <?= strlen($rel['description'] ?? '') > 80
                                                ? htmlspecialchars(substr($rel['description'], 0, 80)) . '...'
                                                : htmlspecialchars($rel['description'] ?? 'Không có mô tả') ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-end mt-auto">
                                            <p class="text-dark fs-5 fw-bold mb-0">
                                                $<?= number_format($rel['price'], 2) ?>
                                                <small class="text-muted fs-6">/ <?= htmlspecialchars($rel['unit']) ?></small>
                                            </p>
                                            <a href="<?= BASE_URL ?>them-vao-gio-hang?product_id=<?= $rel['id'] ?>&quantity=1"
                                                class="btn border border-secondary rounded-pill px-3 py-2 text-primary btn-sm">
                                                <i class="fa fa-shopping-bag me-2 text-primary"></i> Thêm vào giỏ
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php if (!$has_related): ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Không tìm thấy sản phẩm liên quan trong danh mục này.</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/shop/php/js/bootstrap.bundle.min.js"></script>
    <script src="/shop/php/js/main.js"></script>

    <script>
        // Tăng/giảm số lượng sản phẩm
        document.querySelectorAll('.btn-plus, .btn-minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.closest('.quantity').querySelector('input[name="quantity"]');
                let val = parseInt(input.value);
                if (this.classList.contains('btn-minus') && val > 1) val--;
                if (this.classList.contains('btn-plus')) val++;
                input.value = val;
            });
        });

        // Chọn số sao đánh giá
        document.querySelectorAll('.star-rating i').forEach(star => {
            star.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                document.getElementById('rating-value').value = value;
                document.querySelectorAll('.star-rating i').forEach((s, idx) => {
                    s.classList.toggle('active', idx < value);
                });
            });
        });

        // Xem trước ảnh khi tải lên đánh giá
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.classList.add('img-fluid', 'rounded');
                    img.style.maxHeight = '250px';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>

</html>

<?php mysqli_close($conn); ?>