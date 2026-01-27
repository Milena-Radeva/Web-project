<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_role(['superadmin']); // само superadmin

$pdo = db();
$u = current_user();

// Зареждаме всички админи + superadmin-и (без студентите)
$admins = $pdo->query("
  SELECT id, email, full_name, role, created_at
  FROM users
  WHERE role IN ('admin','superadmin')
  ORDER BY role DESC, created_at DESC
")->fetchAll();

$err = $_GET['err'] ?? '';
$ok  = $_GET['ok'] ?? '';
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/graduation/assets/styles.css">
  <title>Администратори</title>
</head>
<body>
<div class="topbar">
  <b>Администратори</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/api/auth_logout.php">Изход</a>
</div>

<div class="container">

  <?php if($ok): ?>
    <div class="card"><b>✅ <?=h($ok)?></b></div>
  <?php endif; ?>

  <?php if($err): ?>
    <div class="card"><b style="color:#b00020">❌ <?=h($err)?></b></div>
  <?php endif; ?>

  <div class="card">
    <h3>Добави нов администратор</h3>
    <form method="post" action="/graduation/api/admin_manage_admins.php" class="row">
      <input type="hidden" name="action" value="create_admin">

      <div>
        <label>Email</label>
        <input name="email" required>
      </div>

      <div>
        <label>Име</label>
        <input name="full_name" required>
      </div>

      <div style="grid-column:1/-1">
        <label>Парола</label>
        <input type="password" name="password" required>
      </div>

      <div style="grid-column:1/-1">
        <button class="btn primary">Добави</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Списък администратори</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Роля</th>
          <th>Име</th>
          <th>Email</th>
          <th>Създаден</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($admins as $a): ?>
          <tr>
            <td>
              <?php if($a['role']==='superadmin'): ?>
                <span class="badge">superadmin</span>
              <?php else: ?>
                <span class="badge">admin</span>
              <?php endif; ?>
            </td>
            <td><?=h($a['full_name'])?></td>
            <td><?=h($a['email'])?></td>
            <td class="small"><?=h($a['created_at'])?></td>
            <td>
              <?php
                $isSelf = ((int)$a['id'] === (int)$u['id']);
                $isSuper = ($a['role'] === 'superadmin');
              ?>
              <?php if($isSuper): ?>
                <span class="small" style="color:#888">Не се трие</span>
              <?php elseif($isSelf): ?>
                <span class="small" style="color:#888">Това си ти</span>
              <?php else: ?>
                <form method="post" action="/graduation/api/admin_manage_admins.php"
                      onsubmit="return confirm('Да изтрия ли този администратор?');"
                      style="display:inline">
                  <input type="hidden" name="action" value="delete_admin">
                  <input type="hidden" name="user_id" value="<?=h($a['id'])?>">
                  <button class="btn" style="background:#fee;border-color:#f99">Изтрий</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
