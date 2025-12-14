<?php
// Tên tệp: order-management.php

require_once '../include/config.php';
require_role(['admin']);

/* ========================================
    1. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
    ======================================== */
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $valid_statuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];

    // Kiểm tra tính hợp lệ của trạng thái mới
    if (in_array($new_status, $valid_statuses)) {
        // Sử dụng Prepared Statement
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công!";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật trạng thái: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Trạng thái đơn hàng không hợp lệ!";
    }

    $redirect_url = "/shop/quan-tri/don-hang";
if (!empty($_GET['page'])) {
    $redirect_url .= '?page=' . (int)$_GET['page'];
}
header("Location: $redirect_url");
    exit();
}

/* ========================================
    2. XỬ LÝ AJAX LẤY CHI TIẾT SẢN PHẨM (Nếu được gọi trực tiếp bằng order_id)
    ======================================== */
// Do file này đóng vai trò là cả trang quản lý và endpoint AJAX, 
// ta kiểm tra xem có phải yêu cầu AJAX đang tìm chi tiết đơn hàng không.
if (isset($_GET['action']) && $_GET['action'] === 'get_details' && isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'html' => '', 'error' => ''];
    $order_id = (int)$_GET['order_id'];

    if ($order_id > 0) {
        // Lấy chi tiết các sản phẩm trong đơn hàng
        $sql = "SELECT oi.quantity, oi.price, p.name 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items = $stmt->get_result();

        $html = '';
        if ($items->num_rows > 0) {
            // *** ĐIỀU CHỈNH: Bắt đầu và kết thúc bằng thẻ table, thêm thead ***
            $html .= '<table class="table table-bordered table-striped align-middle">';
            $html .= '<thead class="table-light">';
            $html .= '<tr><th>Sản phẩm</th><th class="text-center">SL</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            // *** END ĐIỀU CHỈNH ***

            while ($item = $items->fetch_assoc()) {
                $total_item_price = $item['quantity'] * $item['price'];

                // *** ĐIỀU CHỈNH: Thay thế li bằng tr, sử dụng td cho các cột ***
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
                $html .= '<td class="text-center">' . number_format($item['quantity']) . '</td>';
                $html .= '<td class="text-end">' . number_format($item['price']) . '₫</td>';
                $html .= '<td class="text-end"><strong class="text-primary">' . number_format($total_item_price) . '₫</strong></td>';
                $html .= '</tr>';
                // *** END ĐIỀU CHỈNH ***
            }

            // *** ĐIỀU CHỈNH: Đóng thẻ table ***
            $html .= '</tbody>';
            $html .= '</table>';
            $response['success'] = true;
            $response['html'] = $html;
        } else {
            $response['success'] = true;
            $response['html'] = '<p class="text-muted text-center">Đơn hàng không có sản phẩm nào.</p>';
        }
        $stmt->close();
    } else {
        $response['error'] = 'Mã đơn hàng không hợp lệ.';
    }

    echo json_encode($response);
    // Ngắt thực thi nếu đây là yêu cầu AJAX
    exit();
}

/* ========================================
    3. CÀI ĐẶT PHÂN TRANG
    ======================================== */
$limit = 7;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* ========================================
    4. LẤY DỮ LIỆU BỘ LỌC TỪ URL
    ======================================== */
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

/* ========================================
    5. ĐẾM TỔNG SỐ ĐƠN HÀNG (CHO PHÂN TRANG)
    ======================================== */
$count_sql = "SELECT COUNT(*) AS total FROM orders o
             LEFT JOIN customers cus ON o.customer_id = cus.id
             LEFT JOIN users u ON cus.user_id = u.id
             WHERE 1=1";
$count_params = [];
$count_types = "";
if ($search !== '') {
    $like = "%$search%";
    $count_sql .= " AND (o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $count_params[] = $like;
    $count_params[] = $like;
    $count_params[] = $like;
    $count_types .= "sss";
}
if ($status_filter !== '') {
    $count_sql .= " AND o.status = ?";
    $count_params[] = $status_filter;
    $count_types .= "s";
}
if ($from_date !== '') {
    $count_sql .= " AND DATE(o.created_at) >= ?";
    $count_params[] = $from_date;
    $count_types .= "s";
}
if ($to_date !== '') {
    $count_sql .= " AND DATE(o.created_at) <= ?";
    $count_params[] = $to_date;
    $count_types .= "s";
}

$stmt_count = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$total_orders = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);
$stmt_count->close();

/* ========================================
    6. LẤY DANH SÁCH ĐƠN HÀNG HIỂN THỊ
    ======================================== */
$sql = "SELECT o.id, o.created_at, o.total, o.status, o.payment_method,
             cus.id AS customer_id, u.username, u.email,
             cus.phone, cus.address
           FROM orders o
           LEFT JOIN customers cus ON o.customer_id = cus.id
           LEFT JOIN users u ON cus.user_id = u.id
           WHERE 1=1";
