<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$u = current_user();
$rows = db()->query("
  SELECT
    s.id AS student_id,
    s.faculty_no,
    u.full_name,
    s.degree,
    s.group_code,
    gp.stage,
    gp.gown_requested, gp.gown_taken, gp.gown_returned,
    gp.is_honors,
    COUNT(gt.id) AS tickets_count,
    s.gpa,
    gp.agree_personal_data,
    gp.declare_correct
  FROM students s
  JOIN users u ON u.id=s.user_id
  JOIN grad_process gp ON gp.student_id=s.id
  LEFT JOIN guest_tickets gt ON gt.student_id=s.id
  GROUP BY s.id
  ORDER BY s.group_code, u.full_name
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
  <a class="btn" href="/graduation/admin/reports.php">Отчети</a>
  <a class="btn" href="/graduation/admin/scanner.php">Скенер (вход)</a>
  <?php if (current_user()['role'] === 'superadmin'): ?>
    <a class="btn" href="/graduation/admin/admins.php">Администратори</a>
  <?php endif; ?>

  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/api/auth_logout.php">Изход</a>
</div>
<div class="container">
  <div class="card">
    <h2>Студенти</h2>
   <table class="table">
  <thead>
    <tr>
      <th>ФН</th>
      <th class='center'>Име</th>
      <th class='center'>Степен</th>
      <th class='center'>Група</th>
      <th class='center'>Етап</th>
      <th class='center'>Заявление</th>
      <th class='center'>Успех</th>
      <th class='center'>Отличник</th>
      <th class='center'>Тога</th>
      <th class='center'>Билети</th>
      <th class='center'>Действия</th>
    </tr>
  </thead>

  <tbody>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?=h($r['faculty_no'])?></td>
      <td><?=h($r['full_name'])?></td>
      <td><?=h($r['degree'])?></td>
      <td><?=h($r['group_code'])?></td>

      <td class='center'>
        <span class="badge <?=h(stage_class((int)$r['stage']))?>">
          <?=h(stage_label((int)$r['stage']))?>
        </span>
      </td>

      <td class="center">
        <?php if(
            $r['agree_personal_data'] == 1 &&
            $r['declare_correct'] == 1
        ): ?>
          <a class="btn" href="/graduation/admin/student_view.php?id=<?=h($r['student_id'])?>">
            Преглед
          </a>
        <?php else: ?>
          <span class="small" style="color:#aaa;">Непълно заявление</span>
        <?php endif; ?>
      </td>
      <td class="center"><?= $r['gpa'] !== null ? h($r['gpa']) : '—' ?></td>
      <td class="center"><?= $r['is_honors'] ? '✅' : '—' ?></td>

      <td class="small">
        заявил: <?= $r['gown_requested']?'Да':'Не' ?> /
        взел: <?= $r['gown_taken']?'Да':'Не' ?> /
        върнал: <?= $r['gown_returned']?'Да':'Не' ?>
        <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
         <?php if($r['gown_requested'] && !$r['gown_taken']): ?>
            <form method="post" action="/graduation/api/update_gown.php" style="margin:0">
              <input type="hidden" name="student_id" value="<?=h($r['student_id'])?>">
              <button class="btn" name="action" value="gown_taken"
                      onclick="return confirm('Потвърждаваш ли, че студентът е взел тогата?')">
                Тога взета
              </button>
            </form>
          <?php endif; ?>

          <?php if($r['gown_taken'] && !$r['gown_returned']): ?>
            <form method="post" action="/graduation/api/update_gown.php" style="margin:0">
              <input type="hidden" name="student_id" value="<?=h($r['student_id'])?>">
              <button class="btn" name="action" value="gown_returned"
                      onclick="return confirm('Потвърждаваш ли, че студентът е върнал тогата?')">
                Тога върната
              </button>
            </form>
          <?php endif; ?>
        </div>
      </td>

      <td class="center"><?= (int)$r['tickets_count'] ?></td>

      <td class="center">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <?php if((int)$r['stage'] === 0): ?>
            <form method="post" action="/graduation/api/admin_update_student.php" style="margin:0">
              <input type="hidden" name="student_id" value="<?=h($r['student_id'])?>">
              <button class="btn primary" name="action" value="confirm">Потвърди</button>
            </form>
          <?php endif; ?>

          <?php if((int)$r['stage'] === 2): ?>
            <form method="post" action="/graduation/api/admin_update_student.php" style="margin:0">
              <input type="hidden" name="student_id" value="<?=h($r['student_id'])?>">
              <button class="btn primary" name="action" value="finish">Завърши</button>
            </form>
          <?php endif; ?>
          <?php if((int)$r['stage'] === 3): ?>
            <form method="post" action="/graduation/api/delete_student.php" style="margin:0"
                  onsubmit="return confirm('Сигурна ли си, че искаш да изтриеш този студент? Това действие е необратимо.');">
              <input type="hidden" name="student_id" value="<?=h($r['student_id'])?>">
              <button class="btn" style="background:#fee;border-color:#f99">Изтрий</button>
            </form>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
  </div>
</div>
</body></html>
