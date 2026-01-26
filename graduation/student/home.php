<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();

/* Взимаме студентския QR */
$u = current_user();

/* Взимаме student_id */
$stmt = db()->prepare("SELECT id FROM students WHERE user_id=?");
$stmt->execute([$u['id']]);
$student_id = (int)$stmt->fetchColumn();

/* Проверяваме дали вече има QR */
$q = db()->prepare("SELECT id, token, used_at FROM student_qr WHERE student_id=?");
$q->execute([$student_id]);
$qr = $q->fetch();

/* Ако няма → създаваме автоматично */
if(!$qr){
  $token = bin2hex(random_bytes(16));
  db()->prepare("INSERT INTO student_qr(student_id, token) VALUES(?,?)")
    ->execute([$student_id, $token]);

  // презареждаме току-що създадения QR
  $q->execute([$student_id]);
  $qr = $q->fetch();
}


/* Данни за студента */
$stmt = db()->prepare("
  SELECT s.*, gp.stage, gp.gown_requested, gp.gown_taken, gp.gown_returned, 
         gp.is_honors, gp.reward_badge, gp.reward_calendar
  FROM students s
  JOIN grad_process gp ON gp.student_id=s.id
  WHERE s.user_id=?
");
$stmt->execute([$u['id']]);
$row = $stmt->fetch();
if(!$row){ exit('Няма студентски профил.'); }
?>
<!doctype html>
<html lang="bg">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<script src="/graduation/assets/qrcode.min.js"></script>
<title>Студент</title>
</head>
<body>

<div class="topbar">
  <b>Студентски панел</b>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/api/auth_logout.php">Изход</a>
</div>

<div class="container">

  <!-- QR КОД НА СТУДЕНТА -->
  <div class="card">
    <h3>Моят входен QR код</h3>
      <div class="small">
        Статус: <?= $qr['used_at'] ? '✅ Влязъл в залата' : '— Очаква се сканиране' ?>
      </div>
      <div id="studentQr" style="margin-top:10px;"></div>
  </div>

  <!-- ОСНОВНИ ДАННИ -->
  <div class="card">
    <h2><?=h($row['full_name'] ?? $u['full_name'])?></h2>
    <div class="badge <?=h(stage_class((int)$row['stage']))?>">
      Етап: <?=h(stage_label((int)$row['stage']))?>
    </div>
    <p class="small">
      ФН: <b><?=h($row['faculty_no'])?></b> • 
      Степен: <b><?=h($row['degree'])?></b> • 
      Група: <b><?=h($row['group_code'])?></b>
    </p>
    <p>
      <a class="btn primary" href="/graduation/student/register.php">Заявка за дипломиране</a>
      <a class="btn" href="/graduation/student/citations.php">Цитати</a>
      <a class="btn" href="/graduation/student/guests.php">Билети за церемония</a>
    </p>
  </div>

  <!-- ТОГИ И ОТЛИЧИЯ -->
  <div class="card">
    <h3>Тоги и отличия</h3>
    <ul>
      <li>Заявил тога: <b><?= $row['gown_requested'] ? 'Да' : 'Не' ?></b></li>
      <li>Получил тога: <b><?= $row['gown_taken'] ? 'Да' : 'Не' ?></b></li>
      <li>Върнал тога: <b><?= $row['gown_returned'] ? 'Да' : 'Не' ?></b></li>
      <li>
        Отличник: <b><?= $row['is_honors'] ? 'Да' : 'Не' ?></b>
        (значка: <?= $row['reward_badge']?'Да':'Не' ?>,
         календар: <?= $row['reward_calendar']?'Да':'Не' ?>)
      </li>
    </ul>
  </div>

</div>

<?php if($qr): ?>
<script>
new QRCode(document.getElementById("studentQr"), {
  text: location.origin + "/graduation/api/student_checkin.php?token=<?=h($qr['token'])?>",
  width: 140,
  height: 140
});
</script>
<?php endif; ?>

</body>
</html>

