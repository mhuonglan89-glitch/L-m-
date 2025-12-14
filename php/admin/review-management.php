<?php
require_once '../include/config.php';
require_role(['admin']); // Chỉ admin mới được truy cập trang này

$search = '';
$rating_filter = '';

/* ============================================================
   1. XỬ LÝ TÌM KIẾM VÀ LỌC THEO ĐIỂM SAO (GET)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search        = isset($_GET['search']) ? trim($_GET['search']) : '';
    $rating_filter = (isset($_GET['rating']) && in_array($_GET['rating'], ['1', '2', '3', '4', '5']))
        ? $_GET['rating'] : '';
}

/* ============================================================
   2. XỬ LÝ HÀNH ĐỘNG AJAX (Ẩn/Hiện hoặc Xóa đánh giá)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['review_id'])) {
    $review_id = (int)$_POST['review_id'];
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'toggle_visibility':
            // Đảo ngược trạng thái hiển thị
            $conn->query("UPDATE review SET is_visible = NOT is_visible WHERE id = $review_id");
            $new_status = $conn->query("SELECT is_visible FROM review WHERE id = $review_id")
                ->fetch_assoc()['is_visible'];

            echo json_encode([
                'success'    => true,
                'new_status' => $new_status,
                'text'       => $new_status ? 'Hiển thị' : 'Ẩn',
                'badge'      => $new_status ? 'success' : 'danger',
                'icon'       => $new_status ? 'eye' : 'eye-slash'
            ]);
            break;

        case 'delete':
            // Xóa vĩnh viễn đánh giá
            $conn->query("DELETE FROM review WHERE id = $review_id");
            echo json_encode(['success' => $conn->affected_rows > 0]);
            break;

        default:
            echo json_encode(['success' => false]);
    }
    exit;
}

/* ============================================================
   3. XÂY DỰNG ĐIỀU KIỆN WHERE CHO TÌM KIẾM VÀ LỌC
   ============================================================ */
$where_conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $like = "%$search%";
    $where_conditions[] = "(p.name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR r.message LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}

