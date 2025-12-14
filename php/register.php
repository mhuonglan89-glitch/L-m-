<?php
require_once 'include/config.php';
require_role(['guest']);

/* ========================================
   KHỞI TẠO BIẾN THÔNG BÁO
   ======================================== */
$message = '';
$error   = '';

/* ========================================
   XỬ LÝ FORM ĐĂNG KÝ KHI NHẬN POST
   ======================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Lấy và làm sạch dữ liệu từ form
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';

  // Kiểm tra các trường bắt buộc
  if (empty($username) || empty($email) || empty($phone) || empty($password)) {
    $error = "Vui lòng điền đầy đủ tất cả các trường!";
  }
  // Kiểm tra định dạng email
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email không hợp lệ!";
  }
  // Kiểm tra độ dài mật khẩu
  elseif (strlen($password) < 6) {
    $error = "Mật khẩu phải có ít nhất 6 ký tự!";
  } else {
    // Kiểm tra trùng username hoặc email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $error = "Tên đăng nhập hoặc email đã được sử dụng!";
      $stmt->close();
    } else {
      $stmt->close();

      // Mã hoá mật khẩu
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      // Thêm người dùng mới (role mặc định là customer)
      $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
      $stmt->bind_param("sss", $username, $email, $hashed_password);

      if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $stmt->close();

        // Thêm thông tin số điện thoại vào bảng customers
        $stmt2 = $conn->prepare("INSERT INTO customers (user_id, phone) VALUES (?, ?)");
        $stmt2->bind_param("is", $user_id, $phone);
        $stmt2->execute();
        $stmt2->close();

        $message = "Đăng ký thành công! Đang chuyển đến trang đăng nhập...";

        // Tự động chuyển hướng sau 2 giây
        echo '<script>
                        setTimeout(() => window.location.href = "login.php", 2000);
                      </script>';
      } else {
        $error = "Đăng ký thất bại. Vui lòng thử lại!";
        $stmt->close();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng ký - FRUITABLES</title>

  <!-- CSS chính và icon -->
  <link rel="stylesheet" href="css/login.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.0/css/boxicons.min.css" />

  <!-- Style thông báo -->
  <style>
    .alert {
      padding: 12px 15px;
      margin: 15px 0;
      border-radius: 6px;
      font-size: 14px;
      text-align: center;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>

<body>
  <!-- ========================================
         GIAO DIỆN CHÍNH - CONTAINER ĐĂNG KÝ
         ======================================== -->
  <div class="container">
    <div class="card">
      <!-- Logo và slogan -->
      <div class="logo">FRUITABLES</div>
      <p class="tagline">Ăn tươi, sống khỏe</p>

      <!-- Thông báo thành công -->
      <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
      <?php endif; ?>

      <!-- Thông báo lỗi -->
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <!-- Form đăng ký -->
      <form method="POST">
        <div class="form-group">
          <label>Tên đăng nhập</label>
          <input type="text" name="username" placeholder="Nhập tên đăng nhập"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="Nhập địa chỉ email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label>Số điện thoại</label>
          <input type="text" name="phone" placeholder="Ví dụ: 0123456789"
            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label>Mật khẩu</label>
          <div class="input-group">
            <input type="password" name="password" placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" required />
            <i class="bx bx-hide toggle-password" onclick="togglePass(this)"></i>
          </div>
        </div>

        <button type="submit" class="btn">Đăng ký</button>
      </form>

      <!-- Liên kết sang trang đăng nhập -->
      <div class="links">
        Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
      </div>
    </div>
  </div>

  <!-- ========================================
         SCRIPT HIỂN/ẨN MẬT KHẨU
         ======================================== -->
  <script>
    function togglePass(icon) {
      const input = icon.previousElementSibling;
      if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bx-hide", "bx-show");
      } else {
        input.type = "password";
        icon.classList.replace("bx-show", "bx-hide");
      }
    }
  </script>
</body>

</html>

<?php
// Đóng kết nối CSDL
mysqli_close($conn);
?>