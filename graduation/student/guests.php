<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();
$pdo = db();

$stmt = $pdo->prepare("
  SELECT s.id AS student_id, gp.guests_allowed, gp.stage
  FROM students s
  JOIN grad_process gp ON gp.student_id=s.id
  WHERE s.user_id=?
");
$stmt->execute([$u['id']]);
$info = $stmt->fetch();
if(!$info) exit('No student');

$student_id = (int)$info['student_id'];
$allowed    = (int)($info['guests_allowed'] ?? 2);
$stage      = (int)($info['stage'] ?? 0);

$st = $pdo->prepare("SELECT id, token, used_at FROM guest_tickets WHERE student_id=? ORDER BY id");
$st->execute([$student_id]);
$tickets = $st->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<script src="/graduation/assets/qrcode.min.js"></script>
<title>Билети за церемония</title></head><body>

<div class="topbar">
  <b>Билети за церемония</b>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/student/home.php">Назад</a>
</div>

<div class="container">
  <div class="card">
    <?php if($stage < 1): ?>
      <div class="card" style="background:#fff3cd;border-color:#ffe69c;color:#664d03;margin:0;">
        Заявлението ти все още не е потвърдено от администрацията.
        След потвърждение ще можеш да генерираш билети за гости.
      </div>
    <?php else: ?>

      <div class="small">Позволени билети: <b><?=h($allowed)?></b></div>

      <?php if($msg==='ok'): ?>
        <div class="badge stage-3" style="margin-top:10px;">✅ Билетът е генериран.</div>
      <?php elseif($msg==='limit'): ?>
        <div class="badge stage-1" style="margin-top:10px;">ℹ️ Достигнат е лимитът.</div>
      <?php elseif($msg==='not_confirmed'): ?>
        <div class="badge stage-1">⛔ Заявлението не е потвърдено.</div>
      <?php endif; ?>

      <p style="margin-top:10px">
        <a class="btn primary" href="/graduation/api/guest_tickets_generate.php">Генерирай билет</a>
      </p>

      <div class="small">
        Покажи QR кода на входа. Всеки билет се използва само веднъж.
      </div>

    <?php endif; ?>
  </div>

  <?php if(empty($tickets)): ?>
    <div class="card">
      <div class="small">Все още няма генерирани билети.</div>
    </div>
  <?php else: ?>
    <?php foreach($tickets as $i => $t): ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
          <div>
            <b>Билет #<?=h($i+1)?></b><br>
            <span class="small">Статус: <?= $t['used_at'] ? '✅ използван' : '— не е използван' ?></span>
          </div>
          <div class="qr" data-token="<?=h($t['token'])?>"></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
document.querySelectorAll('.qr').forEach(el=>{
  const token = el.dataset.token;
  const url = location.origin + '/graduation/api/guest_checkin.php?token=' + encodeURIComponent(token);
  new QRCode(el, { text: url, width: 110, height: 110 });
});
</script>

</body></html>