if ($rating_filter !== '') {
    $where_conditions[] = "r.rating = ?";
    $params[] = $rating_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

/* ============================================================
   4. LẤY DANH SÁCH ĐÁNH GIÁ TỪ CƠ SỞ DỮ LIỆU
   ============================================================ */
$sql = "
    SELECT 
        r.*, 
        u.username, 
        u.email, 
        p.name AS product_name
    FROM review r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN products p ON r.product_id = p.id
    $where_clause
    ORDER BY r.created_at DESC
    LIMIT 200
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Quản lý Đánh giá - Admin Fruitables</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <!-- Bootstrap & CSS tùy chỉnh -->
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">

    <style>
        .review-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>

    <?php include_header(); ?>

    <!-- ============================================================
         5. HEADER TRANG VÀ THANH ĐIỀU HƯỚNG NHANH
         ============================================================ -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Bảng Điều Khiển Admin</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="/shop/quan-tri/san-pham">Sản phẩm</a></li>
            <li class="breadcrumb-item"><a href="/shop/quan-tri/danh-gia">Đánh giá</a></li>
            <li class="breadcrumb-item"><a href="/shop/quan-tri/danh-muc">Danh mục</a></li>
            <li class="breadcrumb-item"><a href="/shop/quan-tri/don-hang">Đơn hàng</a></li>
        </ol>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">

            <!-- Thanh điều hướng nhanh -->
            <div class="bg-light rounded p-3 p-md-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div>
                        <h4 class="mb-1">Quản lý</h4>
                        <small class="text-muted">Trang quản trị sản phẩm, đánh giá, danh mục và đơn hàng</small>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo $base_url; ?>quan-tri/san-pham" 
                           class="btn btn-<?= basename($_SERVER['PHP_SELF']) == 'store-management.php' ? 'primary' : 'outline-secondary' ?>">
                            <i class="fa fa-box-open"></i> Sản phẩm
                        </a>
                        <a href="<?php echo $base_url; ?>quan-tri/danh-gia" 
                           class="btn btn-<?= basename($_SERVER['PHP_SELF']) == 'review-management.php' ? 'primary' : 'outline-secondary' ?>">
                            <i class="fa fa-comments"></i> Đánh giá
                        </a>
                        <a href="<?php echo $base_url; ?>quan-tri/danh-muc" 
                           class="btn btn-<?= basename($_SERVER['PHP_SELF']) == 'category-management.php' ? 'primary' : 'outline-secondary' ?>">
                            <i class="fa fa-list"></i> Danh mục
                        </a>
                        <a href="<?php echo $base_url; ?>quan-tri/don-hang" 
                           class="btn btn-<?= basename($_SERVER['PHP_SELF']) == 'order-management.php' ? 'primary' : 'outline-secondary' ?>">
                            <i class="fa fa-shopping-cart"></i> Đơn hàng
                        </a>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 6. FORM TÌM KIẾM VÀ LỌC THEO SỐ SAO
                 ============================================================ -->
            <div class="card border-0 shadow mb-4">
                <div class="card-body">
                    <form id="searchForm" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="Tên sản phẩm, khách hàng, nội dung đánh giá..."
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Số sao</label>
                            <select name="rating" id="ratingSelect" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>5 Sao</option>
                                <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>4 Sao</option>
                                <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>3 Sao</option>
                                <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>2 Sao</option>
                                <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>1 Sao</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100" id="searchButton">Tìm kiếm</button>
                        </div>
                    </form>

                    <?php if ($search !== '' || $rating_filter !== ''): ?>
                        <div class="mt-3">
                            <a href="/shop/quan-tri/danh-gia" class="btn btn-sm btn-outline-secondary">
                                Xóa bộ lọc
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ============================================================
                 7. DANH SÁCH ĐÁNH GIÁ
                 ============================================================ -->
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tất cả Đánh giá (<?= count($reviews) ?>)</h5>
                    <?php if ($search !== '' || $rating_filter !== ''): ?>
                        <small>Kết quả cho: "<?= htmlspecialchars($search) ?>" <?= $rating_filter ? "· $rating_filter Sao" : '' ?></small>
                    <?php endif; ?>
                </div>

                <div class="card-body p-0">
                    <div style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                        <?php if (empty($reviews)): ?>
                            <div class="p-5 text-center text-muted">
                                <p>Không tìm thấy đánh giá nào phù hợp.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($reviews as $r): ?>
                                    <div class="list-group-item review-item py-4" id="review-row-<?= $r['id'] ?>">
                                        <div class="row align-items-start">
                                            <!-- Thông tin đánh giá -->
                                            <div class="col-lg-8">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-1">
                                                        <strong>#<?= $r['id'] ?></strong>
                                                        <?= htmlspecialchars($r['username'] ?? $r['email'] ?? 'Khách') ?>
                                                        <span class="text-muted small">đánh giá:</span>
                                                        <span class="text-success fw-bold"><?= htmlspecialchars($r['product_name'] ?? 'Sản phẩm đã xóa') ?></span>
                                                    </h6>
                                                    <span class="badge rounded-pill <?= $r['is_visible'] ? 'bg-success' : 'bg-danger' ?>"
                                                        id="status-badge-<?= $r['id'] ?>">
                                                        <?= $r['is_visible'] ? 'Hiển thị' : 'Ẩn' ?>
                                                    </span>
                                                </div>

                                                <div class="mb-2">
                                                    <span class="text-warning">
                                                        <?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?>
                                                    </span>
                                                    <small class="text-muted ms-3">
                                                        <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                                    </small>
                                                </div>

                                                <p class="mb-2"><?= nl2br(htmlspecialchars($r['message'])) ?></p>

                                                <?php if ($r['image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/shop/img/reviews/' . $r['image'])): ?>
                                                    <a href="/shop/img/reviews/<?= htmlspecialchars($r['image']) ?>" target="_blank">
                                                        <img src="/shop/img/reviews/<?= htmlspecialchars($r['image']) ?>"
                                                            alt="Hình ảnh đánh giá" class="img-thumbnail"
                                                            style="max-height: 120px; cursor: zoom-in;">
                                                    </a>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Nút hành động -->
                                            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                                                <button class="btn btn-sm <?= $r['is_visible'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-toggle me-2"
                                                    data-id="<?= $r['id'] ?>">
                                                    <i class="fa fa-<?= $r['is_visible'] ? 'eye-slash' : 'eye' ?>"></i>
                                                    <?= $r['is_visible'] ? 'Ẩn' : 'Hiển thị' ?>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="<?= $r['id'] ?>">
                                                    Xóa
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include_footer(); ?>
    <a href="#" class="btn btn-primary back-to-top rounded-circle border-3">Up</a>

    <!-- ============================================================
         8. SCRIPT AJAX XỬ LÝ ẨN/HIỆN VÀ XÓA ĐÁNH GIÁ
         ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Handle form submission with clean URL
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const search = document.querySelector('input[name="search"]').value;
            const rating = document.querySelector('select[name="rating"]').value;
            
            // Build the clean URL
            let url = '/shop/quan-tri/danh-gia';
            
            // Add search and rating to URL only if they have values
            if (search && rating) {i
                // Both search and rating
                url += '/' + encodeURIComponent(search) + '/' + rating;
            } else if (search) {
                // Only search
                url += '/' + encodeURIComponent(search);
            } else if (rating) {
                // Only rating
                url += '//' + rating;
            }
            
            // Navigate to the clean URL
            window.location.href = url;
        });
        $(document).ready(function() {
            // Chuyển đổi hiển thị / ẩn đánh giá
            $('.btn-toggle').on('click', function() {
                const btn = $(this);
                const id = btn.data('id');

                $.post('', {
                    action: 'toggle_visibility',
                    review_id: id
                }, function(res) {
                    if (res.success) {
                        // Cập nhật badge trạng thái
                        const badge = $('#status-badge-' + id);
                        badge.text(res.text)
                            .removeClass('bg-success bg-danger')
                            .addClass('bg-' + res.badge);

                        // Cập nhật nút
                        btn.html('<i class="fa fa-' + res.icon + '"></i> ' + (res.new_status ? 'Ẩn' : 'Hiển thị'))
                            .toggleClass('btn-outline-warning btn-outline-success');
                    }
                }, 'json');
            });

            // Xóa đánh giá
            $('.btn-delete').on('click', function() {
                if (!confirm('Xóa vĩnh viễn đánh giá này? Hành động này không thể hoàn tác!')) return;

                const id = $(this).data('id');

                $.post('', {
                    action: 'delete',
                    review_id: id
                }, function(res) {
                    if (res.success) {
                        $('#review-row-' + id).fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Xóa đánh giá thất bại.');
                    }
                }, 'json');
            });
        });
    </script>
</body>

</html>