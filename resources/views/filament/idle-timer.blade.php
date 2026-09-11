{{-- Idle Session Timeout Notification & Modal --}}
@php
    $timeoutMinutes = (int) env('SESSION_IDLE_TIMEOUT', 15);
    $warningSeconds = 60; // Durasi hitung mundur peringatan (detik)
    $totalTimeoutMs = max(60, $timeoutMinutes * 60) * 1000;
    // Waktu tunggu sebelum modal peringatan muncul (total dikurangi durasi countdown)
    $idleBeforeWarningMs = max(10 * 1000, $totalTimeoutMs - ($warningSeconds * 1000));
    $logoutUrl = filament()->getLogoutUrl();
@endphp

<div
    id="idle-warning-modal"
    class="idle-modal-container hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="idle-modal-title"
>
    {{-- Backdrop --}}
    <div class="idle-modal-backdrop" id="idle-modal-backdrop"></div>

    {{-- Dialog Box --}}
    <div class="idle-modal-wrapper">
        <div class="idle-modal-card">
            {{-- Pulsing Warning Icon --}}
            <div class="idle-icon-wrapper">
                <div class="idle-icon-pulse"></div>
                <div class="idle-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="idle-icon-svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>

            {{-- Title & Message --}}
            <h3 id="idle-modal-title" class="idle-modal-heading">
                Apakah Anda Masih Ada Aktivitas?
            </h3>

            <p class="idle-modal-desc">
                Sistem mendeteksi tidak ada aktivitas beberapa saat. Demi keamanan data Anda, sesi akan otomatis keluar dalam:
            </p>

            {{-- Countdown Display & Progress Bar --}}
            <div class="idle-countdown-box">
                <div class="idle-countdown-timer">
                    <span id="idle-countdown" class="idle-countdown-number">{{ $warningSeconds }}</span>
                    <span class="idle-countdown-unit">detik</span>
                </div>
                <div class="idle-progress-track">
                    <div id="idle-progress-bar" class="idle-progress-fill" style="width: 100%;"></div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="idle-modal-actions">
                <button
                    id="idle-stay-btn"
                    type="button"
                    class="idle-btn idle-btn-primary"
                    autofocus
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="idle-btn-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Saya Masih di Sini (Tetap Login)</span>
                </button>

                <button
                    id="idle-logout-btn"
                    type="button"
                    class="idle-btn idle-btn-secondary"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="idle-btn-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    <span>Keluar Sekarang</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden Logout Form --}}
<form id="idle-logout-form" action="{{ $logoutUrl }}" method="POST" style="display:none;">
    @csrf
</form>

