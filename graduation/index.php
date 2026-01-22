<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/db.php';
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<title><?=h(APP_NAME)?></title></head><body>
<div class="topbar"><b><?=h(APP_NAME)?></b></div>
<div class="container">
  <div class="card">
    <h2>Вход</h2>
    <div class="small">Тест акаунти: admin@uni.test / admin123, stud1@uni.test / student123</div>
    <form method="post" action="/graduation/api/auth_login.php">
      <div class="row">
        <div><label>Email</label><input name="email" required></div>
        <div><label>Парола</label><input type="password" name="pass" required></div>
      </div>
      <p><button class="btn primary">Вход</button></p>
    </form>
  </div>
</div>
</body></html>
