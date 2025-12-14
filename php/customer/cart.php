<?php
require_once '../include/config.php';
require_role(['customer']);

// === BƯỚC 1: KIỂM TRA QUYỀN TRUY CẬP - CHỈ CUSTOMER ĐƯỢC VÀO ===
if (($_SESSION['role'] ?? 'guest') !== 'customer') {
    header("Location: /"); // Đã sửa: ../index.php -> /
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =============================================================================
   BƯỚC 2: LẤY HOẶC TẠO MỚI GIỎ HÀNG CỦA CUSTOMER
============================================================================= */
$stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    die("Không tìm thấy thông tin khách hàng.");
}
$customer_id = $customer['id'];

// Lấy cart_id hiện tại hoặc tạo mới nếu chưa có
$stmt = $conn->prepare("SELECT id FROM carts WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO carts (customer_id) VALUES (?)");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $cart_id = $conn->insert_id;
} else {
    $cart_id = $result->fetch_assoc()['id'];
}

/* =============================================================================
   BƯỚC 3: XỬ LÝ CẬP NHẬT SỐ LƯỢNG SẢN PHẨM TRONG GIỎ HÀNG
============================================================================= */
if ($_POST && isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $new_qty    = (int)$_POST['quantity'];

    // Kiểm tra sản phẩm có tồn tại và lấy thông tin tồn kho
    $stmt = $conn->prepare("SELECT name, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        setFlash('danger', 'Sản phẩm không tồn tại!');
    } else {
        $name  = $product['name'];
        $stock = (int)$product['stock'];

        if ($new_qty <= 0) {
            // Xóa sản phẩm khỏi giỏ hàng
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $cart_id, $product_id);
            $stmt->execute();
            setFlash('warning', "<strong>$name</strong> đã bị xóa khỏi giỏ hàng.");
            header("Location: " . BASE_URL . "gio-hang");
            exit;
        } elseif ($new_qty > $stock) {
            // Hết hàng hoặc vượt quá tồn kho
            if ($stock <= 0) {
                $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $cart_id, $product_id);
                $stmt->execute();
                setFlash('danger', "<strong>$name</strong> đã hết hàng và bị xóa.");
            } else {
                $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE quantity = ?");
                $stmt->bind_param("iiii", $cart_id, $product_id, $stock, $stock);
                $stmt->execute();
                setFlash('warning', "Chỉ còn $stock <strong>$name</strong>. Đã điều chỉnh số lượng.");
            }
        } else {
            // Cập nhật số lượng bình thường
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) 
                                    VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
            $stmt->bind_param("iii", $cart_id, $product_id, $new_qty);
            $stmt->execute();
            setFlash('success', "Đã cập nhật <strong>$name</strong> × $new_qty");
        }
    }

    header("Location: " . BASE_URL . "gio-hang");
    exit;
}

/* =============================================================================
   BƯỚC 4: XỬ LÝ MÃ GIẢM GIÁ (COUPON)
============================================================================= */
if (isset($_POST['coupon_code']) && trim($_POST['coupon_code']) !== '') {
    $code = trim($_POST['coupon_code']);

    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();

    if ($coupon) {
        $_SESSION['applied_coupon'] = $coupon;
        setFlash('success', "Áp dụng mã <strong>$code</strong> thành công!");
    } else {
        unset($_SESSION['applied_coupon']);
        setFlash('danger', "Mã giảm giá không hợp lệ hoặc đã hết hạn!");
    }
    header("Location: " . BASE_URL . "gio-hang");
    exit;
}

// Xóa mã giảm giá
if (isset($_GET['remove_coupon'])) {
    unset($_SESSION['applied_coupon']);
    setFlash('info', "Đã xóa mã giảm giá.");
    header("Location: " . BASE_URL . "gio-hang");
    exit;
}

