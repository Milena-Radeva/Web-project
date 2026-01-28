<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$student_id = (int)($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

if(!$student_id || !$action) exit('Bad request');

$pdo = db();

switch($action){

  case 'confirm': 
    $pdo->prepare("UPDATE grad_process SET stage=1, confirmed_at=NOW() WHERE student_id=?")->execute([$student_id]);
    break;

  case 'back_to_edit': 
    $pdo->prepare("UPDATE grad_process SET stage=0 WHERE student_id=?")->execute([$student_id]);
    break;

  case 'checkin': 
    $pdo->prepare("UPDATE grad_process SET stage=2, ceremony_checked_in_at=NOW() WHERE student_id=?")->execute([$student_id]);
    break;

  case 'finish': 
    $pdo->prepare("UPDATE grad_process SET stage=3, diploma_received_at=NOW() WHERE student_id=?")->execute([$student_id]);
    break;

  case 'gown_take':
    $pdo->prepare("UPDATE grad_process SET gown_taken=1 WHERE student_id=?")->execute([$student_id]);
    break;

  case 'gown_return':
    $pdo->prepare("UPDATE grad_process SET gown_returned=1 WHERE student_id=?")->execute([$student_id]);
    break;

  case 'set_honors':
  $pdo->prepare("UPDATE grad_process SET is_honors = 1 WHERE student_id=?")
      ->execute([$student_id]);
  break;

  default:
    exit('Unknown action');
}

header("Location: /graduation/admin/dashboard.php");
exit;
