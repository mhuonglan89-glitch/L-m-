<?php
// ==================================================================
// 1. KHỞI TẠO & KIỂM TRA PHÂN QUYỀN
// ==================================================================
require_once '../include/config.php';
require_role(['customer']); // Chỉ khách hàng đã đăng nhập mới được đánh giá

// Nếu chưa đăng nhập → chuyển hướng về trang login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dang-nhap');
    exit;
}

// ==================================================================
// 2. LẤY THÔNG TIN CUSTOMER TỪ USER_ID
// ==================================================================
$customer_stmt = mysqli_prepare($conn, "SELECT id FROM customers WHERE user_id = ?");
mysqli_stmt_bind_param($customer_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($customer_stmt);
$cust_result = mysqli_stmt_get_result($customer_stmt);

if (mysqli_num_rows($cust_result) === 0) {
    die("Không tìm thấy thông tin khách hàng.");
}

$customer     = mysqli_fetch_assoc($cust_result);
$customer_id  = $customer['id'];

// ==================================================================
// 3. NHẬN DỮ LIỆU TỪ FORM
// ==================================================================
$product_id = (int)$_POST['product_id'];           // ID sản phẩm được đánh giá
$rating     = (int)$_POST['rating'];               // Điểm đánh giá (1-5)
$message    = trim($_POST['message'] ?? '');       // Nội dung đánh giá (có thể rỗng)
$image      = '';                                  // Tên file ảnh (nếu có)

// ==================================================================
// 4. XỬ LÝ UPLOAD ẢNH ĐÁNH GIÁ
// ==================================================================
$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/shop/img/reviews/';                // Thư mục lưu ảnh đánh giá
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);                // Tạo thư mục nếu chưa tồn tại
}

if (!empty($_FILES['image']['name'])) {
    $orig_name = basename($_FILES["image"]["name"]);
    $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    // Kiểm tra file có phải là ảnh thật không
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        $target_file = $target_dir . $orig_name;

        // Ngăn upload nếu file đã tồn tại (tránh ghi đè)
        if (file_exists($target_file)) {
            die("File ảnh đã tồn tại trên server. Vui lòng đổi tên file và thử lại.");
        }

        // Di chuyển file từ tmp sang thư mục đích
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $orig_name; // Lưu tên file gốc vào CSDL
        }
    }
    // Nếu không phải ảnh hoặc upload lỗi → $image vẫn rỗng
}

// ==================================================================
// 5. LƯU ĐÁNH GIÁ VÀO CƠ SỞ DỮ LIỆU
// ==================================================================
$sql = "INSERT INTO review 
        (customer_id, product_id, rating, message, image, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iiiss", $customer_id, $product_id, $rating, $message, $image);
mysqli_stmt_execute($stmt);

// ==================================================================
// 6. ĐÓNG KẾT NỐI & CHUYỂN HƯỚNG VỀ TRANG CHI TIẾT SẢN PHẨM
// ==================================================================
mysqli_close($conn);
header("Location: " . SITE_URL . "san-pham/$product_id");
exit;
