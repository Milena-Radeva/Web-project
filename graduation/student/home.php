<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();
$stmt = db()->prepare("
  SELECT s.*, gp.stage, gp.gown_requested, gp.gown_taken, gp.gown_returned, gp.is_honors, gp.reward_badge, gp.reward_calendar
  FROM students s
  JOIN grad_process gp ON gp.student_id=s.id
  WHERE s.user_id=?
");
$stmt->execute([$u['id']]);
$row = $stmt->fetch();
if(!$row){ exit('Няма студентски профил.'); }
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Студент</title></head><body>
<div class="topbar">
  <b>Студентски панел</b>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/api/auth_logout.php">Изход</a>
</div>
<div class="container">
  <div class="card">
    <h2><?=h($row['full_name'] ?? $u['full_name'])?></h2>
    <div class="badge <?=h(stage_class((int)$row['stage']))?>">
      Етап: <?=h(stage_label((int)$row['stage']))?>
    </div>
    <p class="small">
      ФН: <b><?=h($row['faculty_no'])?></b> • Степен: <b><?=h($row['degree'])?></b> • Група: <b><?=h($row['group_code'])?></b>
    </p>
    <p>
      <a class="btn primary" href="/graduation/student/register.php">Заявка за дипломиране</a>
      <a class="btn" href="/graduation/student/citations.php">Цитати</a>
    </p>
  </div>

  <div class="card">
    <h3>Тоги и отличия</h3>
    <ul>
      <li>Заявил тога: <b><?= $row['gown_requested'] ? 'Да' : 'Не' ?></b></li>
      <li>Получил тога: <b><?= $row['gown_taken'] ? 'Да' : 'Не' ?></b></li>
      <li>Върнал тога: <b><?= $row['gown_returned'] ? 'Да' : 'Не' ?></b></li>
      <li>Отличник: <b><?= $row['is_honors'] ? 'Да' : 'Не' ?></b> (значка: <?= $row['reward_badge']?'Да':'Не' ?>, календар: <?= $row['reward_calendar']?'Да':'Не' ?>)</li>
    </ul>
  </div>
</div>
</body></html>
