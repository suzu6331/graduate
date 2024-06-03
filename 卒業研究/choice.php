<?php
session_start();

$user = $_SESSION['user'] ?? null;

if (!$user)
{
    header('Location: login.php');
    exit;
}

echo "ログイン中のユーザー: {$user['username']}";
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クイズ選択</title>

    <link rel="stylesheet" href="css/s.css">        
</head>
<body>
    <style>
        .navigation-btn
        {
            display: flex;
            margin: 10px 10;
            flex-direction: row;
            text-align: center;
            justify-content: center;
            align-items: left;
        }
    </style>
    <header>
        <h1 onclick="navigateTo('index.html')">問題選択</h1>
    </header>
    <div class="navigation-container">
        <a href="search.php" class="navigation-btn">基本情報</a>
        <a href="mypage.php" class="navigation-btn">応用情報</a>
        <a href="ranking.php" class="navigation-btn">SPI</a>
    </div>

    <footer>
    </footer>

</body>
</html>
