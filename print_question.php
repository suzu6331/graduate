<?php
session_start();

// GitHubからコンテンツを取得する関数
function get_content($repo, $path, $token) {
    $url = "https://api.github.com/repos/$repo/contents/$path"; // $repo = ユーザー名/graduate
    $opt = [
        'http' => [
            'header' => [
                "User-Agent: request",
                "Authorization: token $token"
            ]
        ]
    ];
    $stream_context = stream_context_create($opt);
    $res = file_get_contents($url, false, $stream_context); // リクエスト送信
    if ($res === FALSE)
    {
        die('GitHub APIから受け取れてないです');
    }
    return json_decode($res, true); // 連想配列でreturnする
}

// GitHubからJSONデータを取得
function Github_JSON($repo, $year, $token) {
    $questions = [];
    $path = "data/$year"; //任意の年のパスを取得
    $files = get_content($repo, $path, $token);

    if (!is_array($files)) 
    {
        die('配列じゃないです');
    }

    foreach ($files as $FILE)
    {
        if (strpos($FILE['name'], '.json') !== false)
        {
            $json_content = file_get_contents($FILE['download_url']); //ファイル内容を取得
            if ($json_content === FALSE)
            {
                die('ファイルの内容を取得できませんでした');
            }
            $questions[] = json_decode($json_content, true);
        }
    }
    return $questions;
}

// ローカルフォルダからJSONデータを取得する関数
function Local_JSON($year) {
    $questions = [];
    $path = "C:\\xampp\\htdocs\\php\\卒業研究\\data\\$year";
    if (!is_dir($path))
    {
        die("パスが間違っているか、パスが存在しません");
    }
    $files = scandir($path);
    foreach ($files as $FILE)
    {
        if (strpos($FILE, '.json') !== false)
        {
            $json_content = file_get_contents("$path\\$FILE");
            if ($json_content === FALSE)
            {
                die('jsonファイルを取得できませんでした');
            }
            $questions[] = json_decode($json_content, true);
        }
    }
    return $questions;
}

// パラメータから年度と試験区分を取得
$year = $_GET['year'];
$siken = $_GET['exam'];
$repo = 'suzu6331/graduate';
$token = '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';
$JSON_receive = 'local';  // 'github'または'local'

if ($JSON_receive == 'github')
{
    $questions = Github_JSON($repo, $year, $token);
}
else
{
    $questions = Local_JSON($year);
}

// 全ての問題をIDベースで取得する関数
function get_question_by_id($questions, $id) {
    foreach ($questions as $question_set)
    {
        foreach ($question_set['quizzes'] as $quiz)
        {
            if ($quiz['id'] == $id)
            {
                return $quiz;
            }
        }
    }
    return null;
}

// セッションから現在の問題番号を取得
if (!isset($_SESSION['curt_question']))
{
    $_SESSION['curt_question'] = 0;
}
if (!isset($_SESSION['curt_question_id']))
{
    $_SESSION['curt_question_id'] = $questions[0]['quizzes'][0]['id'];
}

$curt_idx = $_SESSION['curt_question'];
$curt_id = $_SESSION['curt_question_id'];

// 画像ファイル名の生成
function create_img($year, $siken, $question_id, $img_idx) {
    // 春か免除なら0、秋なら1
    if ($siken == 'spring' || $siken == 'menjo')
    {
        $season = '0';
    } 
    else{
        $season = '1';
    }
    $question_code = substr($question_id, -2); // question_idの最後の2桁を取得
    return "{$year}{$season}{$question_code}-{$img_idx}.gif";
}

function display_image($year, $siken, $question_id, $img_idx) {
    $img_src = "/php/卒業研究/data/image/" . create_img($year, $siken, $question_id, $img_idx);
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $img_src))
    {
        return '<p style="color: red;">画像が見つかりません ' . $img_src . '</p>';
    }
    return '<img src="' . $img_src . '" style="max-width: 100%; height: auto;">';
}

function display_option($option, $year, $siken, $question_id, $img_idx) {
    if (strpos($option, 'img data-image-index') !== false)
    {
        preg_match('/img data-image-index="(\d+)"/', $option, $matches);
        $img_idx = $matches[1];
        return display_image($year, $siken, $question_id, $img_idx);
    } 
    else
    {
        return htmlspecialchars($option);
    }
}

