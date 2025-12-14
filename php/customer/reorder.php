<?php
require_once '../include/config.php';
require_role(['customer']);

/* ==========================================================================
   1. LẤY THÔNG TIN NGƯỜI DÙNG & KHỞI TẠO GIỎ HÀNG
   ========================================================================== */
$user_id      = (int)$_SESSION['user_id'];
$success      = $error = "";
$total_added  = 0; // Đếm số sản phẩm được thêm (dù đầy đủ hay một phần)

// Lấy customer_id từ user_id
$stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cust = $stmt->get_result()->fetch_assoc();
if (!$cust) die("Không tìm thấy thông tin khách hàng.");
$customer_id = $cust['id'];

// Kiểm tra và tạo giỏ hàng nếu chưa có
$stmt = $conn->prepare("SELECT id FROM carts WHERE customer_id = ? LIMIT 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    // Tạo mới giỏ hàng
    $stmt = $conn->prepare("INSERT INTO carts (customer_id) VALUES (?)");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $cart_id = $conn->insert_id;
} else {
    $cart_id = $res->fetch_assoc()['id'];
}

/* ==========================================================================
   2. XỬ LÝ YÊU CẦU MUA LẠI ĐƠN HÀNG (khi có ?id=...)
   ========================================================================== */
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = (int)$_GET['id'];

    // Kiểm tra đơn hàng có tồn tại và thuộc về khách hàng này không
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $error = "Đơn hàng không tồn tại hoặc bạn không có quyền mua lại.";
    } else {
        // Lấy danh sách sản phẩm trong đơn hàng cũ
        $stmt = $conn->prepare("
            SELECT oi.product_id, oi.quantity, p.name, p.stock 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items = $stmt->get_result();

        // Mảng phân loại kết quả thêm vào giỏ
        $added_items   = []; // Đủ hàng
        $partial_items = []; // Chỉ còn 1 phần
        $out_of_stock  = []; // Hết hàng hoàn toàn

        while ($item = $items->fetch_assoc()) {
            $pid   = $item['product_id'];
            $name  = $item['name'];
            $want  = (int)$item['quantity'];
            $stock = (int)$item['stock'];

            if ($stock >= $want) {
                // Đủ hàng → thêm nguyên số lượng cũ
                $add_qty = $want;
                $added_items[] = "<strong>$name</strong> × $want";
            } elseif ($stock > 0) {
                // Còn ít hàng → thêm tối đa có thể
                $add_qty = $stock;
                $partial_items[] = "<strong>$name</strong>: chỉ còn $stock (đã thêm $stock)";
            } else {
                // Hết hàng → không thêm được
                $out_of_stock[] = "<strong>$name</strong>";
                continue; // Bỏ qua việc thêm vào giỏ
            }

            // Thêm / Cộng dồn số lượng vào giỏ hàng hiện tại
            $upsert = $conn->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");
            $upsert->bind_param("iii", $cart_id, $pid, $add_qty);
            $upsert->execute();
            $total_added += $add_qty; // Cộng dồn số lượng đã thêm vào biến đếm
        }


        // Tạo thông báo kết quả
        if ($total_added > 0) {
            $success = "Đã thêm <strong>$total_added</strong> sản phẩm từ đơn hàng cũ vào giỏ hàng!";

            if (!empty($added_items)) {
                $success .= "<br><small class='text-success'>Đầy đủ: " . implode(", ", $added_items) . "</small>";
            }
            if (!empty($partial_items)) {
                $success .= "<br><small class='text-warning'>Tồn kho hạn chế:<br>→ " . implode("<br>→ ", $partial_items) . "</small>";
            }
            if (!empty($out_of_stock)) {
                $success .= "<br><small class='text-danger'>Hết hàng (không thêm được):<br>→ " . implode("<br>→ ", $out_of_stock) . "</small>";
            }
        } else {
            $error = "Rất tiếc! Tất cả sản phẩm trong đơn hàng cũ hiện đã <strong>hết hàng</strong>.";
        }

        // Lưu thông báo flash để hiển thị sau khi redirect
        $_SESSION['reorder_flash'] = $success ?: $error;
        $_SESSION['reorder_type']  = $success ? 'success' : 'danger';
        $_SESSION['reorder_total_added'] = $total_added;

        // Quay lại trang trạng thái mua lại
        header("Location: " . SITE_URL . "mua-lai/trang-thai");
        exit();
    }
}

/* ==========================================================================
   3. LẤY THÔNG BÁO FLASH (sau khi redirect)
   ========================================================================== */
if (isset($_SESSION['reorder_flash'])) {
    $success = $_SESSION['reorder_flash'];
    $type    = $_SESSION['reorder_type'] ?? 'success';
    $total_added = $_SESSION['reorder_total_added'] ?? 0; // Lấy lại số lượng đã thêm
    unset($_SESSION['reorder_flash'], $_SESSION['reorder_type'], $_SESSION['reorder_total_added']);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Mua Lại Đơn Hàng - Fruitables</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
    <style>
        .reorder-card {
            max-width: 600px;
            margin: 100px auto;
            padding: 50px 30px;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .icon-lg {
            font-size: 90px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include_header(); ?>

    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Mua Lại Đơn Hàng</h1>
    </div>

    <div class="container py-5">
        <div class="reorder-card">

            <?php if ($success): ?>
                <i class="fas fa-check-circle icon-lg text-success"></i>
                <h3 class="text-success mb-4">Thêm vào giỏ hàng thành công!</h3>
                <div class="alert alert-success text-start fs-6">
                    <?= $success ?>
                </div>
                <div class="mt-4">
                    <a href="<?= SITE_URL ?>gio-hang" class="btn btn-primary rounded-pill px-5 py-3 me-3"> Xem Giỏ Hàng (<?= $total_added ?> sản phẩm)
                    </a>
                    <a href="<?= SITE_URL ?>lich-su-mua-hang" class="btn btn-outline-secondary rounded-pill px-5 py-3"> Quay Lại Lịch Sử Đơn Hàng
                    </a>
                </div>

            <?php elseif ($error): ?>
                <i class="fas fa-times-circle icon-lg text-danger"></i>
                <h3 class="text-danger mb-4">Không thể mua lại đơn hàng</h3>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
                <a href="<?= SITE_URL ?>lich-su-mua-hang" class="btn btn-primary rounded-pill px-5 py-3 mt-3"> Quay Lại Lịch Sử Đơn Hàng
                </a>

            <?php else: ?>
                <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;"></div>
                <h4 class="mt-4">Đang xử lý yêu cầu mua lại...</h4>
                <p>Vui lòng chờ trong giây lát</p>
            <?php endif; ?>

        </div>
    </div>

    <?php include_footer(); ?>
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">
        <i class="fa fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php mysqli_close($conn); ?>