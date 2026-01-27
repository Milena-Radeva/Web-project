<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Импорт/Експорт</title></head><body>
<div class="topbar">
  <b>Импорт/Експорт</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
  <a class="btn" style="margin-left:auto" href="/graduation/api/auth_logout.php">Изход</a>
</div>
<div class="container">
  <div class="card">
    <?php if(isset($_SESSION['flash'])): ?>
      <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="card" style="
          background:<?= $f['type']=='success' ? '#e8f7ee' : '#fdecea' ?>;
          border-color:<?= $f['type']=='success' ? '#6b8f71' : '#d9534f' ?>;
          color:<?= $f['type']=='success' ? '#1f5d3a' : '#842029' ?>;
      ">
        <?=h($f['msg'])?>
      </div>
    <?php endif; ?>

    <h3>Импорт студенти (CSV)</h3>
    <form method="post" action="/graduation/api/import_students.php" enctype="multipart/form-data">
      <input type="file" name="csv" accept=".csv" required>
      <p><button class="btn primary">Импорт</button></p>
    </form>
  </div>

  <div class="card">
    <h3>Експорт</h3>
    <p>
      <a class="btn primary" href="/graduation/admin/reports.php?export=students">Експорт студенти CSV</a>
    </p>
  </div>
</div>
</body></html>
