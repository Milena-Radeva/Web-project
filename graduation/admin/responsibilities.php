<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$pdo = db();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $type = $_POST['type'] ?? 'gowns';
  $name = $_POST['person_name'] ?? '';
  $email= $_POST['email'] ?? '';
  $phone = trim($_POST['phone'] ?? '');
  $phoneNorm = preg_replace('/[^\d+]/', '', $phone);

  if ($phoneNorm !== '' && !preg_match('/^(\+?359|0)8\d{8}$/', $phoneNorm)) {
    $_SESSION['flash'] = ['type'=>'error', 'msg'=>'Невалиден телефон. Пример: 0888123456 или +359888123456'];
    header("Location: /graduation/admin/responsibilities.php");
    exit;
  }
  $phone = $phoneNorm;

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
<div class="topbar">
  <b>Отговорници</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
  <a class="btn" style="margin-left:auto" href="/graduation/api/auth_logout.php">Изход</a>
</div>
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
      <div><label>Телефон</label><input
                                        name="phone"
                                        inputmode="tel"
                                        placeholder="08xx xxx xxx или +359..."
                                         pattern="^(\+359|0)8[7-9]\d{7}$"
                                        title="Пример: 0888123456 или +359888123456"
                                      >

      <div style="grid-column:1/-1"><button class="btn primary">Запази</button></div>
    </form>
  </div>

  <div class="card">
    <h3>Списък</h3>
    <table class="table">
      <thead><tr><th>Тип</th><th class='center'>Име</th><th class='center'>Email</th><th class='center'>Телефон</th><th class='center'>Действие</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=h($r['type'])?></td>
            <td class='center'><?=h($r['person_name'])?></td>
            <td class='center'><?=h($r['email'] ?? '')?></td>
            <td class='center'><?=h($r['phone'] ?? '')?></td>
            <td class='center'>
            <form method="post" action="/graduation/api/delete_responsible.php"
                  onsubmit="return confirm('Сигурна ли си, че искаш да изтриеш този отговорник?');">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn" style="background:#fee;border-color:#f99">
                Отпиши
              </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
