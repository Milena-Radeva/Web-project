<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['student']);

$u = current_user();

$stmt = db()->prepare("
  SELECT s.id, s.photo
  FROM students s
  WHERE s.user_id=?
");
$stmt->execute([$u['id']]);
$s = $stmt->fetch();
if(!$s) exit('Няма студентски профил.');

$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK){
    $err = 'Грешка при качване.';
  } else {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png'];

    if(!in_array($ext, $allowed, true)){
      $err = 'Позволени са само JPG/PNG.';
    } else {
      // ограничение размер (примерно 3MB)
      if($_FILES['photo']['size'] > 3*1024*1024){
        $err = 'Файлът е твърде голям (макс 3MB).';
      } else {
        $photoName = 'student_'.$s['id'].'_'.time().'.'.$ext;
        $dest = __DIR__.'/../uploads/'.$photoName;

        if(!is_dir(__DIR__.'/../uploads')) {
          mkdir(__DIR__.'/../uploads', 0777, true);
        }

        if(!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)){
          $err = 'Неуспешно записване на файла.';
        } else {
          // запис в DB
          db()->prepare("UPDATE students SET photo=? WHERE id=?")
            ->execute([$photoName, $s['id']]);

          header("Location: /graduation/student/home.php");
          exit;
        }
      }
    }
  }
}
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Снимка</title></head><body>

<div class="topbar">
  <b>Качи снимка за диплома</b>
  <a class="btn" href="/graduation/student/home.php">Назад</a>
</div>

<div class="container">
  <div class="card">
    <?php if($err): ?>
      <div style="color:#b00020"><b>❌ <?=h($err)?></b></div>
      <hr style="border:none;border-top:1px solid #eee;margin:12px 0">
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label>Избери снимка (JPG/PNG, до 3MB)</label>
      <input type="file" name="photo" accept="image/*" required>
      <p class="small">Препоръка: 3×4 см, бял фон.</p>
      <p><button class="btn primary">Качи</button></p>
    </form>
  </div>
</div>

</body></html>
