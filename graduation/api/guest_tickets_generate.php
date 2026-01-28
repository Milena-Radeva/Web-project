<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['student']);

$u = current_user();

$stmt = db()->prepare("
  SELECT s.id AS student_id, gp.guests_allowed, gp.stage
  FROM students s
  JOIN grad_process gp ON gp.student_id = s.id
  WHERE s.user_id = ?
");
$stmt->execute([$u['id']]);
$st = $stmt->fetch();

if(!$st) exit('No student');

if((int)$st['stage'] < 1){
  header("Location: /graduation/student/guests.php?msg=not_confirmed");
  exit;
}

$student_id = (int)$st['student_id'];
$allowed    = (int)$st['guests_allowed'];

if($allowed <= 0) $allowed = 2;

$cnt = db()->prepare("SELECT COUNT(*) FROM guest_tickets WHERE student_id=?");
$cnt->execute([$student_id]);
$existing = (int)$cnt->fetchColumn();

if($existing >= $allowed){
  header("Location: /graduation/student/guests.php?msg=limit");
  exit;
}

$token = bin2hex(random_bytes(16));
db()->prepare("INSERT INTO guest_tickets(student_id, token) VALUES(?,?)")
  ->execute([$student_id, $token]);

header("Location: /graduation/student/guests.php?msg=ok");
exit;
