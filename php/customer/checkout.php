<?php
require_once '../include/config.php';
require_role(['customer']);
$user_id = (int)$_SESSION['user_id'];

// ==================================================================
// 1. LẤY THÔNG TIN KHÁCH HÀNG
// ==================================================================
$stmt = $conn->prepare("
    SELECT c.id AS customer_id, c.phone, c.address,
           u.username AS name, u.email
    FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE c.user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$customer) {
    setFlash('danger', 'Không tìm thấy thông tin khách hàng!');
    header("Location: ../customer/cart.php");
    exit;
}

// ==================================================================
// 2. LẤY CART_ID
// ==================================================================
$stmt = $conn->prepare("SELECT id FROM carts WHERE customer_id = ?");
$stmt->bind_param("i", $customer['customer_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    setFlash('warning', 'Giỏ hàng trống!');
    header("Location: ../customer/cart.php");
    exit;
}
$cart_id = $result->fetch_assoc()['id'];
$stmt->close();

// ==================================================================
// 3. LẤY SẢN PHẨM TRONG GIỎ + KIỂM TRA TỒN KHO
// ==================================================================
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image, ci.quantity, p.stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.cart_id = ?
");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$cart_items_result = $stmt->get_result();

$subtotal = 0;
$items = [];
$has_stock_alert = false;

while ($item = $cart_items_result->fetch_assoc()) {
    if ($item['stock'] < $item['quantity']) {
        $available = $item['stock'];
        if ($available <= 0) {
            $conn->query("DELETE FROM cart_items WHERE cart_id = $cart_id AND product_id = {$item['id']}");
            setFlash('danger', "<strong>{$item['name']}</strong> đã hết hàng và bị xóa khỏi giỏ hàng.");
            $has_stock_alert = true;
            continue;
        } else {
            $conn->query("UPDATE cart_items SET quantity = $available WHERE cart_id = $cart_id AND product_id = {$item['id']}");
            $item['quantity'] = $available;
            setFlash('warning', "Sản phẩm <strong>{$item['name']}</strong> chỉ còn $available. Đã điều chỉnh số lượng.");
            $has_stock_alert = true;
        }
    }
    $subtotal += $item['price'] * $item['quantity'];
    $items[] = $item;
}
$stmt->close();

if ($has_stock_alert && empty($_POST)) {
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$shipping = 30000;
$total = $subtotal + $shipping;

// Biến đặt hàng
$order_success = false;
$order_id = null;
$qr_code_url = '';

// ==================================================================
// 4. XỬ LÝ ĐẶT HÀNG
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    $payment = $_POST['payment'] ?? 'Cash'; // BankTransfer | MoMo | ZaloPay | Cash

    if (empty($name) || empty($phone) || empty($address)) {
        setFlash('danger', 'Vui lòng điền đầy đủ thông tin bắt buộc!');
    } else {
        $conn->autocommit(false);
        try {
            // === CẬP NHẬT THÔNG TIN KHÁCH HÀNG ===
            $stmt = $conn->prepare("UPDATE customers SET phone = ?, address = ? WHERE id = ?");
            $stmt->bind_param("ssi", $phone, $address, $customer['customer_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
            $stmt->execute();
            $stmt->close();

            // === TẠO ĐƠN HÀNG ===
            $stmt = $conn->prepare("
                INSERT INTO orders 
                (customer_id, cart_id, subtotal, shipping_cost, total, payment_method, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Processing')
            ");
            $stmt->bind_param("iididss", $customer['customer_id'], $cart_id, $subtotal, $shipping, $total, $payment, $notes);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $order_code = "DH" . str_pad($order_id, 6, "0", STR_PAD_LEFT);
            $stmt->close();

            // Cập nhật mã đơn hàng
            $conn->query("UPDATE orders SET order_code = '$order_code' WHERE id = $order_id");

            // === CHI TIẾT ĐƠN HÀNG + TRỪ TỒN KHO ===
            $stmt_item  = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($items as $item) {
                $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                $stmt_item->execute();

                $stmt_stock->bind_param("ii", $item['quantity'], $item['id']);
                $stmt_stock->execute();
            }
            $stmt_item->close();
            $stmt_stock->close();

            // Xóa giỏ hàng
            $conn->query("DELETE FROM cart_items WHERE cart_id = $cart_id");
            $conn->commit();
            $conn->autocommit(true);

            $order_success = true;
            $_SESSION['username'] = $name;

            // =================================================================
            // === TẠO LINK THANH TOÁN THEO PHƯƠNG THỨC ===
            // =================================================================
            if ($payment === 'BankTransfer') {
                $bank_info = [
                    'bank' => 'MB Bank',
                    'account_name' => 'LE THI KIEU TRANG',
                    'account_number' => '0354491100',
                    'bin' => 'MBBANK'
                ];
                $content = $order_code;
                $qr_code_url = "https://img.vietqr.io/image/{$bank_info['bin']}-{$bank_info['account_number']}-qr_only.png?amount={$total}&addInfo=" . urlencode($content) . "&accountName=" . urlencode($bank_info['account_name']);
            } elseif ($payment === 'MoMo') {
                // MoMo Deep Link (mở app MoMo ngay lập tức)
                $qr_code_url = "https://me.momo.vn/transfer?amount={$total}&note=" . urlencode($order_code) . "&receiver=0354491100"; // số điện thoại nhận tiền

                // Hoặc dùng QR chính thức của MoMo (đẹp hơn)
                $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode("2|99|0354491100|||0|0|{$total}|{$order_code}|transfer_myqr");
            } elseif ($payment === 'ZaloPay') {
                $bank_info = [
                    'bank'       => 'ZaloPay',
                    'account_name' => 'LE THI KIEU TRANG',
                    'account_number' => '0354491100',
                    'bin'        => '970415'  // Mã BIN chính thức của ZaloPay
                ];
                $content = $order_code; // rất quan trọng: phải đúng nội dung này mới tự động đối soát

                $qr_code_url = "https://img.vietqr.io/image/{$bank_info['bin']}-{$bank_info['account_number']}-qr_only.png?amount={$total}&addInfo=" . urlencode($content) . "&accountName=" . urlencode($bank_info['account_name']);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(true);
            setFlash('danger', 'Đặt hàng thất bại! Vui lòng thử lại.');
            error_log("Checkout error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Thanh toán - Fruitables Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="/shop/css/bootstrap.min.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
    <style>
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: 20px 0;
        }

        .bank-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            font-size: 15px;
        }

        .order-id {
            font-size: 2rem;
            font-weight: bold;
            color: #2d6a4f;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include_header(); ?>

    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Thanh toán đơn hàng</h1>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <?= getFlash() ?>

            <?php if ($order_success): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                    <h2>Cảm ơn quý khách!</h2>
                    <p>Đơn hàng của bạn đã được đặt thành công.</p>
                    <p class="order-id">#<?= htmlspecialchars($order_code ?? 'DH' . str_pad($order_id, 6, '0', STR_PAD_LEFT)) ?></p>

                    <?php if ($payment === 'Transfer' && $qr_code_url): ?>
                        <div class="qr-container">
                            <h4><i class="fas fa-qrcode"></i> Quét QR để thanh toán ngay</h4>
                            <img src="<?= $qr_code_url ?>" alt="QR Thanh toán" class="img-fluid" style="max-width: 280px;">
                            <div class="bank-info mt-3">
                                <p><strong>Ngân hàng:</strong> MB Bank</p>
                                <p><strong>Chủ tài khoản:</strong> LE THI KIEU TRANG</p>
                                <p><strong>Số tài khoản:</strong> 0354491100</p>
                                <p><strong>Số tiền:</strong> <span class="text-danger fs-5"><?= number_format($total) ?>₫</span></p>
                                <p><strong>Nội dung chuyển khoản:</strong>
                                    <code class="bg-white px-3 py-2 rounded" style="font-size: 1.1rem;">
                                        <?= htmlspecialchars($order_code) ?>
                                    </code>
                                </p>
                                <small class="text-muted">Vui lòng chuyển khoản đúng nội dung để đơn được xử lý nhanh</small>
                            </div>
                        </div>
                        <p class="mt-3">Sau khi chuyển khoản, đơn hàng sẽ được xác nhận trong vòng 5-10 phút.</p>
                    <?php elseif ($payment === 'MoMo' && $qr_code_url): ?>
                        <div class="qr-container">
                            <h4><img src="/shop/img/momo.png" width="30" class="me-2"> Thanh toán qua MoMo</h4>
                            <img src="<?= $qr_code_url ?>" alt="QR MoMo" class="img-fluid" style="max-width: 280px;">
                            <div class="bank-info mt-3">
                                <p><strong>Số điện thoại MoMo:</strong> 0354491100</p>
                                <p><strong>Số tiền:</strong> <span class="text-danger fs-5"><?= number_format($total) ?>₫</span></p>
                                <p><strong>Nội dung:</strong> <code><?= htmlspecialchars($order_code) ?></code></p>
                                <p><small>Quét mã QR bằng ứng dụng MoMo để thanh toán ngay</small></p>
                            </div>
                            <a href="momo://pay?amount=<?= $total ?>&note=<?= urlencode($order_code) ?>&receiver=0354491100" class="btn btn-danger mt-3">
                                Mở ứng dụng MoMo
                            </a>
                        </div>

                    <?php elseif ($payment === 'ZaloPay' && $qr_code_url): ?>
                        <div class="qr-container">
                            <h4><img src="/shop/img/zalopay.jpeg" width="30" class="me-2"> Thanh toán qua ZaloPay</h4>
                            <img src="<?= $qr_code_url ?>" alt="QR ZaloPay" class="img-fluid" style="max-width: 280px;">
                            <div class="bank-info mt-3">
                                <p><strong>Số tiền:</strong> <span class="text-danger fs-5"><?= number_format($total) ?>₫</span></p>
                                <p><strong>Mô tả:</strong> <?= htmlspecialchars("Thanh toán đơn {$order_code}") ?></p>
                                <p><small>Quét mã QR bằng ứng dụng ZaloPay</small></p>
                            </div>
                            <a href="zalopay://pay?amount=<?= $total ?>&description=<?= urlencode("Thanh toán đơn {$order_code}") ?>" class="btn btn-primary mt-3">
                                Mở ứng dụng ZaloPay
                            </a>
                        </div>

                    <?php elseif ($payment === 'Cash'): ?>
                        <p>Chúng tôi sẽ giao hàng và thu tiền tại nhà (COD).</p>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="/shop/lich-su-mua-hang" class="btn btn-primary rounded-pill px-5 py-3">
                            Xem chi tiết đơn hàng
                        </a>
                        <a href="/shop/php/" class="btn btn-outline-primary rounded-pill px-5 py-3 ms-3">
                            Về trang chủ
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Form thanh toán bình thường -->
                <h2 class="mb-5 text-center text-primary">Thông tin giao hàng & thanh toán</h2>
                <form method="POST" action="">
                    <div class="row g-5">
                        <div class="col-lg-7">
                            <!-- Form thông tin giao hàng (giữ nguyên như cũ) -->
                            <div class="bg-light p-4 rounded">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($customer['address'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Ghi chú</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Giao giờ hành chính, để trước cửa..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="bg-light p-4 rounded">
                                <h4 class="mb-4">Đơn hàng của bạn</h4>
                                <div class="table-responsive mb-4">
                                    <table class="table">
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <img src="/shop/img/products/<?= $item['image'] ?>" class="img-fluid rounded" style="width: 80px; height: 80px; object-fit: cover;" alt="<?= htmlspecialchars($item['name']) ?>"> × <?= $item['quantity'] ?>
                                                    </td>
                                                    <td class="text-end"><?= number_format($item['price'] * $item['quantity']) ?>đ</td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td colspan="2">
                                                    <hr>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Tạm tính</td>
                                                <td class="text-end"><strong><?= number_format($subtotal) ?>đ</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Phí vận chuyển</td>
                                                <td class="text-end"><strong><?= number_format($shipping) ?>đ</strong></td>
                                            </tr>
                                            <tr class="table-primary">
                                                <td class="fw-bold fs-5">TỔNG CỘNG</td>
                                                <td class="text-end text-primary fw-bold fs-4"><?= number_format($total) ?>đ</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment" id="transfer" value="BankTransfer" checked>
                                        <label class="form-check-label fw-bold" for="transfer">
                                            Chuyển khoản ngân hàng (QR Code MB Bank)
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment" id="momo" value="MoMo">
                                        <label class="form-check-label fw-bold text-danger" for="momo">
                                            <img src="/shop/img/momo.png" width="20" class="me-2">
                                            Thanh toán qua Ví MoMo
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment" id="zalopay" value="ZaloPay">
                                        <label class="form-check-label fw-bold text-primary" for="zalopay">
                                            <img src="/shop/img/zalopay.jpeg" width="20" class="me-2">
                                            Thanh toán qua ZaloPay
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment" id="cash" value="Cash">
                                        <label class="form-check-label fw-bold" for="cash">
                                            Thanh toán khi nhận hàng (COD)
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 mt-4 rounded-pill fw-bold fs-5">
                                    XÁC NHẬN ĐẶT HÀNG
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php include_footer(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>