<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$time_limit = 3600; // 全体の制限時間を秒単位で設定
$cur_time = time();

// セッションに全体の開始時間が設定されていない場合、現在の時間を設定
if (!isset($_SESSION['total_start_time'])) {
    $_SESSION['total_start_time'] = $cur_time;
}

// 残り時間の計算
$total_elapsed_time = $cur_time - $_SESSION['total_start_time'];
$total_remaining_time = $time_limit - $total_elapsed_time;

// 全体の残り時間が0以下になった場合、終了ページにリダイレクト
if ($total_remaining_time <= 0) {
    header("Location: end.php");
    exit();
}

// 残り時間を返す
return $total_remaining_time;
?>
