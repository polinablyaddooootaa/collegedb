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
            $error = "Error during registration: " . $e->getMessage();
        }
    } else {
        $error = "Invalid registration code!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Secure Cloud</title>
    <link rel="icon" href="logo2.png" type="image/png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            height: 100vh;
            background-color: #5e4a8a;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            height: 550px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .register-form {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
        }
        .logo img {
            width: 400px;
            margin: 20px auto;
            display: block;
        }
        
        .info-section {
            flex: 1;
            background: linear-gradient(rgba(94, 74, 138, 0.9), rgba(94, 74, 138, 0.9)), 
                        url('office_background.jpg') center/cover no-repeat;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            text-align: center;
        }
        
        .register-title {
            font-size: 24px;
            font-weight: 600;
            color: #5e4a8a;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 0;
            font-size: 16px;
            border: none;
            border-bottom: 1px solid #ddd;
            background: transparent;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-bottom: 2px solid #5e4a8a;
        }
        
        .btn-register {
            background-color: #5e4a8a;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
          
            transition: background-color 0.3s;
        }
        
        .btn-register:hover {
            background-color: #4e3a7a;
        }
        
        .login-link {
            margin-top: auto;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .login-link a {
            color: #5e4a8a;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .cloud-icon {
            font-size: 50px;
            margin-bottom: 20px;
        }
        
        .info-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .info-subtitle {
            font-size: 16px;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .info-cta {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column-reverse;
                height: auto;
            }
            
            .register-form, .info-section {
                padding: 30px;
            }
            
            .info-section {
                padding-bottom: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <h1 class="register-title">РЕГИСТРАЦИЯ</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">ФИО</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Повторите пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="code">Код регистрации</label>
                    <input type="text" id="code" name="code" required>
                </div>
                
                <button type="submit" class="btn-register">Зарегистрироваться</button>
            </form>
            
            <div class="login-link">
                Есть аккаунт? <a href="login.php">Войти</a>
            </div>
        </div>
        
        <div class="info-section">
        <div class="logo">
        <img src="logo1.png" alt="Logo 1">
    </div> 
    </div>
    
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>