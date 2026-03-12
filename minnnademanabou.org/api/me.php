<?php
header("Content-Type: application/json");
session_start();

if (!isset($_SESSION["username"])) {
  echo json_encode(["ok" => false]);
  exit;
}

echo json_encode([
  "ok" => true,
  "username" => $_SESSION["username"]
]);
