<?php
require_once __DIR__.'/../inc/auth.php';
require_role(['admin','superadmin']);
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title>Импорт/Експорт</title></head><body>
<div class="topbar">
  <b>Импорт/Експорт</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
</div>
<div class="container">
  <div class="card">
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
