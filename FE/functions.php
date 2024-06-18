<?php
// GitHubからコンテンツを取得する関数
function get_content($repo, $path, $token) {
    $url = "https://api.github.com/repos/$repo/contents/$path"; // $repo = ユーザー名/graduate
    $retry = 3;
    while ($retry > 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'request');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: token $token"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL証明書の検証を無効にする（開発環境のみ）
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 接続タイムアウトを10秒に設定
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 全体のタイムアウトを30秒に設定
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            if ($retry > 1) {
                $retry--;
                sleep(2); // 2秒待ってリトライ
                curl_close($ch);
                continue;
            } else {
                die('GitHub APIから受け取れてないです: ' . curl_error($ch));
            }
        }
        curl_close($ch);
        return json_decode($res, true); // 連想配列でreturnする
    }
    return null; // リトライ後も失敗した場合
}

// GitHubからJSONデータを取得
function get_json_from_github($repo, $year, $token) {
    $questions = [];
    $path = "data/$year"; // 任意の年のパスを取得
    $files = get_content($repo, $path, $token);

    if (!is_array($files)) {
        die('配列じゃないです');
    }

    foreach ($files as $FILE) {
        if (strpos($FILE['name'], '.json') !== false) {
            $json_url = $FILE['download_url'];
            $retry = 3;
            while ($retry > 0) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $json_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL証明書の検証を無効にする（開発環境のみ）
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 接続タイムアウトを10秒に設定
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 全体のタイムアウトを30秒に設定
                $json_content = curl_exec($ch);
                if (curl_errno($ch)) {
                    if ($retry > 1) {
                        $retry--;
                        sleep(2); // 2秒待ってリトライ
                        curl_close($ch);
                        continue;
                    } else {
                        die('ファイルの内容を取得できませんでした: ' . curl_error($ch));
                    }
                }
                curl_close($ch);
                $questions[] = json_decode($json_content, true);
                break;
            }
        }
    }
    return $questions;
}

// ローカルからJSONデータを取得
function get_json_from_local($year) {
    $questions = [];
    $path = "C:\\xampp\\htdocs\\php\\卒業研究\\data\\$year";
    if (!is_dir($path)) {
        die("パスが間違っているか、パスが存在しません");
    }
    $files = scandir($path);

    foreach ($files as $FILE) {
        if (strpos($FILE, '.json') !== false) {
            $json_content = file_get_contents("$path\\$FILE");
            if ($json_content === FALSE) {
                die('jsonファイルを取得できませんでした');
            }

            $decoded_content = json_decode($json_content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                die('JSONのデコードに失敗しました: ' . json_last_error_msg());
            }

            $questions[] = $decoded_content;
        }
    }
    return $questions;
}

function get_question_by_id($questions, $id) {
    foreach ($questions as $question_set) {
        foreach ($question_set['quizzes'] as $quiz) {
            if ($quiz['id'] === $id) {
                return $quiz;
            }
        }
    }
    return null;
}

function question_print($question, $year, $siken, $question_idx, $total_remaining_time) {
    echo '<form method="post">';
    echo '<div>';
    echo '<p><strong>問題:</strong> ' . htmlspecialchars($question['mondai']) . '</p>';
    if (strpos($question['mondai'], 'img data-image-index') !== false) {
        echo display_image($year, $siken, $question['id'], 0);
    }
    echo '<p><strong>選択肢:</strong></p>';
    echo '<ul>';
    $labels = ['ア', 'イ', 'ウ', 'エ'];
    if (isset($question['kaitou'])) {
        foreach ($labels as $index => $label) {
            echo '<li><button type="submit" name="select" value="' . $index . '">' . $label . '</button></li>';
        }
    } elseif (is_array($question['sentaku'])) {
        foreach ($question['sentaku'] as $opt_idx => $option) {
            echo '<li><button type="submit" name="select" value="' . $opt_idx . '">' . $labels[$opt_idx] . '</button><br>' . display_option($option, $year, $siken, $question['id'], $opt_idx) . '</li>';
        }
    }
    echo '</ul>';
    echo '</div>';
    echo '<input type="hidden" name="submit_ans" value="1">';
    echo '</form>';
    echo '<form method="post"><button type="submit" name="next">次の問題へ</button></form>';
    echo '<form method="post"><button type="submit" name="reset">最初の問題に戻る</button></form>';
}

function create_img($year, $siken, $question_id, $img_idx) {
    // 春か免除なら0、秋なら1
    if ($siken == 'spring' || $siken == 'menjo') {
        $season = '0';
    } else {
        $season = '1';
    }
    $question_code = substr($question_id, -2); // question_idの最後の2桁を取得
    return "{$year}{$season}{$question_code}-{$img_idx}.gif";
}

function display_image($year, $siken, $question_id, $img_idx) {
    $img_src = "/php/卒業研究/data/image/" . create_img($year, $siken, $question_id, $img_idx);
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $img_src)) {
        return '<p style="color: red;">画像が見つかりません ' . $img_src . '</p>';
    }
    return '<img src="' . $img_src . '" style="max-width: 100%; height: auto;">';
}

function display_option($option, $year, $siken, $question_id, $img_idx) {
    if (strpos($option, 'img data-image-index') !== false) {
        preg_match('/img data-image-index="(\d+)"/', $option, $matches);
        $img_idx = $matches[1];
        return display_image($year, $siken, $question_id, $img_idx);
    } else {
        return htmlspecialchars($option);
    }
}
?>
