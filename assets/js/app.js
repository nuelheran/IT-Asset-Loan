/* ==========================================================
   IT ASSET LOAN — app.js
   Interaktivitas: sidebar mobile, tooltip stat-card,
   modal konfirmasi custom, auto-dismiss alert, dll.
   ========================================================== */
(function () {
    'use strict';

    /* ---------------- Sidebar mobile (off-canvas) ---------------- */
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    var closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        overlay.classList.add('visible');
        document.body.classList.add('sidebar-locked');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }
    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
        document.body.classList.remove('sidebar-locked');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }
    if (toggleBtn) toggleBtn.addEventListener('click', function () {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
    // Tutup otomatis saat menu diklik (mobile)
    document.querySelectorAll('.sidebar-nav a').forEach(function (a) {
        a.addEventListener('click', function () { closeSidebar(); });
    });
    // Tutup dengan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    /* ---------------- Baris tabel yang bisa diklik (data-href) ---------------- */
    document.querySelectorAll('table.data-table tbody tr[data-href]').forEach(function (row) {
        row.addEventListener('click', function (e) {
            // Jangan trigger redirect jika yang diklik adalah link/tombol/elemen interaktif di dalam baris
            if (e.target.closest('a, button, input, select, textarea, label')) return;
            window.location.href = row.getAttribute('data-href');
        });
    });

    /* ---------------- Auto-dismiss alert sukses ---------------- */
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            el.classList.add('alert-fade-out');
            setTimeout(function () { el.style.display = 'none'; }, 400);
        }, 4500);
    });

    /* ---------------- Ripple kecil pada tombol (micro-interaction) ---------------- */
    document.querySelectorAll('.btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var rect = btn.getBoundingClientRect();
            var ripple = document.createElement('span');
            ripple.className = 'btn-ripple';
            ripple.style.left = (e.clientX - rect.left) + 'px';
            ripple.style.top = (e.clientY - rect.top) + 'px';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 600);
        });
    });

    /* ---------------- Modal konfirmasi custom (pengganti confirm() native) ---------------- */
    var confirmModal = document.getElementById('appConfirmModal');
    var confirmMessage = document.getElementById('appConfirmMessage');
    var confirmOkBtn = document.getElementById('appConfirmOk');
    var confirmCancelBtn = document.getElementById('appConfirmCancel');
    var pendingAction = null;

    function showConfirmModal(message, onConfirm) {
        if (!confirmModal) { if (onConfirm) onConfirm(); return; }
        confirmMessage.textContent = message;
        confirmModal.classList.add('visible');
        pendingAction = onConfirm;
        confirmOkBtn.focus();
    }
    function hideConfirmModal() {
        if (!confirmModal) return;
        confirmModal.classList.remove('visible');
        pendingAction = null;
    }
    if (confirmOkBtn) confirmOkBtn.addEventListener('click', function () {
        var action = pendingAction;
        hideConfirmModal();
        if (action) action();
    });
    if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', hideConfirmModal);
    if (confirmModal) confirmModal.addEventListener('click', function (e) {
        if (e.target === confirmModal) hideConfirmModal();
    });

    // Intercept elemen ber-atribut data-confirm (link maupun submit button)
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var message = el.getAttribute('data-confirm');
            var isSubmitButton = el.tagName === 'BUTTON' && el.type === 'submit';
            var form = isSubmitButton ? el.closest('form') : null;
            var href = el.tagName === 'A' ? el.getAttribute('href') : null;

            e.preventDefault();
            showConfirmModal(message, function () {
                if (form) {
                    form.submit();
                } else if (href) {
                    window.location.href = href;
                }
            });
        });
    });

    /* ---------------- Auto-submit pada select filter (UX lebih cepat) ---------------- */
    document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            sel.form.submit();
        });
    });

    /* ---------------- Loading state pada tombol submit form ---------------- */
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.dataset.originalText = submitBtn.innerHTML;
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
            }
        });
    });

    /* ---------------- Counter animasi pada angka stat-card ---------------- */
    document.querySelectorAll('.stat-card .value[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var current = 0;
        var duration = 700;
        var stepTime = Math.max(Math.floor(duration / Math.max(target, 1)), 20);
        if (target === 0) { el.textContent = '0'; return; }
        var timer = setInterval(function () {
            current += Math.ceil(target / (duration / stepTime));
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = current;
        }, stepTime);
    });

    /* ---------------- Sembunyikan mobile topbar saat scroll ke bawah (opsional, hemat ruang) ---------------- */
    var mobileTopbar = document.querySelector('.mobile-topbar');
    var lastScrollY = window.scrollY;
    if (mobileTopbar) {
        window.addEventListener('scroll', function () {
            var currentY = window.scrollY;
            if (currentY > lastScrollY && currentY > 60) {
                mobileTopbar.classList.add('topbar-hidden');
            } else {
                mobileTopbar.classList.remove('topbar-hidden');
            }
            lastScrollY = currentY;
        }, { passive: true });
    }
})();
