<?php
header("Content-Type: application/json");
session_start();
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user["password_hash"])) {
  echo json_encode(["ok" => false, "message" => "账号或密码错误"]);
  exit;
}

$_SESSION["username"] = $username;
echo json_encode(["ok" => true, "message" => "登录成功"]);
