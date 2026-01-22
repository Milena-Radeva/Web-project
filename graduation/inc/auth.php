<?php
require_once __DIR__ . '/db.php';

function current_user() {
  return $_SESSION['user'] ?? null;
}

function require_login() {
  if (!current_user()) { header('Location: /graduation/index.php'); exit; }
}

function require_role(array $roles) {
  require_login();
  $u = current_user();
  if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('403 Forbidden'); }
}
