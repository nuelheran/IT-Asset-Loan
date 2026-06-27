        </div>
    </div>
</div>

<!-- Modal konfirmasi global (pengganti confirm() native browser) -->
<div class="app-modal" id="appConfirmModal">
    <div class="app-modal-box">
        <div class="app-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
        </div>
        <p id="appConfirmMessage">Apakah Anda yakin?</p>
        <div class="app-modal-actions">
            <button type="button" class="btn btn-outline" id="appConfirmCancel">Batal</button>
            <button type="button" class="btn btn-danger" id="appConfirmOk">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
</body>
</html>
