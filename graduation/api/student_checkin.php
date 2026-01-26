<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$token = $_GET['token'] ?? '';
if(!$token) exit("Missing token");

$stmt = db()->prepare("
  SELECT sq.id, sq.student_id, sq.used_at, u.full_name
  FROM student_qr sq
  JOIN students s ON s.id = sq.student_id
  JOIN users u ON u.id = s.user_id
  WHERE sq.token=?
");
$stmt->execute([$token]);
$r = $stmt->fetch();

if(!$r) exit("❌ Невалиден QR код");

if($r['used_at']){
  exit("⚠️ Студентът вече е влязъл: ".$r['full_name']);
}

db()->prepare("
  UPDATE student_qr 
  SET used_at = NOW(), used_by_user_id=?
  WHERE id=?
")->execute([current_user()['id'], $r['id']]);

db()->prepare("
  UPDATE grad_process 
  SET ceremony_checked_in_at = NOW(), stage = 2
  WHERE student_id = ?
")->execute([$r['student_id']]);

echo "✅ Check-in успешен за студент: ".$r['full_name'];
