<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$token = $_GET['token'] ?? '';
if(!$token){ http_response_code(400); exit("Missing token"); }

$stmt = db()->prepare("
  SELECT gt.id, gt.used_at, u.full_name AS student_name, s.group_code
  FROM guest_tickets gt
  JOIN students s ON s.id=gt.student_id
  JOIN users u ON u.id=s.user_id
  WHERE gt.token=?
");
$stmt->execute([$token]);
$t = $stmt->fetch();

if(!$t){ http_response_code(404); exit("❌ Невалиден билет"); }

if($t['used_at']){
  exit("⚠️ Билетът вече е използван на: ".$t['used_at']." (Студент: ".$t['student_name'].")");
}

db()->prepare("UPDATE guest_tickets SET used_at=NOW(), used_by_user_id=? WHERE id=?")
  ->execute([current_user()['id'], $t['id']]);

echo "✅ OK: Вход разрешен. Студент: ".$t['student_name']." • Група: ".$t['group_code'];
