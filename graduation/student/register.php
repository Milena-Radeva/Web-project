<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();
$s = db()->prepare("SELECT s.id, s.program_name, gp.stage, gp.notes FROM students s JOIN grad_process gp ON gp.student_id=s.id WHERE s.user_id=?");
$s->execute([$u['id']]);
$st = $s->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $photoName = null;

  if(isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK){
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoName = 'student_'.$st['id'].'_'.time().'.'.$ext;
    move_uploaded_file(
      $_FILES['photo']['tmp_name'], 
      __DIR__.'/../uploads/'.$photoName
    );
  

    // записваме снимката в таблицата students
    db()->prepare("UPDATE students SET photo=? WHERE id=?")
       ->execute([$photoName, $st['id']]);
  }

  $notes = $_POST['notes'] ?? '';
$gown_requested      = isset($_POST['gown_requested']) ? 1 : 0;
$agree_personal_data = isset($_POST['agree_personal_data']) ? 1 : 0;
$agree_public_name   = isset($_POST['agree_public_name']) ? 1 : 0;
$agree_photos        = isset($_POST['agree_photos']) ? 1 : 0;
$declare_correct     = isset($_POST['declare_correct']) ? 1 : 0;
  db()->prepare("
  UPDATE grad_process
  SET
    notes=?,
    gown_requested=?,
    agree_personal_data=?,
    agree_public_name=?,
    agree_photos=?,
    declare_correct=?
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

  header("Location: /graduation/student/home.php");
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
    <form method="post" enctype="multipart/form-data">
      <label>Заявление за допускане до церемония за дипломиране</label>
      <textarea name="notes" rows="6"><?=h($st['notes'] ?? '')?></textarea>
      <div style="margin:12px 0;">
        <label>Снимка за диплома (JPG/PNG)</label>
        <input type="file" name="photo" accept="image/*">
        <div class="small">Препоръчителен размер: 3x4 cm, бял фон</div>
      </div>
      <div style="margin:12px 0;">
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="gown_requested" value="1">
          Заявявам тога за церемонията
        </label>
      </div>

      <div style="margin:12px 0;">
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="agree_personal_data" value="1" required>
          *Съгласен/съгласна съм личните ми данни да бъдат обработвани за целите на дипломирането
        </label>
      </div>

      <div style="margin:12px 0;">
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="agree_public_name" value="1">
          Съгласен/съгласна съм името ми да бъде публикувано в официалните списъци
        </label>
      </div>

      <div style="margin:12px 0;">
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="agree_photos" value="1">
          Съгласен/съгласна съм да бъда заснеман/а по време на церемонията
        </label>
      </div>

      <div style="margin:12px 0;">
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="declare_correct" value="1" required>
          *Декларирам, че предоставените от мен данни са верни
        </label>
      </div>

      <p><button class="btn primary">Запази</button></p>
    </form>
  </div>

</div>
</body></html>
