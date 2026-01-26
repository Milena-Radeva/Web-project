<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);

$id = (int)($_POST['id'] ?? 0);
if(!$id) exit('Bad request');

$pdo = db();

// обръщаме active: 1 → 0, 0 → 1
$pdo->prepare("
  UPDATE responsibilities
  SET active = 1 - active
  WHERE id = ?
")->execute([$id]);

header("Location: /graduation/admin/responsibilities.php");
exit;
