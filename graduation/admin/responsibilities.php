<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$pdo = db();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $type = $_POST['type'] ?? 'gowns';
  $name = $_POST['person_name'] ?? '';
  $email= $_POST['email'] ?? '';
  $phone= $_POST['phone'] ?? '';
  if($name){
    $pdo->prepare("INSERT INTO responsibilities(type,person_name,email,phone) VALUES(?,?,?,?)")
        ->execute([$type,$name,$email,$phone]);
  }
  header("Location: /graduation/admin/responsibilities.php"); exit;
}
$rows = $pdo->query("SELECT * FROM responsibilities ORDER BY type, active DESC, person_name")->fetchAll();

?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Отговорници</title></head><body>
<div class="topbar"><b>Отговорници</b><a class="btn" href="/graduation/admin/dashboard.php">Назад</a></div>
<div class="container">
  <div class="card">
    <h3>Добави</h3>
    <form method="post" class="row">
      <div><label>Тип</label>
        <select name="type">
          <option value="gowns">Тоги</option>
          <option value="signatures">Подписи</option>
          <option value="diplomas">Дипломи</option>
        </select>
      </div>
      <div><label>Име</label><input name="person_name" required></div>
      <div><label>Email</label><input name="email"></div>
      <div><label>Телефон</label><input name="phone"></div>
      <div style="grid-column:1/-1"><button class="btn primary">Запази</button></div>
    </form>
  </div>

  <div class="card">
    <h3>Списък</h3>
    <table class="table">
      <thead><tr><th>Тип</th><th>Име</th><th>Email</th><th>Телефон</th><th>Статус</th><th>Действие</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=h($r['type'])?></td>
            <td><?=h($r['person_name'])?></td>
            <td><?=h($r['email'] ?? '')?></td>
            <td><?=h($r['phone'] ?? '')?></td>
            <td>
              <?= $r['active'] ? '<span style="color:green">Активен</span>' : '<span style="color:#999">Отписан</span>' ?>
            </td>

            <td>
              <form method="post" action="/graduation/api/toggle_responsibility.php">
                <input type="hidden" name="id" value="<?=h($r['id'])?>">
                <?php if($r['active']): ?>
                  <button class="btn" style="background:#fee;border-color:#f99"
                    onclick="return confirm('Сигурен ли си, че искаш да отпишеш този отговорник?')">
                    Отпиши
                  </button>
                <?php else: ?>
                  <button class="btn" style="background:#e6fffa;border-color:#6ee7b7">
                    Възстанови
                  </button>
                <?php endif; ?>
              </form>
            </td>

          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
