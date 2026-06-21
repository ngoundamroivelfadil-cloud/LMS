/* ===== THEME (DARK MODE) ===== */
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const target = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', target);
    localStorage.setItem('theme', target);
}

/* ===== MODALS ===== */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

/* ===== TABS ===== */
function switchTab(btn, tabId) {
    btn.closest('.tabs').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
}

/* ===== ALERTS AUTO-HIDE ===== */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert').forEach(a => {
        setTimeout(() => { a.style.opacity='0'; a.style.transition='opacity .4s'; setTimeout(()=>a.remove(),400); }, 4000);
    });
    document.querySelectorAll('.progress-fill[data-width]').forEach(el => {
        setTimeout(() => el.style.width = el.dataset.width + '%', 200);
    });
    checkNotifications();
    setInterval(checkNotifications, 30000); // Check every 30s
});

async function checkNotifications() {
    const bell = document.querySelector('.notif-bell .fa-bell');
    if (!bell) return;
    try {
        const r = await fetch(API_URL + 'notifications.php?action=count');
        const d = await r.json();
        if (d.count > 0) {
            let dot = bell.parentElement.querySelector('.notif-dot');
            if (!dot) {
                dot = document.createElement('span');
                dot.className = 'notif-dot';
                bell.parentElement.appendChild(dot);
            }
        }
    } catch(e) {}
}

/* ===== CONFIRM DELETE ===== */
function confirmDelete(msg, url) {
    if (confirm(msg || 'Supprimer ?')) window.location.href = url;
}

/* ===== TIMER ===== */
let timerInterval;
function startTimer(totalSeconds, displayId) {
    const el = document.getElementById(displayId);
    if (!el) return;
    let s = totalSeconds;
    timerInterval = setInterval(() => {
        s--;
        const m = Math.floor(s/60), sec = s%60;
        el.textContent = `${m}:${sec<10?'0':''}${sec}`;
        if (s<=60) el.style.color='#ef4444';
        if (s<=0) { clearInterval(timerInterval); document.getElementById('eval-form')?.submit(); }
    }, 1000);
}

/* ===== UPLOAD DRAG & DROP ===== */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.upload-zone').forEach(zone => {
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
        zone.addEventListener('drop', e => {
            e.preventDefault(); zone.classList.remove('drag');
            const input = zone.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                zone.querySelector('p').textContent = e.dataTransfer.files[0].name;
            }
        });
        const input = zone.querySelector('input[type="file"]');
        if (input) input.addEventListener('change', () => {
            if (input.files.length) zone.querySelector('p').textContent = input.files[0].name;
        });
    });
});

/* ===== TOAST ===== */
function toast(msg, type='success') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;background:${type==='success'?'#10b981':'#ef4444'};color:white;padding:14px 20px;border-radius:10px;font-size:.875rem;font-weight:600;z-index:9999;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.2)`;
    t.innerHTML = `<i class="fa-solid fa-${type==='success'?'check':'xmark'}"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .4s'; setTimeout(()=>t.remove(),400); }, 3000);
}

/* ===== CONTENT TYPE TOGGLE ===== */
function toggleContentType(v) {
    document.getElementById('blockPdf').style.display      = v==='pdf'           ? '' : 'none';
    document.getElementById('blockVideoUrl').style.display = v==='video_url'     ? '' : 'none';
    document.getElementById('blockVideoFic').style.display = v==='video_fichier' ? '' : 'none';
}

function toggleQType(sel, blockId) {
    document.getElementById(blockId).style.display = sel.value==='ouverte' ? 'none' : '';
}
