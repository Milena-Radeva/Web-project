<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['student']);

$u = current_user();

$stmt = db()->prepare("
  SELECT s.id AS student_id
  FROM students s
  WHERE s.user_id=?
");
$stmt->execute([$u['id']]);
$s = $stmt->fetch();
if(!$s) exit("No student");

$student_id = (int)$s['student_id'];

$exists = db()->prepare("SELECT token FROM student_qr WHERE student_id=?");
$exists->execute([$student_id]);
$old = $exists->fetch();

if($old){
  header("Location: /graduation/student/home.php?msg=already");
  exit;
}

$token = bin2hex(random_bytes(16));
db()->prepare("INSERT INTO student_qr(student_id, token) VALUES(?,?)")
  ->execute([$student_id, $token]);

header("Location: /graduation/student/home.php?msg=qr_ok");
