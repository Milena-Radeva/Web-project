<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$id = (int)($_GET['id'] ?? 0);
if(!$id) exit('Missing id');

// Данни за студента + процес
$stmt = db()->prepare("
  SELECT
    s.id AS student_id,
    s.faculty_no, s.degree, s.program_name, s.group_code, s.phone,
    u.full_name, u.email,
    gp.stage, gp.registered_at, gp.confirmed_at, gp.ceremony_checked_in_at, gp.diploma_received_at,
    gp.gown_requested, gp.gown_taken, gp.gown_returned,
    gp.is_honors, gp.reward_badge, gp.reward_calendar,
    gp.notes
  FROM students s
  JOIN users u ON u.id=s.user_id
  JOIN grad_process gp ON gp.student_id=s.id
  WHERE s.id=?
");
$stmt->execute([$id]);
$st = $stmt->fetch();
if(!$st) exit('Student not found');

// Какви декларации (цитати) е използвал този студент (по желание)
$uses = db()->prepare("
  SELECT c.key_code, c.quote_text, COUNT(*) AS cnt
  FROM citation_uses cu
  JOIN citations c ON c.id=cu.citation_id
  JOIN users uu ON uu.id=cu.user_id
  WHERE uu.id = (SELECT user_id FROM students WHERE id=?)
  GROUP BY c.id
  ORDER BY cnt DESC, c.key_code
");
$uses->execute([$id]);
$cit_used = $uses->fetchAll();

// Безопасно рендериране на notes:
// - позволяваме само <cite> и <br> (и текст)
// - махаме всички други тагове
$notes = $st['notes'] ?? '';
$notes_safe = strip_tags($notes, '<cite><br>');
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Заявление – <?=h($st['full_name'])?></title></head><body>

<div class="topbar">
  <b>Заявление</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
</div>

<div class="container">

  <div class="card">
    <h2><?=h($st['full_name'])?></h2>
    <div class="badge <?=h(stage_class((int)$st['stage']))?>">
      Етап: <?=h(stage_label((int)$st['stage']))?>
    </div>

    <p class="small" style="margin-top:10px">
      Email: <b><?=h($st['email'])?></b> • ФН: <b><?=h($st['faculty_no'])?></b><br>
      Степен: <b><?=h($st['degree'])?></b> • Програма: <b><?=h($st['program_name'])?></b> • Група: <b><?=h($st['group_code'])?></b><br>
      Телефон: <b><?=h($st['phone'] ?? '')?></b>
    </p>
  </div>

  <div class="card">
    <h3>Заявление (текст от студента)</h3>

    <?php if(!$notes_safe): ?>
      <div class="small">Няма въведена бележка.</div>
    <?php else: ?>
      <!-- показваме го като HTML, за да се визуализират <cite> чиповете -->
      <div style="white-space:pre-wrap; line-height:1.5">
        <?= $notes_safe ?>
      </div>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid #eee;margin:12px 0">

    <div class="small">
      Тога заявена: <b><?= $st['gown_requested'] ? 'Да' : 'Не' ?></b> •
      Взел тога: <b><?= $st['gown_taken'] ? 'Да' : 'Не' ?></b> •
      Върнал тога: <b><?= $st['gown_returned'] ? 'Да' : 'Не' ?></b><br>
      Отличник: <b><?= $st['is_honors'] ? 'Да' : 'Не' ?></b> (значка: <?= $st['reward_badge']?'Да':'Не' ?>, календар: <?= $st['reward_calendar']?'Да':'Не' ?>)
    </div>
  </div>

  <?php if($cit_used): ?>
    <div class="card">
      <h3>Използвани декларации (цитати)</h3>
      <table class="table">
        <thead><tr><th>Код</th><th>Текст</th><th>Брой</th></tr></thead>
        <tbody>
        <?php foreach($cit_used as $c): ?>
          <tr>
            <td><cite class="cite-chip" data-cite-id="<?=h($c['key_code'])?>"><?=h($c['key_code'])?></cite></td>
            <td><?=h($c['quote_text'])?></td>
            <td><?=h($c['cnt'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>
</body></html>
