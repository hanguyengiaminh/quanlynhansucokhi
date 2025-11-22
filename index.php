<?php
session_start();
require_once "inc/db.php";

$error = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->execute([$username,$password]);
    $user = $stmt->fetch();

    if($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng nhập - QLNS Cơ Khí</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #71b7e6, #9b59b6);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}
.login-card {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}
.login-card h3 {
    font-weight: 700;
    color: #343a40;
    margin-bottom: 20px;
    text-align: center;
}
.login-card .form-control {
    border-radius: 10px;
    padding: 12px 15px;
}
.login-card button {
    border-radius: 10px;
    padding: 10px;
    font-weight: 600;
}
.login-card .alert {
    border-radius: 10px;
    font-size: 0.9rem;
}
</style>
</head>
<body>

<div class="login-card">
    <h3>Quản Lý Nhân Sự Cơ Khí</h3>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" placeholder="Nhập username" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
    </form>
</div>

</body>
</html>
