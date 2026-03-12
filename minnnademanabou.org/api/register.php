<?php
header("Content-Type: application/json");
session_start();
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

if ($username === "" || $password === "") {
  echo json_encode(["ok" => false, "message" => "账号或密码不能为空"]);
  exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
  $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
  $stmt->execute([$username, $hash]);
} catch (Exception $e) {
  echo json_encode(["ok" => false, "message" => "账号已存在"]);
  exit;
}

$_SESSION["username"] = $username;
echo json_encode(["ok" => true, "message" => "注册成功"]);
