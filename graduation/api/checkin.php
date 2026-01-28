<?php
require_once __DIR__.'/../inc/db.php';

$token = $_GET['token'] ?? '';
if(!$token){ http_response_code(400); exit('Missing token'); }

$pdo = db();
$q = $pdo->prepare("SELECT id, student_id, purpose, used_at FROM qr_tokens WHERE token=?");
$q->execute([$token]);
$t = $q->fetch();
if(!$t){ http_response_code(404); exit('Invalid token'); }

if($t['used_at']){
  echo "Този QR вече е използван на: ".$t['used_at'];
  exit;
}

$pdo->prepare("UPDATE qr_tokens SET used_at=NOW() WHERE id=?")->execute([$t['id']]);

switch($t['purpose']){
  case 'checkin':
    $pdo->prepare("UPDATE grad_process SET ceremony_checked_in_at=NOW(), stage=GREATEST(stage,2) WHERE student_id=?")->execute([$t['student_id']]);
    echo "OK: check-in";
    break;
  case 'gown_take':
    $pdo->prepare("UPDATE grad_process SET gown_taken=1 WHERE student_id=?")->execute([$t['student_id']]);
    echo "OK: тога получена";
    break;
  case 'gown_return':
    $pdo->prepare("UPDATE grad_process SET gown_returned=1 WHERE student_id=?")->execute([$t['student_id']]);
    echo "OK: тога върната";
    break;
  case 'diploma':
    $pdo->prepare("UPDATE grad_process SET diploma_received_at=NOW(), stage=3 WHERE student_id=?")->execute([$t['student_id']]);
    echo "OK: диплома получена";
    break;
  default:
    echo "Unknown purpose";
}
