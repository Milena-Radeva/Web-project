<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$id = (int)($_GET['id'] ?? 0);
if(!$id) { exit('Missing id'); }

$stmt = db()->prepare("
  SELECT
    s.id AS student_id,
    s.faculty_no, s.degree, s.program_name, s.group_code, s.phone, s.photo,
    u.full_name, u.email,
    gp.stage, gp.registered_at, gp.confirmed_at, gp.ceremony_checked_in_at, gp.diploma_received_at,
    gp.gown_requested, gp.gown_taken, gp.gown_returned,
    gp.is_honors,
    gp.notes,
    gp.agree_personal_data, gp.agree_public_name, gp.agree_photos, gp.declare_correct
  FROM students s
  JOIN users u ON u.id=s.user_id
  JOIN grad_process gp ON gp.student_id=s.id
  WHERE s.id=?
");
$stmt->execute([$id]);
$st = $stmt->fetch();

if(!$st) { exit('Student not found'); }
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/graduation/assets/styles.css">
  <title>Заявление – <?=h($st['full_name'])?></title>
</head>
<body>

<div class="topbar">
  <b>Заявление</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
  <a class="btn" style="margin-left:auto" href="/graduation/api/auth_logout.php">Изход</a>
</div>

<div class="container">
  <div class="card" style="display:flex; gap:20px; align-items:center;">
    <div style="flex:0 0 140px; text-align:center;">
      <?php if(!empty($st['photo'])): ?>
        <img src="/graduation/uploads/<?=h($st['photo'])?>"
            alt="Снимка за диплома"
            style="width:120px;height:160px;object-fit:cover;
                    border-radius:12px;border:1px solid #ddd;">
      <?php else: ?>
        <div style="width:120px;height:160px;
                    display:flex;align-items:center;justify-content:center;
                    border:1px dashed #ccc;border-radius:12px;
                    color:#999;font-size:13px;">
          Няма снимка
        </div>
      <?php endif; ?>
    </div>

    <div style="flex:1;">
      <h2 style="margin:0 0 6px 0;"><?=h($st['full_name'])?></h2>

      <div class="badge <?=h(stage_class((int)$st['stage']))?>" style="margin-bottom:8px;">
        Етап: <?=h(stage_label((int)$st['stage']))?>
      </div>

      <p class="small">
        Email: <b><?=h($st['email'])?></b> <br> ФН: <b><?=h($st['faculty_no'])?></b><br>
        Степен: <b><?=h($st['degree'])?></b><br>
        Програма: <b><?=h($st['program_name'])?></b><br>
        Група: <b><?=h($st['group_code'])?></b><br>
        Телефон: <b><?=h($st['phone'] ?? '')?></b>
      </p>
    </div>
  </div>

</div>
<div class="container">
  <div class="card">
    <h3>Заявление (текст от студента)</h3>

    <?php if(empty($st['notes'])): ?>
      <div class="small">Няма въведен текст.</div>
    <?php else: ?>
      <div style="white-space:pre-wrap; line-height:1.5">
        <?= h($st['notes']) ?>
      </div>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid #eee;margin:12px 0">

  </div>
</div>
<div class="container">
  <div class="card">
    <h3>Декларации / отметки</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Декларация</th>
          <th class='center'>Статус</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Заявена тога</td>
          <td class='center'><?= $st['gown_requested'] ? '✅' : '—' ?></td>
        </tr>
        <tr>
          <td>Съгласие за обработка на лични данни</td>
          <td class='center'><?= $st['agree_personal_data'] ? '✅' : '—' ?></td>
        </tr>
        <tr>
          <td>Съгласие за публикуване на име в списъци</td>
          <td class='center'><?= $st['agree_public_name'] ? '✅' : '—' ?></td>
        </tr>
        <tr>
          <td>Съгласие за снимки и видео</td>
          <td class='center'><?= $st['agree_photos'] ? '✅' : '—' ?></td>
        </tr>
        <tr>
          <td>Декларация за коректност на данните</td>
          <td class='center'><?= $st['declare_correct'] ? '✅' : '—' ?></td>
        </tr>
      </tbody>
    </table>
    <hr style="border:none;border-top:1px solid #eee;margin:12px 0">
 </div>
</div>

<div class="container">
    <div class="card">
      Тога заявена: <b><?= $st['gown_requested'] ? 'Да ✅' : 'Не ❌' ?></b> <br>
      Взел тога: <b><?= $st['gown_taken'] ? 'Да ✅' : 'Не ❌' ?></b> <br>
      Върнал тога: <b><?= $st['gown_returned'] ? 'Да ✅' : 'Не ❌' ?></b><br>
      Отличник: <b><?= $st['is_honors'] ? 'Да ✅' : 'Не ❌' ?></b>
      <hr style="border:none;border-top:1px solid #eee;margin:12px 0">
    </div>

    <hr style="border:none;border-top:1px solid #eee;margin:12px 0">

    <div class="small">
      Регистрация: <b><?=h($st['registered_at'])?></b><br>
      Потвърден: <b><?=h($st['confirmed_at'] ?? '—')?></b><br>
      Check-in: <b><?=h($st['ceremony_checked_in_at'] ?? '—')?></b><br>
      Получена диплома: <b><?=h($st['diploma_received_at'] ?? '—')?></b>
    </div>
  </div>
</div>

</body>
</html>
