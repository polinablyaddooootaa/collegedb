<?php
session_start();
include('config.php');

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        header('Location: register.php?error=user_not_found');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="icon" href="logo2.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #4946e5 0%, #636ff1 100%);
            color: #fff;
        }

        .login-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        .brand-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            background:rgb(189, 216, 255);
            color: #1f2937;
        }

        .logo {
            font-size: 3rem;
            font-weight: bold;
            color: #fff;
        }

        .form-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        input {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        button {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #4946e5 0%, #636ff1 100%);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

     

        .footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: #1f2937;
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(255, 255, 255, 0.9);
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .brand-section {
                padding: 1rem;
                flex: 0 0 100px;
            }

            .form-section {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-section">
        <img src="logo1.png" alt="Logo 1">
        </div>
        <div class="form-section">
            <div class="form-container">
                <?php if (isset($_GET['error']) && $_GET['error'] == 'user_not_found'): ?>
                    <div class="error-message">
                        Пользователь не найден. Пожалуйста, зарегистрируйтесь.
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Имя пользователя" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <button type="submit" name="login">Войти</button>
                    <div class="footer-links">
                        <a href="register.php">Регистрация</a>
                        <a href="#">Забыли пароль?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>