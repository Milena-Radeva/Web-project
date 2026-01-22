<?php
require_once __DIR__.'/../inc/auth.php';
require_login();
$u = current_user();
$key = $_POST['key'] ?? '';
$context = $_POST['context'] ?? 'unknown';

$stmt = db()->prepare("SELECT id FROM citations WHERE key_code=?");
$stmt->execute([$key]);
$c = $stmt->fetch();
if(!$c){ http_response_code(404); exit('No citation'); }

db()->prepare("INSERT INTO citation_uses(citation_id,user_id,context) VALUES(?,?,?)")
  ->execute([$c['id'], $u['id'], $context]);

echo "OK";
