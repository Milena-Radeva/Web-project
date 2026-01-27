<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$id = (int)($_POST['id'] ?? 0);
if(!$id){
  header("Location: /graduation/admin/responsibilities.php");
  exit;
}

$pdo = db();
$pdo->prepare("DELETE FROM responsibilities WHERE id=?")->execute([$id]);

header("Location: /graduation/admin/responsibilities.php?deleted=1");
exit;
