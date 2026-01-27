<?php

require_once __DIR__.'/../inc/db.php';
$email = $_POST['email'] ?? '';
$pass  = $_POST['pass'] ?? '';

$stmt = db()->prepare("SELECT id,email,pass_hash,role,full_name FROM users WHERE email=?");
$stmt->execute([$email]);
$u = $stmt->fetch();

$hash = hash('sha256', $pass);
if (!$u || !hash_equals($u['pass_hash'], $hash)) {
  header("Location: /graduation/index.php?err=1"); exit;
}
$_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email'],'role'=>$u['role'],'full_name'=>$u['full_name']];

if ($u['role']==='student') header("Location: /graduation/student/home.php");
else header("Location: /graduation/admin/dashboard.php");
exit;