<style>
    /* CSS Scoped untuk memastikan tampilan modal selalu sempurna di Filament theme manapun */
    .idle-modal-container {
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: inherit;
    }
    .idle-modal-container.hidden {
        display: none !important;
    }
    .idle-modal-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        animation: idleFadeIn 0.25s ease-out forwards;
    }
    .idle-modal-wrapper {
        position: relative;
        z-index: 10;
        padding: 1.25rem;
        width: 100%;
        max-width: 30rem;
        animation: idleScaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .idle-modal-card {
        background-color: #ffffff;
        border-radius: 1.25rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.05);
        padding: 2rem 1.75rem;
        text-align: center;
        position: relative;
    }
    .dark .idle-modal-card {
        background-color: #18181b;
        color: #f4f4f5;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.1);
    }
    .idle-icon-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
    }
    .idle-icon-pulse {
        position: absolute;
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 9999px;
        background-color: rgba(245, 158, 11, 0.25);
        animation: idlePulse 2s infinite;
    }
    .idle-icon-circle {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.75rem;
        height: 3.75rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #d97706;
    }
    .dark .idle-icon-circle {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(217, 119, 6, 0.2));
        color: #fbbf24;
    }
    .idle-icon-svg {
        width: 2rem;
        height: 2rem;
    }
    .idle-modal-heading {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 0.5rem 0;
        line-height: 1.4;
    }
    .dark .idle-modal-heading {
        color: #ffffff;
    }
    .idle-modal-desc {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0 0 1.25rem 0;
        line-height: 1.5;
    }
    .dark .idle-modal-desc {
        color: #a1a1aa;
    }
    .idle-countdown-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .dark .idle-countdown-box {
        background-color: #27272a;
        border-color: #3f3f46;
    }
    .idle-countdown-timer {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 0.35rem;
        margin-bottom: 0.75rem;
    }
    .idle-countdown-number {
        font-size: 2.25rem;
        font-weight: 800;
        color: #d97706;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .dark .idle-countdown-number {
        color: #f59e0b;
    }
    .idle-countdown-unit {
        font-size: 0.95rem;
        font-weight: 600;
        color: #64748b;
    }
    .dark .idle-countdown-unit {
        color: #a1a1aa;
    }
    .idle-progress-track {
        width: 100%;
        height: 6px;
        background-color: #e2e8f0;
        border-radius: 9999px;
        overflow: hidden;
    }
    .dark .idle-progress-track {
        background-color: #3f3f46;
    }
    .idle-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #f59e0b, #ef4444);
        border-radius: 9999px;
        transition: width 1s linear;
    }
    .idle-modal-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .idle-modal-actions {
            flex-direction: column;
        }
    }
    .idle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem;
        font-size: 0.925rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border: none;
        outline: none;
    }
    .idle-btn:focus-visible {
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.4);
    }
    .idle-btn-primary {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
    }
    .idle-btn-primary:hover {
        background: linear-gradient(135deg, #fbbf24, #d97706);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(217, 119, 6, 0.4);
    }
    .idle-btn-primary:active {
        transform: translateY(0);
    }
    .idle-btn-secondary {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .idle-btn-secondary:hover {
        background-color: #e2e8f0;
        color: #1e293b;
    }
    .dark .idle-btn-secondary {
        background-color: #27272a;
        color: #d4d4d8;
        border-color: #3f3f46;
    }
    .dark .idle-btn-secondary:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }
    .idle-btn-icon {
        width: 1.15rem;
        height: 1.15rem;
    }

    @keyframes idleFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes idleScaleUp {
        from { opacity: 0; transform: scale(0.92) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes idlePulse {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.18); opacity: 0.2; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }
</style>

<script>
(function () {
    'use strict';

    // Konfigurasi waktu
    const IDLE_BEFORE_WARNING_MS = {{ $idleBeforeWarningMs }};
    const WARNING_DURATION_SEC   = {{ $warningSeconds }};
    const LOGOUT_URL             = '{{ $logoutUrl }}';

    let idleTimer        = null;
    let countdownTimer   = null;
    let titleBlinkTimer  = null;
    let remainingSec     = WARNING_DURATION_SEC;
    const originalTitle  = document.title;

    // Elemen DOM
    const modal        = document.getElementById('idle-warning-modal');
    const countdownEl  = document.getElementById('idle-countdown');
    const progressBar  = document.getElementById('idle-progress-bar');
    const stayBtn      = document.getElementById('idle-stay-btn');
    const logoutBtn    = document.getElementById('idle-logout-btn');
    const logoutForm   = document.getElementById('idle-logout-form');

    // Suara notifikasi halus (Web Audio API)
    function playWarningBeep() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5

            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.45);
        } catch (e) {
            // Audio context mungkin diblokir browser sebelum user interaksi
        }
    }

    // Flash tab title ketika modal muncul
    function startTitleAlert() {
        let toggle = false;
        clearInterval(titleBlinkTimer);
        titleBlinkTimer = setInterval(() => {
            document.title = toggle ? `⚠️ (${remainingSec}s) Sesi Berakhir!` : originalTitle;
            toggle = !toggle;
        }, 1000);
    }

    function stopTitleAlert() {
        clearInterval(titleBlinkTimer);
        document.title = originalTitle;
    }

    // Tampilkan modal peringatan
    function showWarning() {
        remainingSec = WARNING_DURATION_SEC;
        updateUI();

        modal.classList.remove('hidden');
        stayBtn.focus();
        playWarningBeep();
        startTitleAlert();

        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            remainingSec -= 1;
            updateUI();

            if (remainingSec <= 0) {
                clearInterval(countdownTimer);
                stopTitleAlert();
                doLogout();
            }
        }, 1000);
    }

    // Update angka countdown dan progress bar
    function updateUI() {
        if (countdownEl) countdownEl.textContent = remainingSec;
        if (progressBar) {
            const percent = Math.max(0, (remainingSec / WARNING_DURATION_SEC) * 100);
            progressBar.style.width = percent + '%';
        }
    }

    // Batalkan peringatan & perpanjang sesi (Keep Alive)
    function stayLoggedIn() {
        clearInterval(countdownTimer);
        stopTitleAlert();
        modal.classList.add('hidden');
        resetIdleTimer();

        // Broadcast ke tab lain bahwa user masih aktif
        try {
            localStorage.setItem('idle_sync_keepalive', Date.now().toString());
        } catch (e) {}

        // Ping server untuk refresh session Laravel
        fetch(window.location.href, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-cache'
        }).catch(() => {});
    }

    // Eksekusi Logout
    function doLogout() {
        clearInterval(countdownTimer);
        stopTitleAlert();

        try {
            localStorage.setItem('idle_sync_logout', Date.now().toString());
        } catch (e) {}

        if (logoutForm) {
            logoutForm.submit();
        } else {
            window.location.href = LOGOUT_URL;
        }
    }

    // Reset idle timer
    function resetIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showWarning, IDLE_BEFORE_WARNING_MS);
    }

    // Tombol listener
    stayBtn.addEventListener('click', stayLoggedIn);
    logoutBtn.addEventListener('click', doLogout);

    // Keyboard support: Enter / Space / Escape saat modal aktif
    document.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('hidden')) {
            if (e.key === 'Enter' || e.key === 'Escape' || e.key === ' ') {
                e.preventDefault();
                stayLoggedIn();
            }
        }
    });

    // Deteksi aktivitas user saat modal BELUM muncul
    const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
    activityEvents.forEach(function (eventName) {
        document.addEventListener(eventName, function () {
            if (!modal.classList.contains('hidden')) return;
            resetIdleTimer();
            try {
                localStorage.setItem('idle_sync_activity', Date.now().toString());
            } catch (e) {}
        }, { passive: true });
    });

    // Sinkronisasi multi-tab lewat localStorage
    window.addEventListener('storage', function (e) {
        if (e.key === 'idle_sync_activity' || e.key === 'idle_sync_keepalive') {
            if (!modal.classList.contains('hidden')) {
                clearInterval(countdownTimer);
                stopTitleAlert();
                modal.classList.add('hidden');
            }
            resetIdleTimer();
        } else if (e.key === 'idle_sync_logout') {
            doLogout();
        }
    });

    // Jalankan timer saat halaman dimuat
    resetIdleTimer();
})();
</script>
