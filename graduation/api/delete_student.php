<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$student_id = (int)($_POST['student_id'] ?? 0);
if(!$student_id) exit('Bad request');

$stage = db()->prepare("SELECT stage FROM grad_process WHERE student_id=?");
$stage->execute([$student_id]);
$current = (int)$stage->fetchColumn();

if($current !== 3){
  exit("Студентът не е завършил и не може да бъде изтрит.");
}

db()->prepare("DELETE FROM students WHERE id=?")->execute([$student_id]);

echo "OK";
