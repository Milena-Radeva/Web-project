<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$u = current_user();
$rows = db()->query("
  SELECT s.faculty_no, u.full_name, s.degree, s.group_code, gp.stage,
         gp.gown_requested, gp.gown_taken, gp.gown_returned, gp.is_honors
  FROM students s
  JOIN users u ON u.id=s.user_id
  JOIN grad_process gp ON gp.student_id=s.id
  ORDER BY s.group_code, s.degree, u.full_name
")->fetchAll();
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Админ</title></head><body>
<div class="topbar">
  <b>Администрация</b>
  <a class="btn" href="/graduation/admin/import_export.php">Импорт/Експорт</a>
  <a class="btn" href="/graduation/admin/responsibilities.php">Отговорници</a>
  <a class="btn" href="/graduation/admin/citations.php">Цитати</a>
  <a class="btn" href="/graduation/admin/reports.php">Отчети</a>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/api/auth_logout.php">Изход</a>
</div>
<div class="container">
  <div class="card">
    <h2>Студенти (етапи с цветово кодиране)</h2>
    <table class="table">
      <thead><tr>
        <th>ФН</th><th>Име</th><th>Степен</th><th>Група</th><th>Етап</th><th>Тога</th><th>Отличник</th>
      </tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=h($r['faculty_no'])?></td>
          <td><?=h($r['full_name'])?></td>
          <td><?=h($r['degree'])?></td>
          <td><?=h($r['group_code'])?></td>
          <td><span class="badge <?=h(stage_class((int)$r['stage']))?>"><?=h(stage_label((int)$r['stage']))?></span></td>
          <td class="small">
            заяв: <?= $r['gown_requested']?'Да':'Не' ?> /
            взел: <?= $r['gown_taken']?'Да':'Не' ?> /
            върнал: <?= $r['gown_returned']?'Да':'Не' ?>
          </td>
          <td><?= $r['is_honors']?'✅':'—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
