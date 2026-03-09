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
    <title>PT AFTECH MAKASSAR INDONESIA - Login</title>
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #1A237E;
            --primary-light: #3F51B5;
            --accent: #FFC107;
            --bg-color: #f4f7fe;
        }
        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 40% 20%, rgba(26, 35, 126, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(63, 81, 181, 0.08) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(255, 193, 7, 0.05) 0px, transparent 50%);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-box {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(26, 35, 126, 0.1);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.8);
        }
        .login-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--accent));
        }
        .logo-container {
            margin-bottom: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .logo-container img {
            width: 45px;
            border-radius: 8px;
            margin-right: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .login-box h1 {
            color: var(--primary);
            font-weight: 900;
            font-size: 26px;
            letter-spacing: 2px;
            margin: 0;
            line-height: 1;
        }
        .login-box p.brand-sub {
            color: #888;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 8px;
            margin-bottom: 35px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            color: #555;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0a5b1;
            font-size: 14px;
            transition: 0.3s;
            pointer-events: none;
        }
        .form-control {
            width: 100%;
            background: #f8f9fc !important;
            border: 1px solid #e2e8f0 !important;
            color: #333 !important;
            border-radius: 12px;
            padding: 14px 15px 14px 45px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
            outline: none;
        }
        .form-control::placeholder {
            color: #adb5bd;
        }
        .form-control:focus {
            border-color: var(--primary-light) !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(63, 81, 181, 0.1) !important;
        }
        .form-control:focus + i {
            color: var(--primary);
        }
        .btn-login {
            background: var(--primary);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            color: #fff;
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3);
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
        }
        .error-msg {
            background: #fff0f0;
            color: #d50000;
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 25px;
            border: 1px solid #ffdbdb;
            display: flex;
            align-items: center;
            text-align: left;
            font-weight: 500;
        }
        .error-msg i {
            font-size: 16px;
            margin-right: 10px;
        }
        .footer-text {
            color: #a0a5b1;
            font-size: 11px;
            margin-top: 30px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="logo-container">
                <img src="../../assets/images/logo.png" alt="Logo">
                <h1>PT AFTECH</h1>
            </div>
            <p class="brand-sub">MAKASSAR INDONESIA</p>
            
            <?php if($error): ?>
                <div class="error-msg">
                    <i class="fa fa-exclamation-circle"></i> 
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>USERNAME</label>
                    <div class="input-icon-wrap">
                        <i class="fa fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label>PASSWORD</label>
                    <div class="input-icon-wrap">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    Masuk Ke Sistem <i class="fa fa-arrow-right"></i>
                </button>
            </form>
            
            <p class="footer-text">&copy; 2026 PT AFTECH MAKASSAR INDONESIA</p>
        </div>
    </div>
</body>
</html>