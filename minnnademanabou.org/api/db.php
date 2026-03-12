<?php
header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
error_reporting(E_ALL);

// ======数据库连接信息======
$DB_HOST = "127.0.0.1";
$DB_NAME = "db46";
$DB_USER = "DB46";
$DB_PASS = "7mjAK74cmrdNX2WY";
// ==========================================

$DB_CHARSET = "utf8mb4";

try {
  $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "message" => "数据库连接失败"]);
  exit;
}
