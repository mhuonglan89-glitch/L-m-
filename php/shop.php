<?php
require_once 'include/config.php';
require_role(['admin', 'customer', 'guest']);

/* ==================== 1. XỬ LÝ THAM SỐ TỪ URL ==================== */
$page           = max(1, (int)($_GET['page'] ?? 1));
$limit          = 6;
$offset         = ($page - 1) * $limit;

$category_filter = (int)($_GET['category'] ?? 0);
$price_max       = min(500000, max(0, (float)($_GET['price'] ?? 500000)));
$search          = trim($_GET['search'] ?? '');
$sort            = $_GET['sort'] ?? '';

/* ==================== 2. XÁC ĐỊNH TRƯỜNG SẮP XẾP ==================== */
$orderBy = "p.id DESC";
switch ($sort) {
    case 'price_low':
        $orderBy = "p.price ASC";
        break;
    case 'price_high':
        $orderBy = "p.price DESC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    case 'name_desc':
        $orderBy = "p.name DESC";
        break;
}

/* ==================== 3. XÂY DỰNG ĐIỀU KIỆN WHERE ==================== */
$where  = ["p.stock > 0", "p.price <= ?"];
$params = [$price_max];
$types  = "d";

if ($category_filter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types   .= "i";
}
if ($search !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = "%$search%";
    $types   .= "s";
}
$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

/* ==================== 4. TRUY VẤN DANH SÁCH SẢN PHẨM ==================== */
$sql = "
    SELECT 
        p.*,
        c.name AS category_name,
        COALESCE(AVG(r.rating), 5) AS avg_rating,
        COUNT(r.id) AS review_count
    FROM products p 
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN review r ON p.id = r.product_id
    $whereClause 
    GROUP BY p.id
    ORDER BY $orderBy 
    LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);
$params[] = $limit;
$params[] = $offset;
$types   .= "ii";
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result_products = mysqli_stmt_get_result($stmt);

/* ==================== 5. TÍNH TỔNG SỐ SẢN PHẨM & PHÂN TRANG ==================== */
$count_sql   = "SELECT COUNT(DISTINCT p.id) AS total FROM products p $whereClause";
$count_stmt  = mysqli_prepare($conn, $count_sql);
if ($params_without_pagination = array_slice($params, 0, -2)) {
    mysqli_stmt_bind_param($count_stmt, substr($types, 0, -2), ...$params_without_pagination);
}
mysqli_stmt_execute($count_stmt);
$total_products = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages    = ceil($total_products / $limit);

