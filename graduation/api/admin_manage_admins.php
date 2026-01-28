<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['superadmin']); // само superadmin

$pdo = db();
$me  = current_user();

$action = $_POST['action'] ?? '';

if ($action === 'create_admin') {
  $email = trim($_POST['email'] ?? '');
  $name  = trim($_POST['full_name'] ?? '');
  $pass  = $_POST['password'] ?? '';

  if (!$email || !$name || !$pass) {
    header("Location: /graduation/admin/admins.php?err=Липсват+данни");
    exit;
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /graduation/admin/admins.php?err=Невалиден+email");
    exit;
  }

  $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
  $exists->execute([$email]);
  if ($exists->fetchColumn()) {
    header("Location: /graduation/admin/admins.php?err=Този+email+вече+съществува");
    exit;
  }

  $hash = hash('sha256', $pass);

  $pdo->prepare("
    INSERT INTO users(email, pass_hash, role, full_name)
    VALUES(?, ?, 'admin', ?)
  ")->execute([$email, $hash, $name]);

  header("Location: /graduation/admin/admins.php?ok=Администраторът+е+добавен");
  exit;
}

if ($action === 'delete_admin') {
  $userId = (int)($_POST['user_id'] ?? 0);
  if (!$userId) {
    header("Location: /graduation/admin/admins.php?err=Липсва+ID");
    exit;
  }

  if ($userId === (int)$me['id']) {
    header("Location: /graduation/admin/admins.php?err=Не+можеш+да+изтриеш+себе+си");
    exit;
  }

  $role = $pdo->prepare("SELECT role FROM users WHERE id=?");
  $role->execute([$userId]);
  $r = $role->fetchColumn();

  if (!$r) {
    header("Location: /graduation/admin/admins.php?err=Няма+такъв+потребител");
    exit;
  }

  if ($r === 'superadmin') {
    header("Location: /graduation/admin/admins.php?err=Не+можеш+да+триеш+superadmin");
    exit;
  }

  $hasStudent = $pdo->prepare("SELECT id FROM students WHERE user_id=?");
  $hasStudent->execute([$userId]);
  if ($hasStudent->fetchColumn()) {
    header("Location: /graduation/admin/admins.php?err=Този+потребител+е+студент+и+не+се+трие+тук");
    exit;
  }

  $pdo->prepare("DELETE FROM users WHERE id=? AND role='admin'")->execute([$userId]);

  header("Location: /graduation/admin/admins.php?ok=Администраторът+е+изтрит");
  exit;
}

header("Location: /graduation/admin/admins.php?err=Невалидно+действие");
