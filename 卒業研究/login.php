<?php

$host = 'localhost';
$db = 'test';
$user = 'root';
$pass = 'password';

try
{
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
    die("データベースに接続できませんでした: " . $e->getMessage());
}
$username = $_POST['username'];
$password = $_POST['password'];

if ($username === null || $password === null) {die("POSTデータが正しく送信されていません。");}

$query = "SELECT * FROM user WHERE username = :username AND password = :password";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':password', $password);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user)
{
    $_SESSION['user'] = $user;
    header('Location: choice.php');
    exit;
} 
else {echo "ユーザー名またはパスワードが正しくありません。";}

$pdo = null;
?>

