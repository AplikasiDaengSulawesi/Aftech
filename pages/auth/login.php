<?php
session_start();
require_once '../../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Log Login Activity ke tabel activity_logs
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('LOGIN', ?)");
            $logStmt->execute(["User " . $user['full_name'] . " (" . $user['role'] . ") berhasil login"]);

            header("Location: ../index.php");
            exit;
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Harap isi semua field!";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AFTECH - Login</title>
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            background-image: radial-gradient(#1A237E 0.5px, #f8f9fa 0.5px);
            background-size: 20px 20px;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 5px solid #1A237E;
        }
        .login-box h1 {
            color: #1A237E;
            font-weight: 900;
            font-size: 36px;
            letter-spacing: 4px;
            margin-bottom: 5px;
        }
        .login-box p.brand-sub {
            color: #FFC107;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }
        .form-group { text-align: left; margin-bottom: 20px; }
        .form-group label { color: #555; font-size: 12px; font-weight: 600; margin-bottom: 8px; display: block; }
        .form-control {
            background: #fdfdfd !important;
            border: 1px solid #ddd !important;
            color: #333 !important;
            border-radius: 8px;
            padding: 12px 15px;
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: #1A237E !important;
            box-shadow: 0 0 8px rgba(26, 35, 126, 0.1) !important;
            background: #fff !important;
        }
        .btn-login {
            background: #1A237E;
            border: none;
            color: #fff;
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
            transition: 0.3s;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            background: #3F51B5;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }
        .error-msg {
            background: #fff5f2;
            color: #d93025;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #fadbd5;
        }
        .footer-text {
            color: #bbb;
            font-size: 11px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>AFTECH</h1>
        <p class="brand-sub">Manufacturing System</p>
        
        <?php if($error): ?>
            <div class="error-msg">
                <i class="fa fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>USERNAME</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>PASSWORD</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-login">Masuk Ke Sistem</button>
        </form>
        
        <p class="footer-text">&copy; 2026 AFTECH GROUP INDONESIA</p>
    </div>
</body>
</html>
