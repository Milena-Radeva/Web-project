<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);

$pdo = db();

/* =========================
   EXPORTS (CSV)
========================= */
if(isset($_GET['export'])){
  $type = $_GET['export'];

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$type.'.csv"');
  $out = fopen('php://output','w');

  /*if($type==='students'){
    fputcsv($out, ['faculty_no','full_name','email','degree','group_code','stage','gown_requested','gown_taken','gown_returned','is_honors']);
    $rows = $pdo->query("
      SELECT s.faculty_no, u.full_name, u.email, s.degree, s.group_code,
             gp.stage, gp.gown_requested, gp.gown_taken, gp.gown_returned, gp.is_honors
      FROM students s
      JOIN users u ON u.id=s.user_id
      JOIN grad_process gp ON gp.student_id=s.id
      ORDER BY s.group_code, u.full_name
    ")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
    exit;
  }*/

  if($type==='gpa'){
    fputcsv($out, ['faculty_no','full_name','degree','group_code','gpa','stage','is_honors']);
    $rows = $pdo->query("
      SELECT s.faculty_no, u.full_name, s.degree, s.group_code, s.gpa, gp.stage, gp.is_honors
      FROM students s
      JOIN users u ON u.id=s.user_id
      JOIN grad_process gp ON gp.student_id=s.id
      ORDER BY s.gpa DESC, u.full_name
    ")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
    exit;
  }

  if($type==='declarations'){
    fputcsv($out, ['faculty_no','full_name','group_code','gown_requested','agree_personal_data','agree_public_name','agree_photos','declare_correct']);
    $rows = $pdo->query("
      SELECT s.faculty_no, u.full_name, s.group_code,
             gp.gown_requested, gp.agree_personal_data, gp.agree_public_name, gp.agree_photos, gp.declare_correct
      FROM students s
      JOIN users u ON u.id=s.user_id
      JOIN grad_process gp ON gp.student_id=s.id
      ORDER BY s.group_code, u.full_name
    ")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
    exit;
  }

  if($type==='tickets'){
    fputcsv($out, ['faculty_no','full_name','group_code','tickets_total','tickets_used']);
    $rows = $pdo->query("
      SELECT
        s.faculty_no,
        u.full_name,
        s.group_code,
        COUNT(gt.id) AS tickets_total,
        SUM(gt.used_at IS NOT NULL) AS tickets_used
      FROM students s
      JOIN users u ON u.id=s.user_id
      LEFT JOIN guest_tickets gt ON gt.student_id=s.id
      GROUP BY s.id
      ORDER BY s.group_code, u.full_name
    ")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
    exit;
  }

  // stats (общи метрики)
  /*if($type==='stats'){
    fputcsv($out, ['metric','value']);

    $registered = (int)$pdo->query("SELECT COUNT(*) FROM grad_process")->fetchColumn();
    $stage0 = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=0")->fetchColumn();
    $stage1 = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=1")->fetchColumn();
    $stage2 = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=2")->fetchColumn();
    $stage3 = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=3")->fetchColumn();

    $gownReq = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_requested=1")->fetchColumn();
    $gownTaken = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_taken=1")->fetchColumn();
    $gownReturned = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_returned=1")->fetchColumn();

    $honors = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE is_honors=1")->fetchColumn();

    $missingPhoto = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE photo IS NULL OR photo=''")->fetchColumn();

    $totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM guest_tickets")->fetchColumn();
    $usedTickets  = (int)$pdo->query("SELECT COUNT(*) FROM guest_tickets WHERE used_at IS NOT NULL")->fetchColumn();

    $decl = $pdo->query("
      SELECT
        SUM(agree_personal_data=1) AS agree_personal_data_yes,
        SUM(agree_public_name=1)   AS agree_public_name_yes,
        SUM(agree_photos=1)        AS agree_photos_yes,
        SUM(declare_correct=1)     AS declare_correct_yes,
        COUNT(*) AS total
      FROM grad_process
    ")->fetch();

    $pairs = [
      ['registered_total', $registered],
      ['stage_0', $stage0],
      ['stage_1', $stage1],
      ['stage_2', $stage2],
      ['stage_3', $stage3],
      ['gown_requested', $gownReq],
      ['gown_taken', $gownTaken],
      ['gown_returned', $gownReturned],
      ['honors', $honors],
      ['missing_photo', $missingPhoto],
      ['tickets_total', $totalTickets],
      ['tickets_used', $usedTickets],
      ['agree_personal_data_yes', (int)$decl['agree_personal_data_yes']],
      ['agree_public_name_yes', (int)$decl['agree_public_name_yes']],
      ['agree_photos_yes', (int)$decl['agree_photos_yes']],
      ['declare_correct_yes', (int)$decl['declare_correct_yes']],
      ['declarations_total_rows', (int)$decl['total']],
    ];

    foreach($pairs as $p) fputcsv($out, $p);
    exit;
  }*/

  // unknown export
  fputcsv($out, ['error','unknown export type']);
  exit;
}

/* =========================
   DATA FOR PAGE (HTML)
========================= */

// Студенти по успех
$byGpa = $pdo->query("
  SELECT
    s.id AS student_id,
    s.faculty_no,
    u.full_name,
    s.degree,
    s.group_code,
    s.gpa,
    gp.stage,
    gp.is_honors
  FROM students s
  JOIN users u ON u.id=s.user_id
  JOIN grad_process gp ON gp.student_id=s.id
  ORDER BY s.gpa DESC, u.full_name
")->fetchAll();

// Декларации (обща статистика)
$declStats = $pdo->query("
  SELECT
    SUM(gown_requested=1)        AS gown_requested_yes,
    SUM(agree_personal_data=1)   AS agree_personal_data_yes,
    SUM(agree_public_name=1)     AS agree_public_name_yes,
    SUM(agree_photos=1)          AS agree_photos_yes,
    SUM(declare_correct=1)       AS declare_correct_yes,
    COUNT(*) AS total
  FROM grad_process
")->fetch();

// Етапи
$stageStats = [
  0 => (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=0")->fetchColumn(),
  1 => (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=1")->fetchColumn(),
  2 => (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=2")->fetchColumn(),
  3 => (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE stage=3")->fetchColumn(),
];

// Тоги
$gownReq = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_requested=1")->fetchColumn();
$gownTaken = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_taken=1")->fetchColumn();
$gownReturned = (int)$pdo->query("SELECT COUNT(*) FROM grad_process WHERE gown_returned=1")->fetchColumn();

// Снимки
$missingPhoto = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE photo IS NULL OR photo=''")->fetchColumn();

// Билети
$totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM guest_tickets")->fetchColumn();
$usedTickets  = (int)$pdo->query("SELECT COUNT(*) FROM guest_tickets WHERE used_at IS NOT NULL")->fetchColumn();

// Непълни заявления (липсва required)
$incomplete = $pdo->query("
  SELECT s.faculty_no, u.full_name, s.group_code
  FROM students s
  JOIN users u ON u.id=s.user_id
  JOIN grad_process gp ON gp.student_id=s.id
  WHERE gp.agree_personal_data=0 OR gp.declare_correct=0
  ORDER BY s.group_code, u.full_name
")->fetchAll();

?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/graduation/assets/styles.css">
  <title>Отчети</title>
</head>
<body>

<div class="topbar">
  <b>Отчети</b>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>

  <span style="margin-left:auto"></span>
  <label> Изтегли готовите таблици от тук: </label>
  <!---<a class="btn" href="/graduation/admin/reports.php?export=students">CSV: студенти</a>--->
  <a class="btn" href="/graduation/admin/reports.php?export=gpa">Студенти, сортирани по успех</a>
  <a class="btn" href="/graduation/admin/reports.php?export=declarations">Статистика на попълнените декларации</a>
  <a class="btn" href="/graduation/admin/reports.php?export=tickets">Билети</a>
  <!---<a class="btn primary" href="/graduation/admin/reports.php?export=stats">CSV: статистика</a>--->
</div>

<div class="container">

  <div class="card">
    <h3>Общи статистики</h3>
    <div class="row">
      <div>
        <b>Етапи</b><br>
        <div class="small">Етап 0 (нови): <b><?=h($stageStats[0])?></b></div>
        <div class="small">Етап 1 (потвърдени): <b><?=h($stageStats[1])?></b></div>
        <div class="small">Етап 2 (на церемония): <b><?=h($stageStats[2])?></b></div>
        <div class="small">Етап 3 (завършили): <b><?=h($stageStats[3])?></b></div>
      </div>

      <div>
        <b>Тоги</b><br>
        <div class="small">Заявени: <b><?=h($gownReq)?></b></div>
        <div class="small">Взети: <b><?=h($gownTaken)?></b></div>
        <div class="small">Върнати: <b><?=h($gownReturned)?></b></div>
      </div>
    </div>

    <hr style="border:none;border-top:1px solid #eee;margin:12px 0">

    <div class="row">
      <div>
        <b>Снимки за диплома</b><br>
        <div class="small">Липсва снимка: <b><?=h($missingPhoto)?></b></div>
      </div>
      <div>
        <b>Билети (гости)</b><br>
        <div class="small">Общо: <b><?=h($totalTickets)?></b></div>
        <div class="small">Използвани: <b><?=h($usedTickets)?></b></div>
        <div class="small">Свободни: <b><?=h($totalTickets - $usedTickets)?></b></div>
      </div>
    </div>
  </div>

  <div class="card">
    <h3>Статистика: дадени съгласия</h3>
    <table class="table">
      <thead><tr><th>Поле</th><th>Отбелязали</th><th>Общо</th></tr></thead>
      <tbody>
        <tr><td>Тога заявена</td><td><?=h($declStats['gown_requested_yes'])?></td><td><?=h($declStats['total'])?></td></tr>
        <tr><td>Съгласие лични данни (GDPR)</td><td><?=h($declStats['agree_personal_data_yes'])?></td><td><?=h($declStats['total'])?></td></tr>
        <tr><td>Публикуване на име</td><td><?=h($declStats['agree_public_name_yes'])?></td><td><?=h($declStats['total'])?></td></tr>
        <tr><td>Снимки/видео</td><td><?=h($declStats['agree_photos_yes'])?></td><td><?=h($declStats['total'])?></td></tr>
        <tr><td>Декларация за вярност</td><td><?=h($declStats['declare_correct_yes'])?></td><td><?=h($declStats['total'])?></td></tr>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Непълни заявления</h3>
    <?php if(!$incomplete): ?>
      <div class="small">Няма.</div>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>ФН</th><th>Име</th><th>Група</th></tr></thead>
        <tbody>
          <?php foreach($incomplete as $r): ?>
            <tr>
              <td><?=h($r['faculty_no'])?></td>
              <td><?=h($r['full_name'])?></td>
              <td><?=h($r['group_code'])?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Студенти по успех</h3>
    <div class="small">Сортирано по успех (низходящо).</div>
    <table class="table">
      <thead>
        <tr>
          <th>ФН</th><th>Име</th><th>Степен</th><th>Група</th><th>Успех</th><th>Етап</th><th>Отличник</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($byGpa as $r): ?>
        <tr>
          <td><?=h($r['faculty_no'])?></td>
          <td><?=h($r['full_name'])?></td>
          <td><?=h($r['degree'])?></td>
          <td><?=h($r['group_code'])?></td>
          <td><b><?=h($r['gpa'] ?? '—')?></b></td>
          <td><?=h(stage_label((int)$r['stage']))?></td>
          <td><?= $r['is_honors'] ? '✅' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>