if (isset($_POST['submit_ans']))
{
    $select_idx = $_POST['select'];
    $current_question = get_question_by_id($questions, $curt_id);

    if ($current_question)
    {
        $ans_idx = $current_question['answer'];
        
        // 最初の問題に戻るボタン
        echo '<form method="post"><button type="submit" name="reset">最初の問題に戻る</button></form>';
        echo '<h2>結果</h2>';
        echo '<div>';
        echo '<p><strong>問題:</strong> ' . $current_question['mondai'] . '</p>';
        if (strpos($current_question['mondai'], 'img data-image-index') !== false)
        {
            echo display_image($year, $siken, $current_question['id'], 0);
        }
        echo '<p><strong>あなたの選択:</strong> ' . display_option($current_question['sentaku'][$select_idx], $year, $siken, $current_question['id'], $select_idx) . '</p>';
        if ($select_idx == $ans_idx)
        {
            echo '<p><strong>結果:</strong> 正解</p>';
        }
        else
        {
            echo '<p><strong>結果:</strong> 不正解</p>';
        }
        echo '<p><strong>正解:</strong> ' . display_option($current_question['sentaku'][$ans_idx], $year, $siken, $current_question['id'], $ans_idx) . '</p>';
        echo '</div>';
        echo '<hr>';
    }
    else
    {
        echo '<p>問題が見つかりません。</p>';
    }

    // 次の問題に進むボタン
    echo '<form method="post"><button type="submit" name="next">次の問題へ</button></form>';
}
elseif (isset($_POST['next']))
{
    // インクリメントして次の問題に進む
    $_SESSION['curt_question']++;
    $next_question_id = $_SESSION['curt_question_id'] + 1;
    $next_question = get_question_by_id($questions, $next_question_id);
    if ($next_question)
    {
        $_SESSION['curt_question_id'] = $next_question['id'];
        // 次の問題を表示する
        question_print($next_question, $year, $siken, $_SESSION['curt_question']);
    }
    else
    {
        $_SESSION['curt_question'] = 0; // すべての問題が終わったら最初に戻る
        $_SESSION['curt_question_id'] = $questions[0]['quizzes'][0]['id'];
        $question = $questions[0]['quizzes'][0];
        question_print($question, $year, $siken, $_SESSION['curt_question']);
    }
}
elseif (isset($_POST['reset']))
{
    // 最初の問題に戻る
    $_SESSION['curt_question'] = 0;
    $_SESSION['curt_question_id'] = $questions[0]['quizzes'][0]['id'];
    $question = $questions[0]['quizzes'][0];
    question_print($question, $year, $siken, $_SESSION['curt_question']);
}
else
{
    // 現在の問題を表示
    $current_question = get_question_by_id($questions, $curt_id);
    if ($current_question) {
        question_print($current_question, $year, $siken, $curt_idx);
    } else {
        echo '<p>問題が見つかりません。</p>';
    }
}

function question_print($question, $year, $siken, $question_idx) {
    // 最初の問題に戻るボタン
    echo '<form method="post"><button type="submit" name="reset">最初の問題に戻る</button></form>';
    echo '<form method="post">';
    echo '<div>';
    echo '<p><strong>問題:</strong> ' . $question['mondai'] . '</p>';  // 画像タグをそのまま表示
    if (strpos($question['mondai'], 'img data-image-index') !== false)
    {
        echo display_image($year, $siken, $question['id'], 0); // 画像を表示
    }
    echo '<p><strong>選択肢:</strong></p>';
    echo '<ul>';
    $labels = ['ア', 'イ', 'ウ', 'エ'];
    if (isset($question['kaitou']))
    {
        foreach ($labels as $index => $label)
        {
            echo '<li><button type="submit" name="select" value="' . $index . '">' . $label . '</button></li>';
        }
    }
    elseif (is_array($question['sentaku']))
    {
        foreach ($question['sentaku'] as $opt_idx => $option)
        {
            echo '<li><button type="submit" name="select" value="' . $opt_idx . '">' . $labels[$opt_idx] . '</button><br>' . display_option($option, $year, $siken, $question['id'], $opt_idx) . '</li>';
        }
    }
    echo '</ul>';
    echo '</div>';
    echo '<input type="hidden" name="submit_ans" value="1">';
    echo '</form>';
}
?>
