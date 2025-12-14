<?php
require_once '../include/config.php';
require_role(['admin']);

/* ========================================
   CẤU HÌNH THƯ MỤC UPLOAD ẢNH
   ======================================== */
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/shop/img/products/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

/* ========================================
   HÀM LÀM SẠCH TÊN FILE (BẢO MẬT)
   ======================================== */
function sanitize_filename($filename)
{
    $filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
    $filename = preg_replace("/_+/", "_", $filename);
    return strtolower(trim($filename, '_'));
}

/* ========================================
   1. XỬ LÝ LƯU / CẬP NHẬT SẢN PHẨM (POST)
   ======================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id           = (int)$_POST['id'];
    $name         = trim($_POST['name']);
    $price        = (float)$_POST['price'];
    $category_id  = (int)$_POST['category_id'];
    $unit         = trim($_POST['unit'] ?? 'kg');
    $stock        = max(0, (int)$_POST['stock']);
    $description  = trim($_POST['description'] ?? '');
    $image_path   = $_POST['existing_image'] ?? '';

    // Xử lý upload ảnh mới
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            die("Chỉ chấp nhận file jpg, jpeg, png, gif, webp");
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            die("File quá lớn, tối đa 5MB");
        }

        $sanitized_name = sanitize_filename($original_name);
        $base_name = $sanitized_name . '.' . $ext;
        $dest = $upload_dir . $base_name;
        $counter = 1;
        $final_name = $base_name;

        while (file_exists($dest)) {
            $final_name = $sanitized_name . '_' . $counter . '.' . $ext;
            $dest = $upload_dir . $final_name;
            $counter++;
        }

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $image_path = $final_name;

            // Xóa ảnh cũ nếu có
            if (!empty($_POST['existing_image'])) {
                $old_file = $upload_dir . $_POST['existing_image'];
                if (file_exists($old_file)) @unlink($old_file);
            }
        }
    }

    // Cập nhật hoặc thêm mới sản phẩm
    if ($id > 0) {
        $sql  = "UPDATE products SET name=?, category_id=?, description=?, price=?, unit=?, stock=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdsisi", $name, $category_id, $description, $price, $unit, $stock, $image_path, $id);
    } else {
        $sql  = "INSERT INTO products (name, category_id, description, price, unit, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdsis", $name, $category_id, $description, $price, $unit, $stock, $image_path);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: /shop/quan-tri/cua-hang?success=1");
    exit;
}

/* ========================================
   2. XỬ LÝ XÓA SẢN PHẨM
   ======================================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $search = $_GET['q'] ?? '';
    $cat_filter = $_GET['cat'] ?? '';
    $page = $_GET['page'] ?? 1;

    // Lấy tên file ảnh để xóa
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $image_file = $upload_dir . $row['image'];
        if (!empty($row['image']) && file_exists($image_file)) {
            @unlink($image_file);
        }
    }
    $stmt->close();

    // Xóa bản ghi sản phẩm
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Tạo lại query string để chuyển hướng
    $query_params = array_filter(['q' => $search, 'cat' => $cat_filter, 'page' => $page]);
    $redirect_query = http_build_query(array_merge($query_params, ['deleted' => 1]));

    header("Location: /shop/quan-tri/cua-hang?" . $redirect_query);
    exit;
}

/* ========================================
   3. CHUẨN BỊ DỮ LIỆU CHO FORM (THÊM / SỬA)
   ======================================== */
$edit_product = null;

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
    $stmt->close();
}

if (isset($_GET['add']) && !isset($_GET['edit'])) {
    $edit_product = [
        'id' => 0,
        'name' => '',
        'price' => '',
        'unit' => 'kg',
        'category_id' => '',
        'description' => '',
        'stock' => 100,
        'image' => ''
    ];
}

/* ========================================
   4. LẤY DANH SÁCH DANH MỤC
   ======================================== */
$categories = [];
$result = $conn->query("SELECT id, name FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

/* ========================================
   5. PHÂN TRANG + TÌM KIẾM + LỌC DANH MỤC
   ======================================== */
$limit = 10;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');
$cat_filter = $_GET['cat'] ?? '';

// Đếm tổng sản phẩm (cho phân trang)
$count_sql = "SELECT COUNT(*) AS total FROM products p WHERE 1=1";
$count_params = [];
$count_types = '';

if ($search !== '') {
    $count_sql .= " AND p.name LIKE ?";
    $count_params[] = "%$search%";
    $count_types .= 's';
}
if ($cat_filter !== '' && is_numeric($cat_filter)) {
    $count_sql .= " AND p.category_id = ?";
    $count_params[] = $cat_filter;
    $count_types .= 'i';
}

$stmt = $conn->prepare($count_sql);
if (!empty($count_params)) $stmt->bind_param($count_types, ...$count_params);
$stmt->execute();
$total_products = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);
$stmt->close();

// Lấy danh sách sản phẩm theo trang
$sql = "SELECT p.id, p.name, p.price, p.unit, p.stock, p.image, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($cat_filter !== '' && is_numeric($cat_filter)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_filter;
    $types .= 'i';
}

$sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// Hàm tạo URL cho phân trang/lọc
function buildAdminProductUrl($overrides = []) {
    $current_params = array_filter([
        'q' => $_GET['q'] ?? null,
        'cat' => $_GET['cat'] ?? null,
        'page' => $_GET['page'] ?? null,
    ]);
    $new_params = array_merge($current_params, $overrides);
    // Remove 'page' if it's 1 or null
    if (isset($new_params['page']) && $new_params['page'] <= 1) {
        unset($new_params['page']);
    }

    $query = http_build_query(array_filter($new_params));
    return '/shop/quan-tri/cua-hang' . ($query ? '?' . $query : '');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Quản trị - Quản lý sản phẩm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
    <style>
        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        #product-form-card {
            position: sticky;
            top: 20px;
        }

        .pagination a {
            margin: 0 4px;
            padding: 8px 12px;
            border-radius: 6px !important;
            text-decoration: none;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
        }

        .pagination a.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <?php include_header(); ?>

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

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Lưu sản phẩm thành công! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Xóa sản phẩm thành công! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <h3>Danh sách sản phẩm (<?= $total_products ?>)</h3>
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <form class="d-flex gap-2" method="GET" action="/shop/quan-tri/cua-hang">
                                <input type="text" name="q" class="form-control" placeholder="Tìm tên..." value="<?= htmlspecialchars($search) ?>" style="max-width:220px;">
                                <select name="cat" class="form-select" style="max-width:180px;">
                                    <option value="">Tất cả danh mục</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($cat_filter == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-outline-primary"><i class="fa fa-search"></i></button>
                                <?php if ($search || $cat_filter): ?>
                                    <a href="/shop/quan-tri/cua-hang" class="btn btn-outline-secondary">Xóa lọc</a>
                                <?php endif; ?>
                                <input type="hidden" name="page" value="<?= $page ?>">
                            </form>
                            <a href="<?= buildAdminProductUrl(['add' => 1]) ?>" class="btn btn-primary rounded-pill">
                                <i class="fa fa-plus me-2"></i>Thêm mới
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Giá</th>
                                    <th>Danh mục</th>
                                    <th>Tồn kho</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($products): foreach ($products as $p): ?>
                                        <tr>
                                            <td>SP<?= str_pad($p['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td>
                                                <img src="<?= htmlspecialchars($p['image'] ? '/shop/img/products/' . $p['image'] : '/shop/img/no-image.png') ?>" class="product-thumb" alt="">
                                            </td>
                                            <td><?= htmlspecialchars($p['name']) ?></td>
                                            <td><?= number_format($p['price'], 2) ?> $ / <?= htmlspecialchars($p['unit']) ?></td>
                                            <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                                            <td>
                                                <?php if ($p['stock'] > 0): ?>
                                                    <span class="badge bg-success">Còn hàng (<?= $p['stock'] ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Hết hàng</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= buildAdminProductUrl(['edit' => $p['id']]) ?>" class="btn btn-sm btn-outline-primary me-1" title="Sửa">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="<?= buildAdminProductUrl(['delete' => $p['id']]) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa sản phẩm này và ảnh của nó?')" title="Xóa">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">Không tìm thấy sản phẩm nào.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <small>Hiển thị <?= $offset + 1 ?>–<?= min($offset + $limit, $total_products) ?> trong tổng <?= $total_products ?> sản phẩm</small>
                                <div class="pagination mb-0">
                                    <a href="<?= buildAdminProductUrl(['page' => $page - 1]) ?>" class="rounded <?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="<?= buildAdminProductUrl(['page' => $i]) ?>" class="rounded <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                                    <?php endfor; ?>
                                    <a href="<?= buildAdminProductUrl(['page' => $page + 1]) ?>" class="rounded <?= $page >= $total_pages ? 'disabled' : '' ?>">&raquo;</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <?php if ($edit_product !== null): ?>
                        <div class="card border-secondary" id="product-form-card">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                <span><?= isset($_GET['edit']) ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?></span>
                                <a href="<?= buildAdminProductUrl(['edit' => null, 'add' => null]) ?>" class="btn-close btn-close-white"></a>
                            </div>
                            <div class="card-body">
                                <form action="/shop/quan-tri/cua-hang" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="save">
                                    <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_product['image'] ?? '') ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Tên sản phẩm *</label>
                                        <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Giá ($) *</label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="price" required value="<?= $edit_product['price'] ?? '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Đơn vị *</label>
                                        <select class="form-select" name="unit">
                                            <?php
                                            $units = ['kg', 'g', 'dozen', 'bunch', 'pack', 'liter', 'ml', 'bottle', 'box', 'piece', 'bag', 'jar', 'can'];
                                            $current = $edit_product['unit'] ?? 'kg';
                                            foreach ($units as $u): ?>
                                                <option value="<?= $u ?>" <?= ($current == $u) ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Danh mục *</label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">-- Chọn danh mục --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" <?= ($edit_product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Hình ảnh</label>
                                        <input type="file" class="form-control" name="image_file" accept="image/*">
                                        <?php if (!empty($edit_product['image'])): ?>
                                            <div class="mt-2">
                                                <small>Hình hiện tại:</small><br>
                                                <img src="/shop/img/products/<?= htmlspecialchars($edit_product['image']) ?>" style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;" onerror="this.src='/shop/img/no-image.png'">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Số lượng tồn kho *</label>
                                        <input type="number" min="0" class="form-control" name="stock" required value="<?= $edit_product['stock'] ?? 100 ?>">
                                        <small class="text-muted">0 = Hết hàng</small>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?= buildAdminProductUrl(['edit' => null, 'add' => null]) ?>" class="btn btn-outline-secondary">Hủy</a>
                                        <button type="submit" class="btn btn-primary">
                                            <?= isset($_GET['edit']) ? 'Cập nhật' : 'Thêm' ?> sản phẩm
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_footer(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tự động ẩn thông báo sau 5 giây
        setTimeout(() => document.querySelectorAll('.alert').forEach(a => a.remove()), 5000);
    </script>
</body>

</html>