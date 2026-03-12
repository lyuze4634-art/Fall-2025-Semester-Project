<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

//MySQL连接信息
$db_host = "127.0.0.1";
$db_name = "db46";
$db_user = "DB46";
$db_pass = "7mjAK74cmrdNX2WY";

try {
  $pdo = new PDO(
    "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
    $db_user,
    $db_pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => "DB connection failed"]);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // 读取评论列表（最新在前）
  $stmt = $pdo->query("SELECT name, content FROM comments ORDER BY created_at DESC, id DESC LIMIT 200");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(["comments" => $rows], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  // 未登录返回 401，让前端跳转
  if (!isset($_SESSION['username']) || trim($_SESSION['username']) === "") {
    http_response_code(401);
    echo json_encode(["error" => "not_logged_in"]);
    exit;
  }

  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);

  $name = trim($_SESSION['username']); // 以登录用户名作为评论者名字
  $content = isset($data['content']) ? trim($data['content']) : "";

  if ($content === "" || mb_strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_content"]);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO comments (name, content) VALUES (?, ?)");
  $stmt->execute([$name, $content]);

  echo json_encode(["ok" => true], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(405);
echo json_encode(["error" => "method_not_allowed"]);
