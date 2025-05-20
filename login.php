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
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Secure Cloud</title>
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
        
        .login-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            height: 500px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .login-form {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
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
        
        .login-title {
            font-size: 24px;
            font-weight: 600;
            color:rgb(66, 52, 97);
            margin-bottom: 40px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 25px;
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
        
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-container input {
            margin-right: 10px;
        }
        
        .checkbox-container label {
            font-size: 14px;
            color: #666;
        }
        
        .btn-login {
            background-color: #5e4a8a;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
         
            margin: 0 auto;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #4e3a7a;
        }
        
        .register-link {
            margin-top: auto;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: #5e4a8a;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        .logo img {
            width: 400px;
            margin: 20px auto;
            display: block;
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
            .login-container {
                flex-direction: column-reverse;
                height: auto;
            }
            
            .login-form, .info-section {
                padding: 30px;
            }
            
            .info-section {
                padding-bottom: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1 class="login-title">ВХОД</h1>
            
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
                
               
                
                <button type="submit" name="login" class="btn-login">Вход</button>
            </form>
            
            <div class="register-link">
                Нет аккаунта? <a href="register.php">Регистрация</a>
            </div>
        </div>
        
        <div class="info-section">
        <div class="logo">
        <img src="logo1.png" alt="Logo 1">
    </div>
         
    
          
        </div>
    </div>
</body>
</html>