<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$student_id = (int)($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

if(!$student_id) exit('Bad request');

switch($action){
  case 'gown_taken':
    db()->prepare("
      UPDATE grad_process 
      SET gown_taken = 1 
      WHERE student_id=? AND gown_taken=0
    ")->execute([$student_id]);
    break;

  case 'gown_returned':
    db()->prepare("
      UPDATE grad_process 
      SET gown_returned = 1 
      WHERE student_id=? AND gown_taken=1 AND gown_returned=0
    ")->execute([$student_id]);
    break;
}

header("Location: /graduation/admin/dashboard.php");
exit;
