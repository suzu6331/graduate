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

    var_dump($path); // パスを確認
    var_dump($files); // ファイルの一覧を確認

    $questions = [];
    foreach ($files as $file) {
        if (strpos($file, '.json') !== false) {
            $json_content = file_get_contents("$path/$file");

            var_dump($file); // ファイル名を確認
            var_dump($json_content); // ファイル内容を確認

            $decoded_content = json_decode($json_content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                die('JSONのデコードに失敗しました: ' . json_last_error_msg());
            }

            $questions[] = $decoded_content;
        }
    }
    return $questions;
}



$yearly_data = [
    ['year' => "令和4年", 'spring' => ['pass_rate' => '27%', 'link' => 'start.php?year=2022&exam=spring'], 'fall' => ['pass_rate' => '29%', 'link' => 'start.php?year=2022&exam=fall']],
    ['year' => "令和3年", 'spring' => ['pass_rate' => '27%', 'link' => 'start.php?year=2021&exam=spring'], 'fall' => ['pass_rate' => '26%', 'link' => 'start.php?year=2021&exam=fall']],
    ['year' => "令和2年", 'spring' => ['pass_rate' => '28%', 'link' => 'start.php?year=2020&exam=spring'], 'fall' => ['pass_rate' => '19%', 'link' => 'start.php?year=2020&exam=fall']],
    ['year' => "令和元年", 'spring' => ['pass_rate' => '25%', 'link' => 'start.php?year=2019&exam=spring'], 'fall' => ['pass_rate' => '22%', 'link' => 'start.php?year=2019&exam=fall']],
    ['year' => "平成31年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2018&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2018&exam=fall']],
    ['year' => "平成29年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2017&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2017&exam=fall']],
    ['year' => "平成28年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2016&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2016&exam=fall']],
    ['year' => "平成27年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2015&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2015&exam=fall']],
    ['year' => "平成26年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2014&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2014&exam=fall']],
    ['year' => "平成25年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2013&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2013&exam=fall']],
    ['year' => "平成24年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2012&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2012&exam=fall']],
    ['year' => "平成23年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2011&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2011&exam=fall']],
    ['year' => "平成22年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2010&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2010&exam=fall']],
    ['year' => "平成21年", 'spring' => ['pass_rate' => '26%', 'link' => 'start.php?year=2009&exam=spring'], 'fall' => ['pass_rate' => '17%', 'link' => 'start.php?year=2009&exam=fall']],
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
            height: 100vh;
            margin: 0;
        }
        table {
            width: 80%;
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
        <table>
            <thead>
                <tr>
                    <th>年度</th>
                    <th>春(午前)</th>
                    <th>秋(午後)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($yearly_data as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['year']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($item['spring']['link']); ?>"><?php echo htmlspecialchars($item['spring']['pass_rate']); ?></a>
                        </td>
                        <td>
                            <a href="<?php echo htmlspecialchars($item['fall']['link']); ?>"><?php echo htmlspecialchars($item['fall']['pass_rate']); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
