<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

if(!isset($_FILES['csv'])){ exit('No file'); }
$tmp = $_FILES['csv']['tmp_name'];
$fh = fopen($tmp,'r');
if(!$fh) exit('Cannot open');

$pdo = db();
$pdo->beginTransaction();

$header = fgetcsv($fh); // first row header
while(($row = fgetcsv($fh)) !== false){
  $data = array_combine($header, $row);

  $email = trim($data['email'] ?? '');
  $full  = trim($data['full_name'] ?? '');
  $fn    = trim($data['faculty_no'] ?? '');
  $deg   = trim($data['degree'] ?? 'bachelor');
  $prog  = trim($data['program_name'] ?? '');
  $grp   = trim($data['group_code'] ?? '');
  $phone = trim($data['phone'] ?? '');
  $gpa = isset($data['gpa']) ? (float)str_replace(',', '.', trim($data['gpa'])) : null;


  if(!$email || !$full || !$fn) continue;

  // default pass = "student123" (можеш да го смениш)
 // паролата = факултетният номер
  $plainPass = $fn; 
  $passHash = password_hash($plainPass, PASSWORD_BCRYPT);


  // upsert user
  $stmt = $pdo->prepare("INSERT INTO users(email,pass_hash,role,full_name)
                         VALUES(?,?, 'student', ?)
                         ON DUPLICATE KEY UPDATE full_name=VALUES(full_name)");
  $stmt->execute([$email,$passHash,$full]);

  $uid = (int)($pdo->lastInsertId() ?: $pdo->query("SELECT id FROM users WHERE email=".$pdo->quote($email))->fetchColumn());

  // upsert student
  $stmt = $pdo->prepare("INSERT INTO students(user_id,faculty_no,degree,program_name,group_code,phone,gpa)
                       VALUES(?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE degree=VALUES(degree),
                                               program_name=VALUES(program_name),
                                               group_code=VALUES(group_code),
                                               phone=VALUES(phone),
                                               gpa=VALUES(gpa)");
$stmt->execute([$uid,$fn,$deg,$prog,$grp,$phone,$gpa]);

  $sid = (int)($pdo->lastInsertId() ?: $pdo->query("SELECT id FROM students WHERE faculty_no=".$pdo->quote($fn))->fetchColumn());
  $isHonors = ($gpa !== null && $gpa >= 5.50) ? 1 : 0;
  $pdo->prepare("UPDATE grad_process SET is_honors=? WHERE student_id=?")->execute([$isHonors, $sid]);


  // ensure grad_process
  $pdo->prepare("INSERT IGNORE INTO grad_process(student_id) VALUES(?)")->execute([$sid]);

  $pdo->prepare("
  UPDATE grad_process gp
  JOIN students s ON s.id = gp.student_id
  SET gp.is_honors = (s.gpa >= 5.50)
  WHERE gp.student_id = ?
")->execute([$sid]);
}
$pdo->commit();

header("Location: /graduation/admin/import_export.php?ok=1");
