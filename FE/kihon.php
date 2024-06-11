<?php

// GitHubからコンテンツを取得する関数
function get_github_content($repo, $path, $token) {
    $url = "https://api.github.com/repos/$repo/contents/$path";
    $options = [
        'http' => [
            'header' => [
                "User-Agent: request",
                "Authorization: token $token"
            ]
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}

// GitHubからJSONデータを取得する関数
function get_json_from_github($repo, $year, $token) {
    $path = "data/$year";
    $files = get_github_content($repo, $path, $token);
    $questions = [];
    foreach ($files as $file) {
        if (strpos($file['name'], '.json') !== false) {
            $json_content = file_get_contents($file['download_url']);
            $questions[] = json_decode($json_content, true);
        }
    }
    return $questions;
}

// ローカルフォルダからJSONデータを取得する関数
function get_json_from_local($year) {
    $path = __DIR__ . "/data/$year";
    $files = scandir($path);
    $questions = [];
    foreach ($files as $file) {
        if (strpos($file, '.json') !== false) {
            $json_content = file_get_contents("$path/$file");
            $questions[] = json_decode($json_content, true);
        }
    }
    return $questions;
}

$yearly_data = [
    ['year' => "令和4年", 'spring' => ['pass_rate' => '27%', 'link' => 'print_question.php?year=2022&exam=spring'], 'fall' => ['pass_rate' => '29%', 'link' => 'print_question.php?year=2022&exam=fall']],
    ['year' => "令和3年", 'spring' => ['pass_rate' => '27%', 'link' => 'print_question.php?year=2021&exam=spring'], 'fall' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2021&exam=fall']],
    ['year' => "令和2年", 'spring' => ['pass_rate' => '28%', 'link' => 'print_question.php?year=2020&exam=spring'], 'fall' => ['pass_rate' => '19%', 'link' => 'print_question.php?year=2020&exam=fall']],
    ['year' => "令和元年", 'spring' => ['pass_rate' => '25%', 'link' => 'print_question.php?year=2019&exam=spring'], 'fall' => ['pass_rate' => '22%', 'link' => 'print_question.php?year=2019&exam=fall']],
    ['year' => "平成31年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2018&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2018&exam=fall']],
    ['year' => "平成29年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2017&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2017&exam=fall']],
    ['year' => "平成28年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2016&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2016&exam=fall']],
    ['year' => "平成27年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2015&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2015&exam=fall']],
    ['year' => "平成26年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2014&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2014&exam=fall']],
    ['year' => "平成25年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2013&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2013&exam=fall']],
    ['year' => "平成24年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2012&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2012&exam=fall']],
    ['year' => "平成23年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2011&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2011&exam=fall']],
    ['year' => "平成22年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2010&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2010&exam=fall']],
    ['year' => "平成21年", 'spring' => ['pass_rate' => '26%', 'link' => 'print_question.php?year=2009&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'print_question.php?year=2009&exam=fall']],
];


?>

<!DOCTYPE html>
<html>
<head>
    <title>基本情報年度別データ</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 150vh;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div>
        <h1>基本情報年度別データ</h1>
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th>年度</th>
                        <th>春(午前)</th>
                        <th>春(午後)</th>
                        <th>秋(午前)</th>
                        <th>秋(午後)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($yearly_data as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['year']); ?></td>
                            <td>
                                <input type="checkbox" name="spring_morning[]" value="<?php echo htmlspecialchars($item['year'] . '-spring-morning'); ?>">
                                <a href="<?php echo htmlspecialchars($item['spring']['link']); ?>"><?php echo htmlspecialchars($item['spring']['pass_rate']); ?></a>
                            </td>
                            <td>
                                <input type="checkbox" name="spring_afternoon[]" value="<?php echo htmlspecialchars($item['year'] . '-spring-afternoon'); ?>">
                                <a href="<?php echo htmlspecialchars($item['spring']['link']); ?>"><?php echo htmlspecialchars($item['spring']['pass_rate']); ?></a>
                            </td>
                            <td>
                                <input type="checkbox" name="fall_morning[]" value="<?php echo htmlspecialchars($item['year'] . '-fall-morning'); ?>">
                                <a href="<?php echo htmlspecialchars($item['fall']['link']); ?>"><?php echo htmlspecialchars($item['fall']['pass_rate']); ?></a>
                            </td>
                            <td>
                                <input type="checkbox" name="fall_afternoon[]" value="<?php echo htmlspecialchars($item['year'] . '-fall-afternoon'); ?>">
                                <a href="<?php echo htmlspecialchars($item['fall']['link']); ?>"><?php echo htmlspecialchars($item['fall']['pass_rate']); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="load_questions">問題を表示する</button>
        </form>

        <?php
        if (isset($_POST['load_questions'])) {
            $repo = 'suzu6331/graduate';
            $token = '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';
            $year = '2022';  // 必要に応じて変更
            $source = 'github';  // 'github' または 'local'

            if ($source == 'github') {
                $questions = get_json_from_github($repo, $year, $token);
            } else {
                $questions = get_json_from_local($year);
            }

            echo '<h2>' . $year . '年度の問題</h2>';
            foreach ($questions as $question_set) {
                foreach ($question_set['quizzes'] as $question) {
                    echo '<div>';
                    echo '<p><strong>問題ID:</strong> ' . htmlspecialchars($question['id']) . '</p>';
                    echo '<p><strong>問題:</strong> ' . htmlspecialchars($question['mondai']) . '</p>';
                    echo '<p><strong>選択肢:</strong></p>';
                    echo '<ul>';
                    foreach ($question['sentaku'] as $option) {
                        if (strpos($option, 'img') !== false) {
                            echo '<li>' . $option . '</li>';
                        } else {
                            echo '<li>' . htmlspecialchars($option) . '</li>';
                        }
                    }
                    echo '</ul>';
                    echo '<p><strong>答え:</strong> ' . htmlspecialchars($question['answer']) . '</p>';
                    echo '</div>';
                    echo '<hr>';
                }
            }
        }
        ?>
    </div>
</body>
</html>
