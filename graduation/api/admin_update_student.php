<?php
/*error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>"; print_r($_POST); echo "</pre>";
exit;*/

require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$student_id = (int)($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

if(!$student_id || !$action) exit('Bad request');

$pdo = db();

switch($action){

  case 'confirm': // stage 0 -> 1
    $pdo->prepare("UPDATE grad_process SET stage=1, confirmed_at=NOW() WHERE student_id=?")->execute([$student_id]);
    break;

  case 'back_to_edit': // stage 1 -> 0
    $pdo->prepare("UPDATE grad_process SET stage=0 WHERE student_id=?")->execute([$student_id]);
    break;

  case 'checkin': // stage 1 -> 2
    $pdo->prepare("UPDATE grad_process SET stage=2, ceremony_checked_in_at=NOW() WHERE student_id=?")->execute([$student_id]);
    break;

  case 'finish': // stage 2 -> 3
    $pdo->prepare("UPDATE grad_process SET stage=3, diploma_received_at=NOW() WHERE student_id=?")->execute([$student_id]);
    break;

  case 'gown_take':
    $pdo->prepare("UPDATE grad_process SET gown_taken=1 WHERE student_id=?")->execute([$student_id]);
    break;

  case 'gown_return':
    $pdo->prepare("UPDATE grad_process SET gown_returned=1 WHERE student_id=?")->execute([$student_id]);
    break;

  case 'toggle_honors':
    $pdo->prepare("UPDATE grad_process SET is_honors = 1 - is_honors WHERE student_id=?")->execute([$student_id]);
    break;

  case 'toggle_badge':
    $pdo->prepare("UPDATE grad_process SET reward_badge = 1 - reward_badge WHERE student_id=?")->execute([$student_id]);
    break;

  case 'toggle_calendar':
    $pdo->prepare("UPDATE grad_process SET reward_calendar = 1 - reward_calendar WHERE student_id=?")->execute([$student_id]);
    break;

  default:
    exit('Unknown action');
}

header("Location: /graduation/admin/dashboard.php");
exit;
