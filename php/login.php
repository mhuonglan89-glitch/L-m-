<?php
// Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'include/config.php';
require_role(['guest']);

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit;
}

// Initialize variables
$errors = [];
$success = '';
$login = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($login)) {
        $errors[] = "Vui lòng nhập email hoặc tên đăng nhập.";
    }
    if (empty($password)) {
        $errors[] = "Vui lòng nhập mật khẩu.";
    }

    // If no validation errors, check credentials
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, email, password, role 
                              FROM users 
                              WHERE username = ? OR email = ? 
                              LIMIT 1");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                // Set success message
                $_SESSION['success'] = "Đăng nhập thành công!";

                // Redirect to home page
                header("Location: " . BASE_URL);
                exit;
            } else {
                $errors[] = "Mật khẩu không chính xác.";
            }
        } else {
            $errors[] = "Tài khoản không tồn tại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập - Fruitables</title>

  <!-- CSS chính -->
  <link rel="stylesheet" href="../css/login.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.0/css/boxicons.min.css" />

  <!-- Style thông báo lỗi/thành công -->
  <style>
    .alert {
      padding: 12px 16px;
      margin: 15px 0;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
    }

    .alert-error {
      background: #fff5f5;
      color: #c53030;
      border: 1px solid #fed7d7;
    }

    .alert-success {
      background: #f0fff4;
      color: #2b8a3e;
      border: 1px solid #9ae6b4;
    }
  </style>
</head>

<body>

  <!-- ============================================= -->
  <!-- CONTAINER CHÍNH TRANG ĐĂNG NHẬP              -->
  <!-- ============================================= -->
  <div class="container">
    <div class="card">
      <!-- Logo và slogan -->
      <div class="logo">FRUITABLES</div>
      <p class="tagline">Ăn tươi, sống khỏe</p>

      <!-- Thông báo lỗi -->
      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Thông báo thành công -->
      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <!-- Form đăng nhập -->
      <form method="POST" action="">
        <!-- Ô nhập email/tên đăng nhập -->
        <div class="form-group">
          <label>Email hoặc Tên đăng nhập</label>
          <input type="text"
            name="login"
            placeholder="Nhập email hoặc tên đăng nhập"
            required
            value="<?= htmlspecialchars($login) ?>"
            autocomplete="username" />
        </div>

        <!-- Ô nhập mật khẩu + nút hiện/ẩn + link quên mật khẩu -->
        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <label>Mật khẩu</label>
            <a href="forgot.php" class="forgot-link">Quên mật khẩu?</a>
          </div>
          <div class="input-group" style="position: relative;">
            <input type="password"
              name="password"
              placeholder="Nhập mật khẩu"
              required
              autocomplete="current-password" />
            <i class="bx bx-hide toggle-password"
              onclick="togglePass(this)"
              style="position: absolute; right: 12px; top: 14px; cursor: pointer;"></i>
          </div>
        </div>

        <!-- Nút submit -->
        <button type="submit" class="btn">Đăng nhập</button>
      </form>

      <!-- Liên kết đến trang đăng ký -->
      <div class="links">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
      </div>
    </div>
  </div>

  <!-- ============================================= -->
  <!-- SCRIPT HIỆN/ẨN MẬT KHẨU                        -->
  <!-- ============================================= -->
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