<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$pdo = db();

if(isset($_GET['export'])){
  $type = $_GET['export'];
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$type.'.csv"');
  $out = fopen('php://output','w');

  if($type==='students'){
    fputcsv($out, ['faculty_no','full_name','degree','group_code','stage','gown_requested','gown_taken','gown_returned','is_honors']);
    $rows = $pdo->query("SELECT s.faculty_no,u.full_name,s.degree,s.group_code,gp.stage,gp.gown_requested,gp.gown_taken,gp.gown_returned,gp.is_honors
                         FROM students s JOIN users u ON u.id=s.user_id JOIN grad_process gp ON gp.student_id=s.id")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
  } else {
    fputcsv($out, ['metric','value']);
    $stats = [
      ['registered', $pdo->query("SELECT COUNT(*) FROM grad_process")->fetchColumn()],
      ['gown_requested', $pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_requested=1")->fetchColumn()],
      ['checked_in', $pdo->query("SELECT COUNT(*) FROM grad_process WHERE ceremony_checked_in_at IS NOT NULL")->fetchColumn()],
      ['gown_returned', $pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_returned=1")->fetchColumn()],
      ['honors', $pdo->query("SELECT COUNT(*) FROM grad_process WHERE is_honors=1")->fetchColumn()],
    ];
    foreach($stats as $s) fputcsv($out, $s);
  }
  exit;
}

$purpose = $_GET['purpose'] ?? 'checkin';

if(isset($_POST['make_tokens'])){
  // генерира QR токени за всички студенти за дадена цел
  $students = $pdo->query("SELECT id FROM students")->fetchAll();
  foreach($students as $s){
    $token = bin2hex(random_bytes(16));
    $pdo->prepare("INSERT INTO qr_tokens(student_id, token, purpose) VALUES(?,?,?)")->execute([$s['id'],$token,$purpose]);
  }
  header("Location: /graduation/admin/reports.php?purpose=".$purpose."&ok=1");
  exit;
}

$list = $pdo->prepare("
  SELECT qt.token, qt.purpose, qt.used_at, u.full_name, s.faculty_no, s.degree, s.group_code
  FROM qr_tokens qt
  JOIN students s ON s.id=qt.student_id
  JOIN users u ON u.id=s.user_id
  WHERE qt.purpose=?
  ORDER BY s.group_code, u.full_name
");
$list->execute([$purpose]);
$rows = $list->fetchAll();

// Citation stats (dictionary)
$cit = $pdo->query("
  SELECT c.key_code, c.quote_text, COUNT(cu.id) AS uses
  FROM citations c
  LEFT JOIN citation_uses cu ON cu.citation_id=c.id
  GROUP BY c.id
  ORDER BY uses DESC, c.id DESC
")->fetchAll();

$who = $pdo->query("
  SELECT u.full_name, c.key_code, COUNT(*) AS cnt
  FROM citation_uses cu
  JOIN users u ON u.id=cu.user_id
  JOIN citations c ON c.id=cu.citation_id
  GROUP BY u.id, c.id
  ORDER BY cnt DESC
")->fetchAll();
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<script src="/graduation/assets/qrcode.min.js"></script>
<title>Отчети</title></head><body>
<div class="topbar">
  <b>Отчети и QR списъци</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
</div>
<div class="container">
  <div class="card">
    <h3>Генерирай QR списък</h3>
    <form method="get" style="display:flex;gap:10px;align-items:end">
      <div style="flex:1">
        <label>Цел</label>
        <select name="purpose">
          <option value="checkin" <?= $purpose==='checkin'?'selected':'' ?>>Check-in церемония</option>
          <option value="gown_take" <?= $purpose==='gown_take'?'selected':'' ?>>Получаване тога</option>
          <option value="gown_return" <?= $purpose==='gown_return'?'selected':'' ?>>Връщане тога</option>
          <option value="diploma" <?= $purpose==='diploma'?'selected':'' ?>>Получаване диплома</option>
        </select>
      </div>
      <button class="btn">Покажи</button>
    </form>
    <form method="post" style="margin-top:10px">
      <button name="make_tokens" class="btn primary" value="1">Генерирай токени за всички (еднократно)</button>
    </form>
  </div>

  <div class="card">
    <h3>Списък: <?=h($purpose)?></h3>
    <div class="small">Сканирането вика endpoint: <code>/graduation/api/checkin.php?token=...</code></div>
    <table class="table">
      <thead><tr><th>Студент</th><th>ФН</th><th>Група</th><th>QR</th><th>Статус</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=h($r['full_name'])?></td>
          <td><?=h($r['faculty_no'])?></td>
          <td><?=h($r['group_code'])?></td>
          <td><div class="qr" data-token="<?=h($r['token'])?>"></div></td>
          <td><?= $r['used_at'] ? '✅ използван' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Речник на цитатите + статистика</h3>
    <table class="table">
      <thead><tr><th>Код</th><th>Цитат</th><th>Ползвания</th></tr></thead>
      <tbody>
        <?php foreach($cit as $c): ?>
          <tr>
            <td><cite class="cite-chip" data-cite-id="<?=h($c['key_code'])?>"><?=h($c['key_code'])?></cite></td>
            <td><?=h($c['quote_text'])?></td>
            <td><?=h($c['uses'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h4>Кой е цитирал какво</h4>
    <table class="table">
      <thead><tr><th>Потребител</th><th>Цитат</th><th>Брой</th></tr></thead>
      <tbody>
        <?php foreach($who as $w): ?>
          <tr>
            <td><?=h($w['full_name'])?></td>
            <td><cite class="cite-chip" data-cite-id="<?=h($w['key_code'])?>"><?=h($w['key_code'])?></cite></td>
            <td><?=h($w['cnt'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('.qr').forEach(el=>{
  const token = el.dataset.token;
  // URL-то може да е на телефона/скенера:
  const url = location.origin + '/graduation/api/checkin.php?token=' + encodeURIComponent(token);
  new QRCode(el, { text: url, width: 96, height: 96 });
});
</script>
</body></html>
