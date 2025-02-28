<?php
session_start();
include('config.php');

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $registration_code = 'SECRET123'; // Your registration code

    if ($code === $registration_code) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $insertQuery = "INSERT INTO users (username, password) VALUES (?, ?)";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([$username, $password]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = "Ошибка при регистрации: " . $e->getMessage();
        }
    } else {
        $error = "Неверный код регистрации!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            height: 100vh;
            display: flex;
            background: #1f2937;
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
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
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
            background: #1f2937;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #374151;
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
            <div class="logo">Your Logo</div>
        </div>
        <div class="form-section">
            <div class="form-container">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Имя пользователя" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="code" placeholder="Код регистрации" required>
                    </div>
                    <button type="submit">Зарегистрироваться</button>
                    <div class="footer-links">
                        <a href="login.php">Уже есть аккаунт?</a>
                        <a href="#">Нужна помощь?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>