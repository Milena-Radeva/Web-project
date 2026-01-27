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
         gp.is_honors, s.photo
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

  <div class="card" style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:16px; align-items:start;">

    <!-- ЛЯВО: профил -->
    <div style="display:flex; gap:16px; align-items:flex-start;">
      <!-- Снимка -->
      <div style="width:120px; text-align:center;">
        <?php if(!empty($row['photo'])): ?>
          <img src="/graduation/uploads/<?=h($row['photo'])?>"
               alt="Снимка"
               style="width:110px;height:150px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">
        <?php else: ?>
          <div style="width:110px;height:150px;display:flex;align-items:center;justify-content:center;
                      border:1px dashed #ccc;border-radius:12px;color:#888;font-size:12px;">
            Няма снимка
          </div>
        <?php endif; ?>

        <div style="margin-top:10px;">
          <a class="btn" href="/graduation/student/photo_upload.php">
            <?= !empty($row['photo']) ? 'Смени снимка' : 'Качи снимка' ?>
          </a>
        </div>
      </div>

      <!-- Инфо -->
      <div style="flex:1;">
        <h2 style="margin:0 0 6px 0;"><?=h($u['full_name'])?></h2>

        <div class="badge <?=h(stage_class((int)$row['stage']))?>">
          Етап: <?=h(stage_label((int)$row['stage']))?>
        </div>

        <div class="small" style="margin-top:10px; line-height:1.6">
          ФН: <b><?=h($row['faculty_no'])?></b><br>
          Степен: <b><?=h($row['degree'])?></b><br>
          Група: <b><?=h($row['group_code'])?></b><br>
          Специалност: <b><?=h($row['program_name'])?></b>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
          <a class="btn primary" href="/graduation/student/register.php">Заявление</a>
          <a class="btn" href="/graduation/student/guests.php">Билети</a>
        </div>
      </div>
    </div>

    <!-- ДЯСНО: QR -->
    <div style="border:1px solid #eee; border-radius:12px; padding:12px;">
      <div style="font-weight:700; margin-bottom:8px;">Входен QR</div>

      <?php if(empty($qr)): ?>
        <div class="small">Няма QR код.</div>
      <?php else: ?>
        <div class="small" style="margin-bottom:10px;">
          Статус: <?= $qr['used_at'] ? '✅ Влязъл в залата' : '— Очаква се сканиране' ?>
        </div>
        <div id="studentQr" style="display:flex;justify-content:center;"></div>
        <div class="small" style="text-align:center; margin-top:8px; color:#666;">
          Покажи кода на входа
        </div>
      <?php endif; ?>
    </div>

    <!-- ДОЛУ: тоги/отличия -->
    <div style="grid-column:1/-1; border-top:1px solid #eee; padding-top:12px;">
      <div style="font-weight:700; margin-bottom:8px;">Тоги и отличия</div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <span class="badge"><?= $row['gown_requested'] ? '✅ Тога заявена' : '❌Тога не е заявена' ?></span>
        <span class="badge"><?= $row['gown_taken'] ? '✅ Тога взета' : '❌ Тога не е взета' ?></span>
        <span class="badge"><?= $row['gown_returned'] ? '✅ Тога върната' : '❌Тога не е върната' ?></span>
        <span class="badge"><?= $row['is_honors'] ? '🏅 Отличник' : '❌Не си отличник' ?></span>
      </div>
    </div>

  </div>
</div>

<script src="/graduation/assets/qrcode.min.js"></script>
<script>
<?php if(!empty($qr)): ?>
  new QRCode(document.getElementById("studentQr"), {
    text: location.origin + "/graduation/api/student_checkin.php?token=<?=h($qr['token'])?>",
    width: 160,
    height: 160
  });
<?php endif; ?>
</script>
</body>
</html>

