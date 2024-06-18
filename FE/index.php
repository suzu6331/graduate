<?php
session_start();
require_once __DIR__ . '/functions.php';

// セッションから年度と試験区分を取得
$year = isset($_SESSION['year']) ? $_SESSION['year'] : null;
$siken = isset($_SESSION['siken']) ? $_SESSION['siken'] : null;

if (!$year || !$siken) {
    echo "年と試験区分を指定してください。";
    exit();
}

// セッションから現在の問題番号を取得
if (!isset($_SESSION['curt_question'])) {
    $_SESSION['curt_question'] = 0;
}

$curt_idx = $_SESSION['curt_question'];

// JSONデータを取得
$repo = 'suzu6331/graduate';
$token = 'your_github_token';
$JSON_receive = 'local';  // 'github' または 'local'

if ($JSON_receive == 'github') {
    $questions = get_json_from_github($repo, $year, $token);
} else {
    $questions = get_json_from_local($year);
    if (!$questions) {
        echo "<p>問題が見つかりません。データが存在することを確認してください。</p>";
        exit();
    }
}

$curt_question_id = $questions[0]['quizzes'][$curt_idx]['id'];

// 残り時間を計算
$time_limit = 3600; // 制限時間を秒単位で設定
$cur_time = time();
if (!isset($_SESSION['total_start_time'])) {
    $_SESSION['total_start_time'] = $cur_time;
}
$total_elapsed_time = $cur_time - $_SESSION['total_start_time'];
$total_remaining_time = $time_limit - $total_elapsed_time;

// 残り時間がなくなった場合、終了ページにリダイレクト
if ($total_remaining_time <= 0) {
    header("Location: end.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_ans'])) {
        $select_idx = $_POST['select'];
        $current_question = get_question_by_id($questions, $curt_question_id);

        if ($current_question) {
            $ans_idx = $current_question['answer'];
            
            // 最初の問題に戻る
            echo '<form method="post"><button type="submit" name="reset">最初の問題に戻る</button></form>';
            echo '<h2>結果</h2>';
            echo '<div>';
            echo '<p><strong>問題:</strong> ' . $current_question['mondai'] . '</p>';
            if (strpos($current_question['mondai'], 'img data-image-index') !== false) {
                echo display_image($year, $siken, $current_question['id'], 0);
            }
            echo '<p><strong>あなたの選択:</strong> ' . display_option($current_question['sentaku'][$select_idx], $year, $siken, $current_question['id'], $select_idx) . '</p>';
            if ($select_idx == $ans_idx) {
                echo '<p><strong>結果:</strong> 正解</p>';
            } else {
                echo '<p><strong>結果:</strong> 不正解</p>';
            }
            echo '<p><strong>正解:</strong> ' . display_option($current_question['sentaku'][$ans_idx], $year, $siken, $current_question['id'], $ans_idx) . '</p>';
            echo '</div>';
            echo '<hr>';
        } else {
            echo '<p>問題が見つかりません。</p>';
        }

        // 次の問題に進むボタン
        echo '<form method="post"><button type="submit" name="next">次の問題へ</button></form>';
    } elseif (isset($_POST['next'])) {
        // インクリメントして次の問題に進む
        $_SESSION['curt_question']++;
        if ($_SESSION['curt_question'] >= count($questions[0]['quizzes'])) {
            $_SESSION['curt_question'] = 0; // すべての問題が終わったら最初に戻る
        }
        $next_question = $questions[0]['quizzes'][$_SESSION['curt_question']];
        $_SESSION['curt_question_id'] = $next_question['id'];
        // 次の問題を表示する
        question_print($next_question, $year, $siken, $_SESSION['curt_question'], $total_remaining_time);
    } elseif (isset($_POST['reset'])) {
        // 最初の問題に戻る
        $_SESSION['curt_question'] = 0;
        $_SESSION['curt_question_id'] = $questions[0]['quizzes'][0]['id'];
        $question = $questions[0]['quizzes'][0];
        question_print($question, $year, $siken, $_SESSION['curt_question'], $total_remaining_time);
    } else {
        // 現在の問題を表示
        $current_question = get_question_by_id($questions, $curt_question_id);
        if ($current_question) {
            question_print($current_question, $year, $siken, $curt_idx, $total_remaining_time);
        } else {
            echo '<p>問題が見つかりません。</p>';
        }
    }
} else {
    // 現在の問題を表示
    $current_question = get_question_by_id($questions, $curt_question_id);
    if ($current_question) {
        question_print($current_question, $year, $siken, $curt_idx, $total_remaining_time);
    } else {
        echo '<p>問題が見つかりません。</p>';
    }
}
?>
