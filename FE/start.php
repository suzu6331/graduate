<?php
session_start();

// GET パラメータから年度と試験区分を取得
$year = isset($_GET['year']) ? $_GET['year'] : null;
$siken = isset($_GET['exam']) ? $_GET['exam'] : null;

if (!$year || !$siken) {
    echo "年と試験区分を指定してください。";
    exit();
}

// セッションを初期化
$_SESSION = [];
session_unset();
session_destroy();
session_start();

// POST リクエストが送信された場合、セッションに年度と試験区分を保存して `index.php` にリダイレクト
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['year'] = $year;
    $_SESSION['siken'] = $siken;
    $_SESSION['total_start_time'] = time();
    $_SESSION['curt_question'] = 0;
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>試験開始</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>試験を開始しますか？</h1>
        <form method="post">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="exam" value="<?php echo htmlspecialchars($siken); ?>">
            <button type="submit">開始</button>
        </form>
    </div>
</body>
</html>
