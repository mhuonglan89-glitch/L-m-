<?php
require_once '../include/config.php';

// === YÊU CẦU QUYỀN TRUY CẬP ===
// Chỉ khách hàng (customer) mới được thêm sản phẩm vào giỏ hàng
require_role(['customer']);

// ========================================
// 1. LẤY VÀ KIỂM TRA DỮ LIỆU ĐẦU VÀO
// ========================================

// Lấy ID sản phẩm từ URL, ép kiểu int, mặc định = 0 nếu không có
$product_id = (int)($_GET['product_id'] ?? 0);

// Lấy số lượng muốn thêm, đảm bảo tối thiểu là 1
$quantity = max(1, (int)($_GET['quantity'] ?? 1));

// Nếu không có product_id hợp lệ → quay về trang cửa hàng
if ($product_id <= 0) {
    header("Location: " . BASE_URL . "cua-hang");
    exit();
}

// ========================================
// 2. KIỂM TRA SẢN PHẨM CÓ TỒN TẠI & CÒN HÀNG KHÔNG
// ========================================

$stmt = mysqli_prepare($conn, "SELECT stock, price FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

// Sản phẩm không tồn tại hoặc đã hết hàng
if (!$product || $product['stock'] < 1) {
    setFlash('danger', 'Sản phẩm không tồn tại hoặc đã hết hàng!');
    header("Location: " . BASE_URL . "cua-hang");
    exit();
}

// Số lượng yêu cầu vượt quá tồn kho hiện có
if ($quantity > $product['stock']) {
    header("Location: " . BASE_URL . "shop.php?error=not_enough_stock&max=" . $product['stock']);
    exit();
}

// ========================================
// 3. XỬ LÝ GIỎ HÀNG THEO TRẠNG THÁI ĐĂNG NHẬP
// ========================================

// ————————————————————————
// Trường hợp 1: Người dùng đã đăng nhập
// ————————————————————————
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Lấy customer_id từ user_id
    $cust_result = mysqli_query($conn, "SELECT id FROM customers WHERE user_id = '$user_id'");
    $cust = mysqli_fetch_assoc($cust_result);

    if (!$cust) {
        // Trường hợp hiếm: user tồn tại nhưng không có customer → lỗi hệ thống (có thể log)
        header("Location: " . BASE_URL . "shop.php?error=system");
        exit();
    }

    $customer_id = $cust['id'];

    // Kiểm tra giỏ hàng của khách hàng
    $cart_result = mysqli_query($conn, "SELECT id FROM carts WHERE customer_id = '$customer_id'");
    $cart = mysqli_fetch_assoc($cart_result);

    // Nếu chưa có giỏ hàng → tạo mới
    if (!$cart) {
        mysqli_query($conn, "INSERT INTO carts (customer_id, created_at) VALUES ('$customer_id', NOW())");
        $cart_id = mysqli_insert_id($conn);
    } else {
        $cart_id = $cart['id'];
    }

    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $check_result = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE cart_id = '$cart_id' AND product_id = '$product_id'");

    if (mysqli_num_rows($check_result) > 0) {
        // Đã có trong giỏ → cộng dồn số lượng
        $row = mysqli_fetch_assoc($check_result);
        $new_quantity = $row['quantity'] + $quantity;

        // Kiểm tra lại tổng số lượng sau khi cộng có vượt tồn kho không
        if ($new_quantity > $product['stock']) {
            header("Location: ../shop.php?error=not_enough_stock_cart&max=" . $product['stock']);
            exit();
        }

        // Cập nhật số lượng mới
        mysqli_query($conn, "UPDATE cart_items SET quantity = '$new_quantity' WHERE cart_id = '$cart_id' AND product_id = '$product_id'");
    } else {
        // Chưa có → thêm mới vào giỏ
        mysqli_query($conn, "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES ('$cart_id', '$product_id', '$quantity')");
    }
}

// ————————————————————————
// Trường hợp 2: Khách vãng lai (chưa đăng nhập)
// ————————————————————————
else {
    // Khởi tạo session giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Lấy số lượng hiện tại trong session (nếu có)
    $current_quantity = $_SESSION['cart'][$product_id]['quantity'] ?? 0;
    $new_quantity = $current_quantity + $quantity;

    // Kiểm tra vượt tồn kho
    if ($new_quantity > $product['stock']) {
        header("Location: ../shop.php?error=not_enough_stock&max=" . $product['stock']);
        exit();
    }

    // Cập nhật hoặc thêm mới vào session cart
    $_SESSION['cart'][$product_id] = [
        'quantity' => $new_quantity
    ];
}

// ========================================
// 4. HOÀN TẤT – CHUYỂN HƯỚNG VỀ GIỎ HÀNG
// ========================================

header("Location: " . BASE_URL . "gio-hang?added=1");
exit();

