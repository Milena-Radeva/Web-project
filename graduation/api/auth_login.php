<?php

require_once __DIR__.'/../inc/db.php';
$email = $_POST['email'] ?? '';
$pass  = $_POST['pass'] ?? '';

$stmt = db()->prepare("SELECT id,email,pass_hash,role,full_name FROM users WHERE email=?");
$stmt->execute([$email]);
$u = $stmt->fetch();

/*if (!$u) {
  exit("Няма такъв потребител: " . htmlspecialchars($email));
}

if (!password_verify($pass, $u['pass_hash'])) {
  exit("Грешна парола за: " . htmlspecialchars($email));
}*/

if (!$u || !password_verify($pass, $u['pass_hash'])) {
  header("Location: /graduation/index.php?err=1"); exit;
}
$_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email'],'role'=>$u['role'],'full_name'=>$u['full_name']];

if ($u['role']==='student') header("Location: /graduation/student/home.php");
else header("Location: /graduation/admin/dashboard.php");
exit;
