document.addEventListener('DOMContentLoaded', async ()=>{
  const box = document.getElementById('citationsBox');
  if(!box) return;
  try{
     const res = await fetch('/graduation/api/citations_list.php');
    const data = await res.json();
    box.innerHTML = data.map(c => `
      <div style="margin:8px 0">
        <cite class="cite-chip" data-cite-id="${c.key_code}">${c.key_code}</cite>
        ${escapeHtml(c.quote_text)}
      </div>
    `).join('');
  }catch(e){
    box.textContent = 'Грешка при зареждане на цитати.';
  }
});

document.addEventListener('click', function(e){
  if(!e.target.classList.contains('cite-chip')) return;

  const textarea = document.querySelector('textarea[name="notes"]');
  if(!textarea) return;

  const code = e.target.dataset.citeId;

  const html = `<cite class="cite-chip" data-cite-id="${code}">${code}</cite> `;

  // вмъкване на мястото на курсора
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;

  textarea.value =
    textarea.value.substring(0, start) +
    html +
    textarea.value.substring(end);

  textarea.focus();
  textarea.selectionStart = textarea.selectionEnd = start + html.length;

  // по желание: логване към сървъра (кой цитира какво)
  fetch('/graduation/api/citations_use.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'code=' + encodeURIComponent(code) + '&context=student_register'
  });
});


function escapeHtml(s){
  return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
