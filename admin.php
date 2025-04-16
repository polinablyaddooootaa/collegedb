<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Панель</title>

    <!-- Подключение стилей и шрифтов -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="index.css">
    <link rel="icon" href="logo2.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
        }

        .content {
            padding: 2rem;
        }

        h1 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2rem;
            font-size: 2.2rem;
        }

        .card {
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .card-img-top {
            height: 150px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .card:hover .card-img-top {
            transform: scale(1.05);
        }

        .card-body {
            padding: 1.5rem;
            text-align: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .btn-select {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-select:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .row {
            gap: 1.5rem;
            justify-content: center;
        }

        .col-md-4 {
            flex: 0 0 auto;
            width: calc(33.333% - 1rem);
            max-width: 400px;
        }

        @media (max-width: 992px) {
            .content {
                padding: 1rem;
            }

            .col-md-4 {
                width: calc(50% - 1rem);
            }
        }

        @media (max-width: 768px) {
            .col-md-4 {
                width: 100%;
            }

            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="content">
    <h1 class="text-center">Админ Панель</h1>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <img src="images/orders.jpg" class="card-img-top" alt="Приказ о взыскании">
                <div class="card-body">
                    <h5 class="card-title">Приказ о взыскании</h5>
                    <a href="orders.php" class="btn btn-select">Выбрать</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <img src="images/neusp.jpg" class="card-img-top" alt="Приказ о неуспевающих">
                <div class="card-body">
                    <h5 class="card-title">Приказ о неуспевающих</h5>
                    <a href="neusp.php" class="btn btn-select">Выбрать</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <img src="images/certificates.jpg" class="card-img-top" alt="Генерация сертификатов">
                <div class="card-body">
                    <h5 class="card-title">Генерация сертификатов</h5>
                    <a href="otchet.php" class="btn btn-select">Выбрать</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>