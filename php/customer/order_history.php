<?php

/********************************************************************
 * 1. KHỞI TẠO & KIỂM TRA PHÂN QUYỀN
 ********************************************************************/
require_once '../include/config.php';
require_role(['customer']); // Chỉ khách hàng mới được truy cập trang này

$user_id = $_SESSION['user_id'] ?? 0;

/********************************************************************
 * 2. LẤY THÔNG TIN KHÁCH HÀNG (customer_id + địa chỉ giao hàng)
 ********************************************************************/
$query_customer = "SELECT c.id AS customer_id, COALESCE(c.address, 'Chưa cung cấp') AS address
                   FROM customers c
                   WHERE c.user_id = ?";
$stmt_cust = mysqli_prepare($conn, $query_customer);
mysqli_stmt_bind_param($stmt_cust, "i", $user_id);
mysqli_stmt_execute($stmt_cust);
$cust_result = mysqli_stmt_get_result($stmt_cust);
$customer = mysqli_fetch_assoc($cust_result);

if (!$customer) {
    die("Không tìm thấy thông tin khách hàng.");
}

$customer_id      = $customer['customer_id'];
$customer_address = $customer['address'];

/********************************************************************
 * 3. LẤY DANH SÁCH ĐƠN HÀNG CỦA KHÁCH HÀNG
 ********************************************************************/
$orders_query = "SELECT 
                    id,
                    status,
                    created_at,
                    total,
                    COALESCE(subtotal, 0) AS subtotal,
                    COALESCE(shipping_cost, 30000) AS shipping_cost
                 FROM orders
                 WHERE customer_id = ?
                 ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $timestamp = strtotime($row['created_at']);

    // Định dạng ngày giờ và tạo mã đơn hàng
    $row['order_date_formatted'] = date('d/m/Y', $timestamp);
    $row['order_time']           = date('H:i', $timestamp);
    $row['order_code']           = '#ORD-' . date('Y', $timestamp) . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);

    $orders[] = $row;
}
mysqli_free_result($orders_result);

/********************************************************************
 * 4. HÀM LẤY CHI TIẾT SẢN PHẨM TRONG ĐƠN HÀNG
 ********************************************************************/
function getOrderItems($conn, $order_id)
{
    $q = "SELECT oi.quantity, p.name, p.price, p.unit, p.image
          FROM order_items oi
          JOIN products p ON oi.product_id = p.id
          WHERE oi.order_id = ?";
    $stmt = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $items = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $items[] = $r;
    }
    mysqli_free_result($res);
    return $items;
}

/********************************************************************
 * 5. HÀM TRẢ VỀ CLASS BADGE THEO TRẠNG THÁI ĐƠN HÀNG
 ********************************************************************/
function getStatusBadgeClass($status)
{
    return match ($status) {
        'Delivered'  => 'bg-success',
        'Shipped'    => 'bg-warning text-dark',
        'Cancelled'  => 'bg-secondary',
        default      => 'bg-info text-dark', // Pending hoặc trạng thái khác
    };
}

/********************************************************************
 * 6. HÀM TRẢ VỀ TÊN TRẠNG THÁI TIẾNG VIỆT
 ********************************************************************/