$params = [];
$types = "";
if ($search !== '') {
    $like = "%$search%";
    $sql .= " AND (o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}
if ($status_filter !== '') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($from_date !== '') {
    $sql .= " AND DATE(o.created_at) >= ?";
    $params[] = $from_date;
    $types .= "s";
}
if ($to_date !== '') {
    $sql .= " AND DATE(o.created_at) <= ?";
    $params[] = $to_date;
    $types .= "s";
}
$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

// Lấy lại danh sách đơn hàng cho JS (nếu cần)
$js_orders = [];
$orders->data_seek(0); // Đặt lại con trỏ kết quả về đầu
while ($order = $orders->fetch_assoc()) {
    $js_orders[$order['id']] = [
        'username' => htmlspecialchars($order['username'] ?? 'Khách vãng lai'),
        'email' => htmlspecialchars($order['email'] ?? 'Chưa cung cấp'),
        'phone' => htmlspecialchars($order['phone'] ?? 'Chưa cung cấp'),
        'address' => htmlspecialchars($order['address'] ?? 'Chưa cung cấp'),
        'total' => number_format($order['total']),
        'payment_method' => ($order['payment_method'] == 'BankTransfer' ? 'Chuyển khoản ngân hàng' : 
                    ($order['payment_method'] == 'MoMo' ? 'Ví MoMo' : 
                    ($order['payment_method'] == 'ZaloPay' ? 'ZaloPay' : 
                    ($order['payment_method'] == 'VNPay' ? 'VNPay' : 'Thanh toán khi nhận hàng (COD)')))),
    ];
}
$orders->data_seek(0); // Reset pointer for HTML display
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản trị - Quản lý đơn hàng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/shop/">
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
            <li class="breadcrumb-item"><a href="/shop/quan-tri/don-hang">Đơn hàng</span></li>
        </ol>
    </div>
    <div class="container-fluid py-5">
        <div class="container py-5">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="bg-light rounded p-3 p-md-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div>
                        <h4 class="mb-1">Quản lý</h4>
                        <small class="text-muted">Các trang quản trị sản phẩm, đánh giá, danh mục và đơn hàng</small>
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
                <div class="col-12">
                    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <h3>Danh sách đơn hàng (<?= $total_orders ?>)</h3>
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <form class="d-flex gap-2" method="GET" action="/shop/quan-tri/don-hang">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm đơn hàng..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="Processing" <?= $status_filter === 'Processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="Shipped" <?= $status_filter === 'Shipped' ? 'selected' : '' ?>>Đã giao vận chuyển</option>
                                    <option value="Delivered" <?= $status_filter === 'Delivered' ? 'selected' : '' ?>>Đã giao hàng</option>
                                    <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Ngày tạo</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Phương thức thanh toán</th>
                                    <th>Khách hàng</th>
                                    <th>Số điện thoại</th>
                                    <th>Địa chỉ</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $order['id'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td><?= number_format($order['total']) ?>₫</td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] == 'Processing' ? 'warning' : ($order['status'] == 'Shipped' ? 'info' : ($order['status'] == 'Delivered' ? 'success' : 'danger')) ?>">
                                                <?= $order['status'] == 'Processing' ? 'Đang xử lý' : ($order['status'] == 'Shipped' ? 'Đã giao vận chuyển' : ($order['status'] == 'Delivered' ? 'Đã giao hàng' : 'Đã hủy')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $payment_methods = [
                                                'COD' => 'Thanh toán khi nhận hàng',
                                                'BankTransfer' => 'Chuyển khoản ngân hàng',
                                                'MoMo' => 'Ví MoMo',
                                                'ZaloPay' => 'ZaloPay',
                                                'VNPay' => 'VNPay'
                                            ];
                                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($order['username'] ?? 'Khách vãng lai') ?></td>
                                        <td><?= htmlspecialchars($order['phone'] ?? 'Chưa cung cấp') ?></td>
                                        <td><?= htmlspecialchars($order['address'] ?? 'Chưa cung cấp') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary view-order-detail" 
                                                    data-order-id="<?= $order['id'] ?>">
                                                <i class="fa fa-eye"></i> Xem chi tiết
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php 
                            // Previous page link
                            if ($page > 1): 
                                $prev_page = $page - 1;
                                $prev_url = "/shop/quan-tri/don-hang?page=$prev_page";
                                if (!empty($search)) $prev_url .= "&search=" . urlencode($search);
                                if (!empty($status_filter)) $prev_url .= "&status=" . urlencode($status_filter);
                                if (!empty($from_date)) $prev_url .= "&from_date=" . urlencode($from_date);
                                if (!empty($to_date)) $prev_url .= "&to_date=" . urlencode($to_date);
                            ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $prev_url ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Page numbers
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if ($start > 1): ?>
                                <li class="page-item"><a class="page-link" href="/shop/quan-tri/don-hang?page=1">1</a></li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): 
                                $page_url = "/shop/quan-tri/don-hang?page=$i";
                                if (!empty($search)) $page_url .= "&search=" . urlencode($search);
                                if (!empty($status_filter)) $page_url .= "&status=" . urlencode($status_filter);
                                if (!empty($from_date)) $page_url .= "&from_date=" . urlencode($from_date);
                                if (!empty($to_date)) $page_url .= "&to_date=" . urlencode($to_date);
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $page_url ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="/shop/quan-tri/don-hang?page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
                            <?php endif; ?>
                            
                            <?php 
                            // Next page link
                            if ($page < $total_pages): 
                                $next_page = $page + 1;
                                $next_url = "/shop/quan-tri/don-hang?page=$next_page";
                                if (!empty($search)) $next_url .= "&search=" . urlencode($search);
                                if (!empty($status_filter)) $next_url .= "&status=" . urlencode($status_filter);
                                if (!empty($from_date)) $next_url .= "&from_date=" . urlencode($from_date);
                                if (!empty($to_date)) $next_url .= "&to_date=" . urlencode($to_date);
                            ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $next_url ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="orderModalLabel">
                    <i class="fa fa-shopping-cart me-2"></i>Chi tiết đơn hàng #<span id="modal-order-id"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Thông tin khách hàng & đơn hàng -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2"><i class="fa fa-user me-2"></i>Thông tin khách hàng</h6>
                        <p class="mb-1"><strong>Họ tên:</strong> <span id="modal-customer-name"></span></p>
                        <p class="mb-1"><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                        <p class="mb-1"><strong>Số điện thoại:</strong> <span id="modal-customer-phone"></span></p>
                        <p class="mb-1"><strong>Địa chỉ:</strong> <span id="modal-customer-address"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2"><i class="fa fa-info-circle me-2"></i>Thông tin đơn hàng</h6>
                        <p class="mb-1"><strong>Phương thức thanh toán:</strong> <span id="modal-payment-method"></span></p>
                        <p class="mb-1"><strong>Ngày đặt hàng:</strong> <span id="modal-order-date"></span></p>
                        <p class="mb-1"><strong>Trạng thái:</strong> 
                            <span id="modal-status" class="badge bg-warning"></span>
                        </p>
                    </div>
                </div>

                <hr>

                <h6><i class="fa fa-boxes me-2"></i>Danh sách sản phẩm</h6>
                <div id="order-items-list">
                    <p class="text-center text-muted">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Đang tải chi tiết sản phẩm...
                    </p>
                </div>

                <hr>

                <div class="text-end">
                    <h5>Tổng tiền: <span id="modal-total-price" class="text-primary"></span>₫</h5>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="fa fa-print"></i> In đơn hàng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script xử lý xem chi tiết đơn hàng -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Dữ liệu đơn hàng được PHP render sẵn để dùng nhanh (tránh gọi thêm AJAX cho info cơ bản)
    const ordersData = <?= json_encode($js_orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const orderModal = new bootstrap.Modal('#orderModal');

    $(document).on('click', '.view-order-detail', function () {
        const orderId = $(this).data('order-id');
        const data = ordersData[orderId] || {};

        // Điền thông tin nhanh
        $('#modal-order-id').text(orderId);
        $('#modal-customer-name').text(data.username || 'Khách vãng lai');
        $('#modal-customer-email').text(data.email || 'Chưa cung cấp');
        $('#modal-customer-phone').text(data.phone || 'Chưa cung cấp');
        $('#modal-customer-address').text(data.address || 'Chưa cung cấp');
        $('#modal-payment-method').text(data.payment_method || 'COD');
        $('#modal-total-price').text(data.total);

        // Lấy ngày + trạng thái từ dòng bảng
        const $row = $(this).closest('tr');
        $('#modal-order-date').text($row.find('td:eq(1)').text());
        const $badge = $row.find('.badge');
        $('#modal-status').text($badge.text()).attr('class', 'badge ' + $badge.attr('class').match(/bg-\w+/)[0]);

        // Loading sản phẩm
        $('#order-items-list').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Đang tải sản phẩm...</p>
            </div>
        `);

        // QUAN TRỌNG: DÙNG ĐƯỜNG DẪN TUYỆT ĐỐI HOẶC ĐẦY ĐỦ ĐẾN FILE HIỆN TẠI
        const currentUrl = window.location.pathname + window.location.search;
        const ajaxUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'action=get_details&order_id=' + orderId;

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'json',
            timeout: 10000, // 10 giây
            success: function(res) {
                if (res.success) {
                    $('#order-items-list').html(res.html);
                } else {
                    $('#order-items-list').html('<div class="alert alert-warning">Không có sản phẩm nào trong đơn hàng.</div>');
                }
            },
            error: function(xhr, status, err) {
                console.error("AJAX Error:", status, err);
                console.error("Response Text:", xhr.responseText); // xem lỗi thật sự là gì

                $('#order-items-list').html(`
                    <div class="alert alert-danger">
                        <strong>Lỗi tải sản phẩm!</strong><br>
                        Vui lòng <a href="javascript:location.reload()">tải lại trang</a> và thử lại.
                    </div>
                `);
            }
        });

        orderModal.show();
    });
</script>
</body>

</html>