<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();
$rows = db()->query("SELECT key_code, quote_text, source_text FROM citations ORDER BY id DESC")->fetchAll();
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Цитати</title></head><body>
<div class="topbar">
  <b>Цитати</b>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/student/home.php">Назад</a>
</div>

<div class="container">
  <?php foreach($rows as $c): ?>
    <div class="card">
      <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;">
        <div>
          <cite class="cite-chip" data-cite-id="<?=h($c['key_code'])?>"><?=h($c['key_code'])?></cite>
        </div>
        <button class="btn primary" onclick="cite('<?=h($c['key_code'])?>')">Цитирай</button>
      </div>

      <p><?=h($c['quote_text'])?></p>
      <div class="small"><?=h($c['source_text'] ?? '')?></div>
    </div>
  <?php endforeach; ?>
</div>

<script>
async function cite(key){
  // запис в статистиката "кой цитира какво"
  await fetch('/graduation/api/citations_use.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'key='+encodeURIComponent(key)+'&context=student_citations'
  });

  // копира в клипборда HTML cite елемент
  const html = `<cite class="cite-chip" data-cite-id="${key}">${key}</cite> `;
  await navigator.clipboard.writeText(html);
  alert('Копирано за поставяне в бележка:\n' + html + '\n\nОтиди в "Заявка" и го постави в бележката.');
}
</script>
</body></html>
