<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();
$s = db()->prepare("SELECT s.id, s.program_name, gp.stage, gp.notes FROM students s JOIN grad_process gp ON gp.student_id=s.id WHERE s.user_id=?");
$s->execute([$u['id']]);
$st = $s->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $notes = $_POST['notes'] ?? '';
  $gown_requested = isset($_POST['gown_requested']) ? 1 : 0;

  // ако етапът е 0 (регистриран), позволяваме редакция
  db()->prepare("UPDATE grad_process SET notes=?, gown_requested=? WHERE student_id=?")
    ->execute([$notes, $gown_requested, $st['id']]);

  header("Location: /graduation/student/home.php"); exit;
}
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<script src="/graduation/assets/app.js" defer></script>
<title>Заявка</title></head><body>
<div class="topbar">
  <b>Заявка за дипломиране</b>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/student/home.php">Назад</a>
</div>
<div class="container">
  <div class="card">
    <form method="post">
      <label>Бележка (можеш да вмъкнеш цитат като <code>&lt;cite ...&gt;</code>)</label>
      <textarea name="notes" rows="6"><?=h($st['notes'] ?? '')?></textarea>

      <p>
        <label><input type="checkbox" name="gown_requested" value="1"> Заявявам тога</label>
      </p>

      <p class="small">
        Пример за стилизирано цитиране:
        <cite class="cite-chip" data-cite-id="CIT-001">CIT-001</cite>
      </p>

      <p><button class="btn primary">Запази</button></p>
    </form>
  </div>

  <div class="card">
    <h3>Вмъкни цитат</h3>
    <div id="citationsBox" class="small">Зареждане…</div>
  </div>
</div>
</body></html>
