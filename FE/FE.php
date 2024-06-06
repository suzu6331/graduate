<?php
session_start();

$user = $_SESSION['user'] ?? null;

if (!$user) {
    header('Location: login.php');
    exit;
}

echo "ログイン中のユーザー: {$user['username']}";

$jsonFilePath = 'data/2022/102022001.json';
$jsonData = file_get_contents($jsonFilePath);
$jsonArray = json_decode($jsonData, true);
$quiz = $jsonArray['quizzes'][0];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>基本情報</title>
    <link rel="stylesheet" href="css/s.css">        
</head>
<body>
    <style>
        .navigation-btn {
            display: flex;
            margin: 10px 10;
            flex-direction: row;
            text-align: center;
            justify-content: center;
            align-items: left;
        }
    </style>
    <header>
        <h1>クイズ表示</h1>
    </header>

    <div>
        <h2>問題:</h2>
        <p><?php echo $quiz['mondai']; ?></p>
    </div>

    <footer>
    </footer>
</body>
</html>