/* =============================================================================
   BƯỚC 5: LẤY DANH SÁCH SẢN PHẨM TRONG GIỎ VÀ TÍNH TOÁN GIÁ
============================================================================= */
$stmt = $conn->prepare("
    SELECT ci.product_id, ci.quantity, p.name, p.price, p.image, p.stock 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.cart_id = ?
");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$subtotal = 0;
$shipping = 30000; // Phí ship cố định

while ($item = $cart_items->fetch_assoc()) {
    // Kiểm tra và tự động xử lý sản phẩm hết hàng hoặc vượt tồn kho
    if ($item['stock'] == 0 || $item['quantity'] > $item['stock']) {
        $available = $item['stock'];

        if ($available <= 0) {
            $stmt2 = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $stmt2->bind_param("ii", $cart_id, $item['product_id']);
            $stmt2->execute();
            setFlash('danger', "<strong>{$item['name']}</strong> đã hết hàng và bị xóa khỏi giỏ.");
            header("Location: " . BASE_URL . "gio-hang");
            exit;
            continue;
        } else {
            $stmt2 = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
            $stmt2->bind_param("iii", $available, $cart_id, $item['product_id']);
            $stmt2->execute();
            $item['quantity'] = $available;
            setFlash('warning', "Chỉ còn {$item['stock']} <strong>{$item['name']}</strong>. Đã tự động điều chỉnh.");
        }
    }

    $subtotal += $item['price'] * $item['quantity'];
}
$cart_items->data_seek(0); // Reset con trỏ để hiển thị lại

// Tính giảm giá từ coupon (nếu có)
$discount = 0;
if (isset($_SESSION['applied_coupon'])) {
    $c = $_SESSION['applied_coupon'];
    $discount = $c['discount_type'] === 'percent'
        ? $subtotal * ($c['discount_value'] / 100)
        : $c['discount_value'];
}

$final_total = max(0, $subtotal + $shipping - $discount);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Giỏ hàng - Fruitables Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .quantity-btn {
            width: 38px;
            height: 38px;
        }

        .empty-cart {
            text-align: center;
            padding: 100px 20px;
        }

        .stock-warning {
            color: #d4a017;
            font-size: 0.9em;
        }

        .table-responsive.scrollable-cart {
            max-height: 500px;
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

        .table-light {
            background-color: #f8f9fa !important;
        }
    </style>
</head>

<body>

    <?php include_header(); ?>

    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Giỏ hàng của bạn</h1>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">

            <?= getFlash() ?>

            <?php if ($cart_items->num_rows == 0): ?>
                <div class="empty-cart">
                    <i class="fa fa-shopping-cart fa-5x text-secondary mb-4"></i>
                    <h3>Giỏ hàng trống</h3>
                    <p>Bạn chưa thêm sản phẩm nào vào giỏ hàng.</p>
                    <a href="/shop/php" class="btn btn-primary rounded-pill py-3 px-5">Tiếp tục mua sắm</a> </div>

            <?php else: ?>
                <div class="table-responsive mb-5 scrollable-cart">
                    <table class="table align-middle fixed-header">
                        <thead class="table-light">
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Tổng</th>
                                <th>Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $cart_items->fetch_assoc()):
                                $item_total   = $item['price'] * $item['quantity'];
                                $out_of_stock = $item['stock'] == 0;
                                $low_stock    = $item['stock'] > 0 && $item['quantity'] >= $item['stock'];
                            ?>
                                <tr class="<?= $out_of_stock ? 'table-danger' : '' ?>">
                                    <td>
                                        <a href="/products/<?= $item['product_id'] ?>"> <img src="../../img/products/<?= $item['image'] ?: 'no-image.jpg' ?>"
                                                class="rounded-circle"
                                                style="width: 80px; height: 80px; object-fit: cover;"
                                                alt="<?= htmlspecialchars($item['name']) ?>">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="/products/<?= $item['product_id'] ?>" class="text-dark text-decoration-none fw-bold"> <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                        <?php if ($out_of_stock): ?>
                                            <br><span class="text-danger fw-bold">Hết hàng</span>
                                        <?php elseif ($low_stock): ?>
                                            <br><small class="stock-warning">Chỉ còn <?= $item['stock'] ?> sản phẩm</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($item['price']) ?>đ</td>

                                    <td>
                                        <form method="post" action="<?php echo BASE_URL; ?>gio-hang" class="d-flex align-items-center"> <div class="input-group" style="width: 140px;">
                                                <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>"
                                                    class="btn btn-sm btn-outline-secondary quantity-btn"
                                                    <?= $out_of_stock ? 'disabled' : '' ?>>–</button>
                                                <input type="text" class="form-control text-center border-0 bg-light"
                                                    value="<?= $item['quantity'] ?>" readonly>
                                                <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>"
                                                    class="btn btn-sm btn-outline-secondary quantity-btn"
                                                    <?= $out_of_stock || $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            </div>
                                        </form>
                                    </td>

                                    <td><?= number_format($item_total) ?>đ</td>

                                    <td>
                                        <form method="post" action="<?php echo BASE_URL; ?>gio-hang"> <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" name="quantity" value="0" class="btn btn-sm btn-danger">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    <form method="post" action="<?php echo BASE_URL; ?>gio-hang" class="d-inline"> <input type="text" name="coupon_code" class="form-control d-inline-block" style="width: 250px;"
                            placeholder="Nhập mã giảm giá" required>
                        <button class="btn btn-outline-primary rounded-pill ms-2">Áp dụng</button>
                    </form>

                    <?php if (isset($_SESSION['applied_coupon'])): ?>
                        <span class="ms-3 text-success fw-bold">
                            Đã áp dụng: <?= htmlspecialchars($_SESSION['applied_coupon']['code']) ?>
                            <a href="/cart?remove_coupon=1" class="text-danger ms-2">[Xóa]</a> </span>
                    <?php endif; ?>
                </div>

                <div class="row justify-content-end">
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="bg-light rounded p-4">
                            <h5 class="mb-4">Tổng thanh toán</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tạm tính:</span>
                                <strong><?= number_format($subtotal) ?>đ</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Phí vận chuyển:</span>
                                <strong><?= number_format($shipping) ?>đ</strong>
                            </div>
                            <?php if ($discount > 0): ?>
                                <div class="d-flex justify-content-between text-success mb-2">
                                    <span>Giảm giá:</span>
                                    <strong>-<?= number_format($discount) ?>đ</strong>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fs-5">Tổng cộng:</h5>
                                <h5 class="text-primary fs-4"><?= number_format($final_total) ?>đ</h5>
                            </div>
                            <a href="<?php echo BASE_URL; ?>thanh-toan" class="btn btn-primary rounded-pill w-100 py-3 mt-3"> Tiến hành thanh toán
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include_footer(); ?>
    <a href="#" class="btn btn-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>