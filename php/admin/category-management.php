<?php
require_once '../include/config.php';
require_role(['admin']);

/* ==========================================================================
   1. XỬ LÝ XÓA DANH MỤC
   ========================================================================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $search = $_GET['search'] ?? '';

    // Kiểm tra xem danh mục có sản phẩm nào đang sử dụng không
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];

    if ($count > 0) {
        $_SESSION['message'] = '<div class="alert alert-danger">Không thể xóa! Danh mục đang chứa sản phẩm.</div>';
    } else {
        // Xóa danh mục nếu không có sản phẩm liên quan
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['message'] = '<div class="alert alert-success">Xóa danh mục thành công!</div>';
    }

    // Quay lại trang với từ khóa tìm kiếm (nếu có)
    header("Location: category-management.php" . ($search ? "?search=" . urlencode($search) : ""));
    exit();
}

/* ==========================================================================
   2. XỬ LÝ THÊM / SỬA DANH MỤC (POST)
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name          = trim($_POST['name']);
    $search_param  = $_POST['search_param'] ?? '';

    // Validate tên danh mục
    if (empty($name)) {
        $_SESSION['message'] = '<div class="alert alert-danger">Tên danh mục không được để trống!</div>';
    } else {
        if ($id > 0) {
            // Cập nhật danh mục
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            $_SESSION['message'] = '<div class="alert alert-success">Cập nhật danh mục thành công!</div>';
        } else {
            // Thêm mới danh mục
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $_SESSION['message'] = '<div class="alert alert-success">Thêm danh mục thành công!</div>';
        }
    }

    // Redirect giữ lại từ khóa tìm kiếm
    header("Location: category-management.php" . ($search_param ? "?search=" . urlencode($search_param) : ""));
    exit();
}

/* ==========================================================================
   3. HIỂN THỊ THÔNG BÁO & XÓA SAU KHI DÙNG
   ========================================================================== */
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

/* ==========================================================================
   4. XỬ LÝ TÌM KIẾM
   ========================================================================== */
$search = $_GET['search'] ?? '';
$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where = " WHERE name LIKE ?";
    $params[] = "%" . $conn->real_escape_string($search) . "%";
    $types .= "s";
}

/* ==========================================================================
   5. LẤY DANH SÁCH DANH MỤC
   ========================================================================== */
$sql = "SELECT * FROM categories" . $where . " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$categories_result = $stmt->get_result();

/* ==========================================================================
   6. LẤY THÔNG TIN DANH MỤC ĐỂ SỬA (nếu có ?edit=)
   ========================================================================== */
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Admin - Quản lý danh mục</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>

<body>

    <?php include_header(); ?>

    <!-- ======================================================================
         HEADER TRANG ADMIN
         ====================================================================== -->
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

            <!-- ==================================================================
                 THÔNG BÁO THÀNH CÔNG / LỖI
                 ================================================================== -->
            <?php if ($message): ?>
                <div class="alert alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ==================================================================
                 MENU NHANH CHUYỂN TRANG
                 ================================================================== -->
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h4 class="mb-1">Quản lý</h4>
                        <small class="text-muted">Sản phẩm • Đánh giá • Danh mục • Đơn hàng</small>
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

            <div class="row g-4">

                <!-- ==============================================================
                     CỘT TRÁI: DANH SÁCH DANH MỤC + TÌM KIẾM
                     ============================================================== -->
                <div class="col-lg-8">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Quản lý danh mục</h3>
                    </div>

                    <!-- Form tìm kiếm -->
                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" placeholder="Tìm kiếm danh mục..."
                                value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                            <?php if ($search): ?>
                                <a href="category-management.php" class="btn btn-outline-danger"><i class="fa fa-times"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Bảng danh sách danh mục -->
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Mã</th>
                                    <th>Tên danh mục</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categories_result->num_rows > 0): ?>
                                    <?php while ($cat = $categories_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>CAT<?= str_pad($cat['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-center">
                                                <a href="?edit=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>"
                                                    class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                                                <a href="?delete=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>"
                                                    onclick="return confirm('Xóa danh mục này?');"
                                                    class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Không có danh mục nào</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ==============================================================
                     CỘT PHẢI: FORM THÊM / SỬA DANH MỤC
                     ============================================================== -->
                <div class="col-lg-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <?= $edit_category ? 'Sửa danh mục' : 'Thêm danh mục mới' ?>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="search_param" value="<?= htmlspecialchars($search) ?>">
                                <?php if ($edit_category): ?>
                                    <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required
                                        value="<?= $edit_category ? htmlspecialchars($edit_category['name']) : '' ?>"
                                        placeholder="VD: Trái cây nhập khẩu">
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <?php if ($edit_category): ?>
                                        <a href="category-management.php?search=<?= urlencode($search) ?>"
                                            class="btn btn-outline-secondary">Hủy</a>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary">
                                        <?= $edit_category ? 'Cập nhật' : 'Thêm mới' ?>
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

    <a href="#" class="btn btn-primary border-3 rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>