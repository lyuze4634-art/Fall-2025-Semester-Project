<?php
header('Content-Type: application/json; charset=utf-8');

function respond($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
  respond(['error' => 'config.php not found', 'path' => $configPath], 500);
}
$config = require $configPath;

$API_KEY  = trim((string)($config['DEEPL_KEY'] ?? ''));
$ENDPOINT = trim((string)($config['DEEPL_ENDPOINT'] ?? ''));
$SOURCE   = strtoupper(trim((string)($config['SOURCE_LANG'] ?? 'JA')));

if ($API_KEY === '' || $ENDPOINT === '') {
  respond(['error' => 'Missing DEEPL_KEY or DEEPL_ENDPOINT'], 500);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(['error' => 'Method not allowed'], 405);
}

// 读取参数：支持 texts[] 和 texts_json
$target = isset($_POST['target_lang']) ? strtoupper(trim((string)$_POST['target_lang'])) : '';
$texts  = [];

if (isset($_POST['texts']) && is_array($_POST['texts'])) {
  $texts = $_POST['texts']; // texts[]=a&texts[]=b
} elseif (isset($_POST['texts_json'])) {
  $tmp = json_decode((string)$_POST['texts_json'], true);
  if (is_array($tmp)) $texts = $tmp;
}

$texts = array_values(array_filter(array_map('strval', $texts), fn($s) => trim($s) !== ''));

if ($target === '') {
  respond(['error' => 'bad request: missing target_lang'], 400);
}
if (count($texts) === 0) {
  // 把收到的POST全部返回
  respond(['error' => 'bad request: missing texts', 'debug_post' => $_POST], 400);
}

//手动构造DeepL表单：text=...&text=...关键修复点
$params = [];
$params[] = 'target_lang=' . rawurlencode($target);
$params[] = 'source_lang=' . rawurlencode($SOURCE);
foreach ($texts as $t) {
  $params[] = 'text=' . rawurlencode($t);
}
$body = implode('&', $params);

$ch = curl_init($ENDPOINT);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_POSTFIELDS => $body,
  CURLOPT_HTTPHEADER => [
    'Authorization: DeepL-Auth-Key ' . $API_KEY,
    'Content-Type: application/x-www-form-urlencoded'
  ],
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
$code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
  respond(['error' => 'cURL error', 'detail' => $err], 500);
}

if ($code < 200 || $code >= 300) {
  respond([
    'error' => 'DeepL API error',
    'status' => $code,
    'body' => $response,
    'debug_sent' => [
      'endpoint' => $ENDPOINT,
      'source_lang' => $SOURCE,
      'target_lang' => $target,
      'texts_count' => count($texts)
    ]
  ], $code);
}

$data = json_decode($response, true);
$out = [];

if (isset($data['translations']) && is_array($data['translations'])) {
  foreach ($data['translations'] as $tr) {
    $out[] = (string)($tr['text'] ?? '');
  }
}

respond(['translations' => $out]);
