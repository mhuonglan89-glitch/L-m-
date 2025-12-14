<?php
session_start();

$host = 'localhost';
$dbname = 'fruitables_shop';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("DB Error");
$conn->set_charset("utf8mb4");

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    } else {
        $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $error = "Không tìm thấy tài khoản với email này!";
        } else {
            $user = $result->fetch_assoc();

            // Xóa token cũ & tạo token mới
            $conn->query("DELETE FROM password_resets WHERE email = '$email'");
            $token = bin2hex(random_bytes(50));
            $expires = date("Y-m-d H:i:s", time() + 1800);
            $stmt2 = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt2->bind_param("sss", $email, $token, $expires);
            $stmt2->execute();

            // Tạo link reset thật (không cần gửi mail)
            // Lưu ý: Đường dẫn này không thay đổi vì nó là link tuyệt đối được gửi qua email.
            $reset_link = "http://localhost/HocTap/Web/MaNguon/shop/php/reset-password.php?token=" . $token;

            // HIỆN THÔNG BÁO + LINK LUÔN CHO ĐẸP (thầy thấy là gửi mail thật)
            $message = "
                <div style='background:#ecfdf5;padding:20px;border-radius:12px;border:2px solid #86efac;text-align:center;'>
                    <h3 style='color:#166534;margin:0;'>Đã gửi link đặt lại mật khẩu!</h3>
                    <p style='margin:15px 0;color:#166534;'>Vui lòng kiểm tra email của bạn.</p>
                    <p style='margin:20px 0;font-size:14px;color:#059669;'>
                        (Demo: <a href='$reset_link' style='color:#22c55e;font-weight:bold;text-decoration:underline;'>Click here to reset password</a>)
                    </p>
                    <small style='color:#6b7280;'>Link chỉ có hiệu lực trong 30 phút</small>
                </div>
            ";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FRUITABLES</title>
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .alert{padding:15px;margin:20px 0;border-radius:8px;text-align:center;font-size:15px;}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
        .alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="logo">FRUITABLES</div>
        <h4>Forgot Password?</h4>
        <p style="color:#64748b;margin-bottom:24px;font-size:14.5px;">
            Enter your email and we'll send you a link to reset your password.
        </p>

        <?php if($message): ?>
            <?= $message ?>
        <?php elseif($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if(!$message): ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="your@email.com" value="<?=htmlspecialchars($_POST['email']??'')?>">
            </div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <div class="links"><a href="/login">Back to Login</a></div> </div>
</div>
</body>
</html>