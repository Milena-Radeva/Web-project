<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();

$s = db()->prepare("
  SELECT 
    s.id,
    s.program_name,
    gp.stage,
    gp.notes,
    gp.application_submitted,
    gp.application_submitted_at,
    gp.gown_requested,
    gp.agree_personal_data,
    gp.agree_public_name,
    gp.agree_photos,
    gp.declare_correct
  FROM students s 
  JOIN grad_process gp ON gp.student_id=s.id 
  WHERE s.user_id=?
");
$s->execute([$u['id']]);
$st = $s->fetch();
if(!$st) exit('Няма студентски профил.');

if($_SERVER['REQUEST_METHOD']==='POST'){

  if (!empty($st['application_submitted'])) {
    exit("Заявлението вече е подадено и не може да се редактира.");
  }

  $notes = $_POST['notes'] ?? '';

  $gown_requested      = isset($_POST['gown_requested']) ? 1 : 0;
  $agree_personal_data = isset($_POST['agree_personal_data']) ? 1 : 0;
  $agree_public_name   = isset($_POST['agree_public_name']) ? 1 : 0;
  $agree_photos        = isset($_POST['agree_photos']) ? 1 : 0;
  $declare_correct     = isset($_POST['declare_correct']) ? 1 : 0;

  // задължителни чекбоксове (server-side)
  if (!$agree_personal_data || !$declare_correct) {
    exit("Трябва да приемеш задължителните декларации (маркирани със *).");
  }

  db()->prepare("
    UPDATE grad_process
    SET
      notes=?,
      gown_requested=?,
      agree_personal_data=?,
      agree_public_name=?,
      agree_photos=?,
      declare_correct=?,
      application_submitted=1,
      application_submitted_at=NOW()
    WHERE student_id=?
  ")->execute([
    $notes,
    $gown_requested,
    $agree_personal_data,
    $agree_public_name,
    $agree_photos,
    $declare_correct,
    $st['id']
  ]);

  header("Location: /graduation/student/home.php?submitted=1");
  exit;
}
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Заявка</title></head><body>

<div class="topbar">
  <b>Заявка за дипломиране</b>
  <span style="margin-left:auto"><?=h($u['full_name'])?></span>
  <a class="btn" href="/graduation/student/home.php">Назад</a>
</div>

<div class="container">
  <div class="card">

    <?php if (!empty($st['application_submitted'])): ?>
      <div class="card" style="background:#e8f7ee;border-color:#6b8f71;color:#1f5d3a">
        ✅ Заявлението е подадено успешно на
        <b><?=h(date('d.m.Y H:i', strtotime($st['application_submitted_at'] ?? 'now')))?></b>.
        Не може да бъде редактирано.
      </div>
    <?php else: ?>

      <form method="post">
        <label>Заявление за допускане до церемония за дипломиране</label>
        <textarea name="notes" rows="6"><?=h($st['notes'] ?? '')?></textarea>

        <div style="margin:12px 0;">
          <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="gown_requested" value="1" <?= !empty($st['gown_requested'])?'checked':'' ?>>
            Заявявам тога за церемонията
          </label>
        </div>

        <div style="margin:12px 0;">
          <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="agree_personal_data" value="1" required <?= !empty($st['agree_personal_data'])?'checked':'' ?>>
            *Съгласен/съгласна съм личните ми данни да бъдат обработвани за целите на дипломирането
          </label>
        </div>

        <div style="margin:12px 0;">
          <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="agree_public_name" value="1" <?= !empty($st['agree_public_name'])?'checked':'' ?>>
            Съгласен/съгласна съм името ми да бъде публикувано в официалните списъци
          </label>
        </div>

        <div style="margin:12px 0;">
          <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="agree_photos" value="1" <?= !empty($st['agree_photos'])?'checked':'' ?>>
            Съгласен/съгласна съм да бъда заснеман/а по време на церемонията
          </label>
        </div>

        <div style="margin:12px 0;">
          <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="declare_correct" value="1" required <?= !empty($st['declare_correct'])?'checked':'' ?>>
            *Декларирам, че предоставените от мен данни са верни
          </label>
        </div>

        <p><button class="btn primary">Изпрати заявление</button></p>
      </form>

    <?php endif; ?>

  </div>
</div>

</body></html>