function getStatusText($status)
{
    return match ($status) {
        'Delivered'  => 'Đã giao',
        'Shipped'    => 'Đang giao',
        'Cancelled'  => 'Đã hủy',
        default      => 'Chờ xử lý',
    };
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Lịch sử đơn hàng - Fruitables</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
    <style>
        .table-responsive.scrollable-order-history {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .table.fixed-header thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
</head>

<body>
    <?php include_header(); ?>

    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Lịch sử đơn hàng</h1>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-12">

                    <ul class="nav nav-tabs mb-5">
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>tai-khoan"><i class='bx bx-user me-2'></i>Thông tin tài khoản</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>doi-mat-khau"><i class='bx bx-lock-alt me-2'></i>Đổi mật khẩu</a></li>
                        <li class="nav-item"><a class="nav-link active" href="<?= SITE_URL ?>lich-su-mua-hang"><i class='bx bx-history me-2'></i>Lịch sử đơn hàng</a></li>
                    </ul>

                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <h4>Bạn chưa có đơn hàng nào</h4>
                            <a href="<?= SITE_URL ?>cua-hang" class="btn btn-primary rounded-pill py-3 px-5 mt-3">Tiếp tục mua sắm</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive scrollable-order-history">
                            <table class="table table-bordered align-middle fixed-header">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Ngày đặt</th>
                                        <th>Sản phẩm</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order):
                                        $order_items = getOrderItems($conn, $order['id']);
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order['order_code']) ?></td>
                                            <td><?= htmlspecialchars($order['order_date_formatted']) ?></td>

                                            <td>
                                                <?php foreach ($order_items as $idx => $item): ?>
                                                    <?php if ($idx < 2): ?>
                                                        <div class="d-flex align-items-center <?= $idx > 0 ? 'mt-2' : '' ?>">
                                                            <img src="/shop/img/products/<?= htmlspecialchars($item['image'] ?: 'fruite-item-5.jpg') ?>"
                                                                class="img-fluid rounded" style="width:50px;height:50px;" alt="">
                                                            <div class="ms-3">
                                                                <p class="mb-0 fw-bold"><?= htmlspecialchars($item['name']) ?></p>
                                                                <small class="text-muted">
                                                                    <?= $item['quantity'] . $item['unit'] . ' × ' . number_format($item['price'], 0, ',', '.') ?>đ
                                                                </small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($idx == 1 && count($order_items) > 2): ?>
                                                        <p class="mb-0 mt-2 text-muted small">... và <?= count($order_items) - 2 ?> sản phẩm khác</p>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </td>

                                            <td class="fw-bold text-danger fs-5"><?= number_format($order['total'], 0, ',', '.') ?>đ</td>

                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($order['status']) ?>">
                                                    <?= getStatusText($order['status']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <button class="btn btn-primary btn-sm rounded-pill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#orderDetail<?= $order['id'] ?>">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center mt-5">
                            <a href="<?= SITE_URL ?>cua-hang" class="btn btn-primary rounded-pill py-3 px-5">
                                Tiếp tục mua sắm
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($orders as $order):
        $order_items   = getOrderItems($conn, $order['id']);
        $subtotal      = $order['subtotal'];
        $shipping_cost = $order['shipping_cost'];
        $total         = $order['total'];
    ?>
        <div class="modal fade" id="orderDetail<?= $order['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chi tiết đơn hàng <?= htmlspecialchars($order['order_code']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p><strong>Ngày đặt hàng:</strong> <?= $order['order_date_formatted'] . ' ' . $order['order_time'] ?></p>
                        <p><strong>Trạng thái:</strong>
                            <span class="badge <?= getStatusBadgeClass($order['status']) ?>">
                                <?= getStatusText($order['status']) ?>
                            </span>
                        </p>
                        <p><strong>Địa chỉ giao hàng:</strong> <?= htmlspecialchars($customer_address) ?></p>
                        <hr>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item):
                                    $item_total = $item['price'] * $item['quantity'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= $item['quantity'] . ' ' . $item['unit'] ?></td>
                                        <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                        <td><?= number_format($item_total, 0, ',', '.') ?>đ</td>
                                    </tr>
                                <?php endforeach; ?>

                                <tr>
                                    <td colspan="3" class="text-end">Tạm tính</td>
                                    <td><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Phí vận chuyển</td>
                                    <td><?= number_format($shipping_cost, 0, ',', '.') ?>đ</td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end fw-bold">Tổng cộng</td>
                                    <td class="fw-bold text-danger fs-5"><?= number_format($total, 0, ',', '.') ?>đ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <a href="/shop/mua-lai/<?= $order['id'] ?>" class="btn btn-primary">Mua lại</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php include_footer(); ?>

    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">
        <i class="fa fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php mysqli_close($conn); ?>