/* ==================== 6. LẤY DANH SÁCH DANH MỤC ==================== */
$categories = mysqli_query($conn, "
    SELECT 
        c.*, 
        COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.stock > 0
    GROUP BY c.id 
    ORDER BY c.name
");

/* ==================== 7. HÀM TẠO URL GIỮ LẠI BỘ LỌC ==================== */
function buildUrl($newPage = null, $overrides = [])
{
    global $category_filter, $price_max, $search, $sort;
    $params = array_filter([
        'page'     => ($newPage && $newPage > 1) ? $newPage : null,
        'category' => $category_filter > 0 ? $category_filter : null,
        'price'    => $price_max < 500000 ? $price_max : null,
        'search'   => $search !== '' ? $search : null,
        'sort'     => $sort !== '' ? $sort : null,
    ]);
    $params = array_merge($params, $overrides);
    // Sử dụng /shop?...
    return '/shop?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Cửa hàng trái cây tươi - Fruitables</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>

<body>
    <?php include_header(); ?>

    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Cửa hàng</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Cửa hàng</li>
            <li class="breadcrumb-item"><a href="/contact">Liên hệ</a></li>
        </ol>
    </div>

    <div class="container-fluid fruite py-5">
        <div class="container py-5">
            <h1 class="mb-4">Cửa hàng trái cây tươi ngon</h1>

            <div class="row g-4">
                <div class="col-lg-12">

                    <div class="row g-4 mb-4">
                        <div class="col-xl-3">
                            <form method="GET" action="/shop" class="input-group">
                                <input type="search" name="search" class="form-control p-3"
                                    placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="input-group-text p-3">Search</button>
                                <?php if ($category_filter): ?><input type="hidden" name="category" value="<?= $category_filter ?>"><?php endif; ?>
                                <?php if ($price_max < 500000): ?><input type="hidden" name="price" value="<?= $price_max ?>"><?php endif; ?>
                                <?php if ($sort): ?><input type="hidden" name="sort" value="<?= $sort ?>"><?php endif; ?>
                            </form>
                        </div>

                        <div class="col-xl-3 offset-xl-6">
                            <form method="GET" action="/shop" class="bg-light ps-3 py-3 rounded d-flex justify-content-between align-items-center">
                                <label class="mb-0">Sắp xếp:</label>
                                <select name="sort" onchange="this.form.submit()" class="border-0 form-select-sm bg-light">
                                    <option value="" <?= !$sort ? 'selected' : '' ?>>Mặc định</option>
                                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Giá: Thấp → Cao</option>
                                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Giá: Cao → Thấp</option>
                                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên: A → Z</option>
                                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Tên: Z → A</option>
                                </select>
                                <input type="hidden" name="category" value="<?= $category_filter ?>">
                                <input type="hidden" name="price" value="<?= $price_max ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            </form>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-3">
                            <div class="row g-4">

                                <div class="col-12">
                                    <h4>Danh mục</h4>
                                    <ul class="list-unstyled fruite-categorie">
                                        <li>
                                            <div class="d-flex justify-content-between fruite-name">
                                                <a href="/shop">
                                                    Tất cả sản phẩm
                                                </a>
                                                <span>(<?= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM products WHERE stock >0"))['t'] ?>)</span>
                                            </div>
                                        </li>
                                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                            <li>
                                                <div class="d-flex justify-content-between fruite-name">
                                                    <a href="<?= buildUrl(1, ['category' => $cat['id']]) ?>">
                                                        <?= htmlspecialchars($cat['name']) ?>
                                                    </a>
                                                    <span>(<?= $cat['product_count'] ?>)</span>
                                                </div>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>

                                <div class="col-12">
                                    <form method="GET" action="/shop" id="priceForm">
                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h4 class="mb-0">Khoảng giá</h4>
                                                <span class="text-success fw-bold fs-5" id="priceValue">
                                                    <?= number_format($price_max, 0, ',', '.') ?>đ
                                                </span>
                                            </div>
                                            <input type="range" class="form-range custom-range" name="price" id="priceRange"
                                                min="0" max="500000" step="1" value="<?= $price_max ?>">
                                            <div class="d-flex justify-content-between text-muted small mt-2">
                                                <span>0.000</span>
                                                <span>500.000</span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="category" value="<?= $category_filter ?>">
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                        <input type="hidden" name="sort" value="<?= $sort ?>">
                                    </form>
                                </div>

                                <div class="col-12">
                                    <div class="position-relative">
                                        <img src="../img/banner-fruits.jpg" class="img-fluid w-100 rounded" alt="Banner trái cây">
                                        <div class="position-absolute" style="top:50%;right:10px;transform:translateY(-50%)">
                                            <h3 class="text-secondary fw-bold">Trái cây <br> Tươi ngon <br> Mỗi ngày</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-9">
                            <div class="row g-4 justify-content-start">
                                <?php if (mysqli_num_rows($result_products) > 0): ?>
                                    <?php while ($product = mysqli_fetch_assoc($result_products)):
                                        $rating = round($product['avg_rating']);
                                        $review_count = (int)$product['review_count'];
                                    ?>
                                        <div class="col-md-6 col-lg-6 col-xl-4">
                                            <div class="rounded position-relative fruite-item">
                                                <div class="fruite-img">
                                                    <a href="<?= BASE_URL ?>san-pham/<?= $product['id'] ?>/">
                                                        <img src="../img/products/<?= $product['image'] ?: 'fruite-item-5.jpg' ?>"
                                                            class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($product['name']) ?>">
                                                    </a>
                                                </div>
                                                <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top:10px;left:10px;">
                                                    <?= htmlspecialchars($product['category_name']) ?>
                                                </div>
                                                <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                                    <h4><a href="<?= BASE_URL ?>san-pham/<?= $product['id'] ?>/" class="text-dark text-decoration-none">
                                                            <?= htmlspecialchars($product['name']) ?></a></h4>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fa fa-star <?= $i <= $rating ? 'text-warning' : 'text-muted' ?> fa-xs"></i>
                                                        <?php endfor; ?>
                                                        <small class="text-muted ms-2">(<?= $review_count ?> đánh giá)</small>
                                                    </div>
                                                    <p class="text-truncate-3"><?= htmlspecialchars($product['description'] ?? '') ?></p>
                                                    <div class="d-flex justify-content-between flex-lg-wrap align-items-center">
                                                        <p class="text-dark fs-5 fw-bold mb-0">
                                                            <?= number_format($product['price'], 0, ',', '.') ?>đ
                                                            <small class="text-muted">/ <?= htmlspecialchars($product['unit']) ?></small>
                                                        </p>
                                                        <a href="<?php echo BASE_URL; ?>them-vao-gio-hang?product_id=<?= $product['id'] ?>"
                                                            class="btn border border-secondary rounded-pill px-3 text-primary">
                                                            <i class="fa fa-shopping-bag me-2 text-primary"></i> Thêm vào giỏ
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center">
                                        <p class="display-6 text-muted">Không tìm thấy sản phẩm nào.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($total_pages > 1): ?>
                                <div class="col-12 mt-5">
                                    <div class="pagination d-flex justify-content-center">
                                        <a href="<?= buildUrl($page - 1) ?>" class="rounded <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="<?= buildUrl($i) ?>" class="rounded <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                                        <?php endfor; ?>
                                        <a href="<?= buildUrl($page + 1) ?>" class="rounded <?= $page >= $total_pages ? 'disabled' : '' ?>">Next</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_footer(); ?>

    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">Up</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>js/main.js"></script>

    <script>
        const priceRange = document.getElementById('priceRange');
        const priceValue = document.getElementById('priceValue');
        const priceForm = document.getElementById('priceForm');

        priceRange.addEventListener('input', function() {
            priceValue.textContent = parseInt(this.value).toLocaleString('vi-VN') + 'đ';
        });

        priceRange.addEventListener('change', function() {
            priceForm.submit();
        });
    </script>
</body>

</html>

<?php mysqli_close($conn); ?>