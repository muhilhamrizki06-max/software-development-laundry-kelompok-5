document.addEventListener('DOMContentLoaded', () => {
  initLiveSearchNota();
  initAjaxStatusNota();
  initSalinBroadcast();
  initConfirmForms();
});

function showToast(pesan, tipe = 'success') {
  let toast = document.getElementById('laundria-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'laundria-toast';
    toast.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:9999; padding:12px 18px; border-radius:12px; font-size:12px; font-weight:700; color:#fff; box-shadow:0 10px 25px rgba(0,0,0,.15); transition:all .25s ease; opacity:0; transform:translateY(10px);';
    document.body.appendChild(toast);
  }
  toast.style.background = tipe === 'error' ? '#b6463e' : '#1f654f';
  toast.textContent = pesan;
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  });
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
  }, 2600);
}

function initLiveSearchNota() {
  const inputCari = document.querySelector('input[name="q"]');
  const tbody = document.querySelector('.data-table tbody');
  if (!inputCari || !tbody) return;

  inputCari.addEventListener('input', function () {
    const keyword = this.value.toLowerCase().trim();
    tbody.querySelectorAll('tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
    });
  });
}

function initAjaxStatusNota() {
  const formStatus = document.getElementById('form-ajax-status');
  if (!formStatus) return;

  formStatus.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
      const formData = new FormData(this);
      formData.append('ajax', '1');
      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      const data = await res.json();

      if (res.ok && data.ok) {
        const badgeBox = document.getElementById('nota-badge-status');
        if (badgeBox && data.badge_html) badgeBox.innerHTML = data.badge_html;
        showToast('Status cucian diperbarui menjadi: ' + data.status);
      } else {
        showToast(data.msg || 'Gagal memperbarui status', 'error');
      }
    } catch (err) {
      showToast('Terjadi kesalahan jaringan', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = origText;
    }
  });
}

function initSalinBroadcast() {
  const btnSalin = document.getElementById('btn-salin-draft');
  const txtDraft = document.querySelector('textarea[name="draft"]');
  if (!btnSalin || !txtDraft) return;

  btnSalin.addEventListener('click', () => {
    if (!txtDraft.value.trim()) {
      showToast('Buat draf pesan terlebih dahulu', 'error');
      return;
    }
    navigator.clipboard.writeText(txtDraft.value).then(() => {
      showToast('Draf pesan disalin ke clipboard!');
    });
  });
}

function initConfirmForms() {
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    }
  });
}
