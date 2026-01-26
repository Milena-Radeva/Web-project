<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
require_role(['admin','superadmin']);
?>
<!doctype html><html lang="bg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/graduation/assets/styles.css">
<script src="/graduation/assets/html5-qrcode.min.js"></script>
<title>Скенер</title></head><body>

<div class="topbar">
  <b>Скенер</b>
  <span style="margin-left:auto"><?=h(current_user()['full_name'])?></span>
  <a class="btn" href="/graduation/admin/dashboard.php">Назад</a>
</div>

<div class="container">
  <div class="card">
    <h3>Сканиране</h3>
    <div class="small">Насочи камерата към QR билета. Всеки билет е еднократен.</div>
    <div id="reader" style="width:340px;max-width:100%;margin-top:12px;"></div>
    <p style="margin-top:10px;">
      <button class="btn primary" id="btnStart">Старт</button>
      <button class="btn" id="btnStop">Стоп</button>
    </p>
  </div>

  <div class="card">
    <h3>Резултат</h3>
    <div id="resultBox" class="small">Очаквам сканиране…</div>
  </div>
</div>

<script>
const resultBox = document.getElementById('resultBox');
let html5QrCode = null;

function parseQr(decodedText){
  try{
    const u = new URL(decodedText);
    const token = u.searchParams.get('token');

    // разпознаваме типа по пътя
    if(u.pathname.includes('/api/student_checkin.php')) return {type:'student', token};
    if(u.pathname.includes('/api/guest_checkin.php')) return {type:'guest', token};

    return {type:'unknown', token};
  }catch(e){
    return {type:'unknown', token:null};
  }
}

async function processQr(decodedText){
  const info = parseQr(decodedText);
  if(!info.token){
    resultBox.textContent = '❌ QR кодът няма token.';
    return;
  }

  let endpoint = '';
  if(info.type === 'student') endpoint = '/graduation/api/student_checkin.php';
  else if(info.type === 'guest') endpoint = '/graduation/api/guest_checkin.php';
  else {
    // fallback: пробваме като гост, ако не стане -> пробваме студент
    endpoint = '/graduation/api/guest_checkin.php';
  }

  resultBox.textContent = 'Проверка…';
  let res = await fetch(endpoint + '?token=' + encodeURIComponent(info.token));
  let text = await res.text();

  // ако е unknown и guest не става, пробваме student
  if(info.type === 'unknown' && (text.includes('Невалиден билет') || res.status === 404)){
    res = await fetch('/graduation/api/student_checkin.php?token=' + encodeURIComponent(info.token));
    text = await res.text();
  }

  resultBox.textContent = text;
}


async function startScanner(){
  if(!html5QrCode) html5QrCode = new Html5Qrcode("reader");
  try{
    await html5QrCode.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 230 },
      async (decodedText) => {
        await stopScanner();
        await processQr(decodedText);
        }
    );
    resultBox.textContent = '✅ Камерата е активна.';
  }catch(e){
    resultBox.textContent = '❌ Не мога да стартирам камерата (разрешение?).';
  }
}

async function stopScanner(){
  if(!html5QrCode) return;
  try{ await html5QrCode.stop(); await html5QrCode.clear(); }catch(e){}
}

document.getElementById('btnStart').addEventListener('click', startScanner);
document.getElementById('btnStop').addEventListener('click', stopScanner);
</script>
</body></html>
