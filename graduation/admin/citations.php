<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);
$pdo = db();

if(isset($_GET['json'])){
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($pdo->query("SELECT key_code, quote_text, source_text FROM citations ORDER BY id DESC")->fetchAll());
  exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $key = $_POST['key_code'] ?? '';
  $qt  = $_POST['quote_text'] ?? '';
  $src = $_POST['source_text'] ?? '';
  if($key && $qt){
    $pdo->prepare("INSERT INTO citations(key_code,quote_text,source_text) VALUES(?,?,?)")->execute([$key,$qt,$src]);
  }
  header("Location: /graduation/admin/citations.php"); exit;
}

$rows = $pdo->query("SELECT * FROM citations ORDER BY id DESC")->fetchAll();
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Цитати</title></head><body>
<div class="topbar"><b>Цитати</b><a class="btn" href="/graduation/admin/dashboard.php">Назад</a></div>
<div class="container">
  <div class="card">
    <h3>Добави цитат</h3>
    <form method="post" class="row">
      <div><label>Код</label><input name="key_code" placeholder="CIT-003" required></div>
      <div><label>Източник</label><input name="source_text" placeholder="книга/статия/автор"></div>
      <div style="grid-column:1/-1"><label>Текст</label><textarea name="quote_text" rows="4" required></textarea></div>
      <div style="grid-column:1/-1"><button class="btn primary">Запази</button></div>
    </form>
  </div>

  <?php foreach($rows as $c): ?>
    <div class="card">
      <cite class="cite-chip" data-cite-id="<?=h($c['key_code'])?>"><?=h($c['key_code'])?></cite>
      <p><?=h($c['quote_text'])?></p>
      <div class="small"><?=h($c['source_text'] ?? '')?></div>
    </div>
  <?php endforeach; ?>
</div>
</body></html>
