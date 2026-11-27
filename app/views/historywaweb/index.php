<?php
$devices = $data['devices'] ?? [];
$pageTitle = $data['title'] ?? 'History WA Web';
?>

<!-- Header Toolbar Halaman -->
<div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <div class="flex items-center gap-2.5">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 shrink-0">
            <!-- WhatsApp Web Icon -->
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm0 18.06c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.12 8.12 0 01-1.25-4.29c0-4.5 3.66-8.16 8.16-8.16 2.18 0 4.23.85 5.77 2.39a8.11 8.11 0 012.4 5.77c-.01 4.5-3.67 8.15-8.17 8.15zm4.47-6.1c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.84-.86 2.05 0 1.21.88 2.38 1 2.55.12.17 1.73 2.65 4.2 3.71.59.25 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.07-.12-.23-.19-.48-.31z"/>
            </svg>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800">History WhatsApp Web</h2>
                <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live Sync
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">Monitoring percakapan masuk & keluar WhatsApp secara langsung dengan tampilan otentik WhatsApp Web.</p>
        </div>
    </div>

    <!-- Filter Device & Status Action -->
    <div class="flex items-center gap-2.5 flex-wrap">
        <!-- Device Filter Dropdown -->
        <div class="relative min-w-[210px]">
            <select id="waweb-device-filter" class="w-full pl-9 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 shadow-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 appearance-none transition-all cursor-pointer">
                <option value="0">🌐 Semua Device WhatsApp</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= $d['id'] ?>" data-phone="<?= htmlspecialchars($d['phone']) ?>" data-status="<?= $d['status'] ?>">
                        <?= ($d['status'] === 'connected' ? '🟢' : '🔴') ?> <?= htmlspecialchars($d['name'] ?: 'Device #' . $d['id']) ?> (<?= htmlspecialchars($d['phone'] ?: 'No Phone') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>

        <!-- Reload Button -->
        <button id="btn-manual-reload" class="p-2 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl text-gray-600 hover:text-emerald-600 shadow-xs transition-all" title="Segarkan Data Sekarang">
            <svg id="reload-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════ -->
<!--  WHATSAPP WEB MAIN CONTAINER (SPLIT PANE)                                     -->
<!-- ════════════════════════════════════════════════════════════════════════════ -->
<div class="bg-white rounded-3xl shadow-sm border border-gray-200/80 flex overflow-hidden h-[calc(100vh-12rem)] min-h-[580px] max-h-[820px] relative">

    <!-- ──────────────────────────────────────────────────────────────────────── -->
    <!--  LEFT PANE: DAFTAR CHAT / KONTAK                                         -->
    <!-- ──────────────────────────────────────────────────────────────────────── -->
    <div id="waweb-left-pane" class="w-full md:w-[360px] lg:w-[410px] flex-shrink-0 flex flex-col border-r border-gray-200/80 bg-white z-10 transition-all duration-200">
        <!-- Header Panel Kiri -->
        <div class="p-3.5 bg-[#f0f2f5] border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold text-xs shadow-xs">
                    WA
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                        WhatsApp Chats
                        <span id="badge-total-chats" class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.2 rounded-full">0</span>
                    </h3>
                    <p class="text-[10px] text-gray-400" id="label-active-device">Semua Device</p>
                </div>
            </div>

            <!-- Auto-sync live indicator -->
            <div class="flex items-center gap-1 text-[11px] text-emerald-700 bg-white px-2 py-1 rounded-lg border border-emerald-200 shadow-2xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Auto-Sync</span>
            </div>
        </div>

        <!-- Toolbar Pencarian & Filter -->
        <div class="p-3 bg-white border-b border-gray-100 space-y-2">
            <!-- Input Cari -->
            <div class="relative">
                <input type="text" id="waweb-search-input" placeholder="Cari kontak, nomor atau isi chat..." class="w-full pl-9 pr-8 py-2 text-xs bg-[#f0f2f5] focus:bg-white border border-transparent focus:border-emerald-400 rounded-xl outline-none transition-all placeholder:text-gray-400">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="button" id="btn-clear-search" class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Filter Pills (Semua, Chat Masuk, Belum Dibaca) -->
            <div class="flex items-center gap-1.5 text-[11px] overflow-x-auto scrollbar-none pb-0.5">
                <button type="button" class="waweb-filter-pill px-2.5 py-1 rounded-full font-semibold transition-all bg-emerald-100 text-emerald-800" data-filter="all">Semua</button>
                <button type="button" class="waweb-filter-pill px-2.5 py-1 rounded-full font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="in">Masuk</button>
                <button type="button" class="waweb-filter-pill px-2.5 py-1 rounded-full font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="unread">Belum Dibaca</button>
            </div>
        </div>

        <!-- Daftar Percakapan (Scrollable) -->
        <div id="waweb-conversations-list" class="flex-1 overflow-y-auto divide-y divide-gray-50">
            <!-- Skeleton Loading State -->
            <div id="conversations-skeleton" class="p-4 space-y-3">
                <?php for($i=0; $i<5; $i++): ?>
                <div class="flex items-center gap-3 animate-pulse">
                    <div class="w-11 h-11 bg-gray-200 rounded-full shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 bg-gray-200 rounded w-1/2"></div>
                        <div class="h-2.5 bg-gray-100 rounded w-3/4"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>


    <!-- ──────────────────────────────────────────────────────────────────────── -->
    <!--  RIGHT PANE: PERCAKAPAN AKTIF / PESAN CHAT (WHATSAPP WEB THEME)         -->
    <!-- ──────────────────────────────────────────────────────────────────────── -->
    <div id="waweb-right-pane" class="hidden md:flex flex-1 flex-col bg-[#efeae2] relative overflow-hidden transition-all duration-200">

        <!-- ── STATE 1: BELUM ADA CHAT YANG DIPILIH (EMPTY STATE) ────────────── -->
        <div id="waweb-empty-state" class="flex-1 flex flex-col items-center justify-center p-8 text-center bg-[#f0f2f5] border-b-8 border-emerald-500">
            <div class="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 mb-4 shadow-sm border border-emerald-100">
                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm0 18.06c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.12 8.12 0 01-1.25-4.29c0-4.5 3.66-8.16 8.16-8.16 2.18 0 4.23.85 5.77 2.39a8.11 8.11 0 012.4 5.77c-.01 4.5-3.67 8.15-8.17 8.15z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-1">WhatsApp Web Live Gateway</h3>
            <p class="text-xs text-gray-500 max-w-md leading-relaxed">
                Pilih kontak dari daftar di sebelah kiri untuk melihat pesan masuk & keluar secara real-time. Anda juga dapat membalas chat secara langsung dari sini.
            </p>
            <div class="mt-8 flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                <span>Terenkripsi end-to-end melalui WAGTW Gateway</span>
            </div>
        </div>

        <!-- ── STATE 2: ACTIVE CHAT SCREEN ───────────────────────────────────── -->
        <div id="waweb-chat-screen" class="hidden flex-1 flex-col h-full">

            <!-- Chat Top Header -->
            <div class="h-16 bg-[#f0f2f5] border-b border-gray-200 px-4 flex items-center justify-between z-10 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Mobile Back Button -->
                    <button type="button" id="btn-back-to-list" class="md:hidden p-1.5 -ml-1 text-gray-600 hover:text-gray-900 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <!-- Contact Avatar -->
                    <div id="chat-header-avatar" class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-xs">
                        WA
                    </div>

                    <!-- Contact Details -->
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 id="chat-header-title" class="text-sm font-bold text-gray-900 truncate">+62 812-xxxx-xxxx</h4>
                            <button type="button" id="btn-copy-phone" class="text-gray-400 hover:text-emerald-600 p-0.5" title="Salin Nomor HP">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <p id="chat-header-subtitle" class="text-[11px] text-gray-500 truncate flex items-center gap-1.5">
                            <span id="chat-device-dot" class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span id="chat-header-device">Device Name</span>
                        </p>
                    </div>
                </div>

                <!-- Chat Action Buttons -->
                <div class="flex items-center gap-1">
                    <!-- Open in WhatsApp Web / App -->
                    <a id="link-direct-wa" href="#" target="_blank" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-white rounded-xl transition shadow-2xs" title="Buka langsung di WhatsApp">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm0 18.06c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.12 8.12 0 01-1.25-4.29c0-4.5 3.66-8.16 8.16-8.16 2.18 0 4.23.85 5.77 2.39a8.11 8.11 0 012.4 5.77c-.01 4.5-3.67 8.15-8.17 8.15z"/></svg>
                    </a>
                    
                    <!-- Refresh active chat -->
                    <button type="button" id="btn-refresh-chat" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-white rounded-xl transition shadow-2xs" title="Segarkan Percakapan Ini">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>

                    <!-- Delete Conversation -->
                    <button type="button" id="btn-delete-conversation" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition shadow-2xs" title="Hapus Seluruh Riwayat Percakapan Ini">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Chat Messages Scrollable Body (WhatsApp Doodle Background) -->
            <div id="waweb-messages-container" class="flex-1 overflow-y-auto px-4 md:px-8 py-5 space-y-3 relative" style="background-color: #efeae2; background-image: radial-gradient(#dfd9ce 0.85px, transparent 0.85px), radial-gradient(#dfd9ce 0.85px, #efeae2 0.85px); background-size: 34px 34px; background-position: 0 0, 17px 17px;">
                <!-- Messages will be injected here via JavaScript -->
            </div>

            <!-- Bottom Reply & Send Bar -->
            <div class="bg-[#f0f2f5] border-t border-gray-200 px-4 py-3 z-10 shrink-0">
                <form id="form-send-message" class="flex items-center gap-2">
                    <input type="hidden" id="active-chat-device-id" value="">
                    <input type="hidden" id="active-chat-contact" value="">

                    <!-- Input Teks Chat -->
                    <div class="flex-1 relative">
                        <textarea id="chat-input-text" rows="1" placeholder="Ketik pesan WhatsApp (tekan Enter untuk kirim)..." class="w-full bg-white border border-gray-200 rounded-2xl px-4 py-2.5 text-xs md:text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-500 outline-none transition resize-none max-h-32 shadow-xs"></textarea>
                    </div>

                    <!-- Tombol Kirim -->
                    <button type="submit" id="btn-send-message" class="w-10 h-10 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white flex items-center justify-center shadow-md shadow-emerald-500/20 transition-all shrink-0" title="Kirim Pesan (Enter)">
                        <svg class="w-4 h-4 transform rotate-45 -ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<!-- ════════════════════════════════════════════════════════════════════════════ -->
<!--  JAVASCRIPT LOGIC UNTUK WHATSAPP WEB UI                                    -->
<!-- ════════════════════════════════════════════════════════════════════════════ -->
<script>
(function() {
    let allConversations = [];
    let currentFilter = 'all';
    let activeConversation = null; // { device_id, contact_phone, contact_display, device_name, device_phone }
    let latestMessageId = 0;
    let isFetchingMessages = false;
    let isFetchingConversations = false;
    let isSending = false;
    let pollIntervalId = null;
    let lastConversationsJson = '';

    // Inisialisasi saat halaman dimuat
    function initWaWeb() {
        loadConversations();
        startPolling();
        bindGlobalEvents();
    }

    // Format jam 24 jam HH:mm lokal untuk bubble optimistik
    function formatCurrentTime() {
        const d = new Date();
        const h = String(d.getHours()).padStart(2, '0');
        const m = String(d.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    function startPolling() {
        if (pollIntervalId) {
            clearInterval(pollIntervalId);
            pollIntervalId = null;
        }

        // Polling setiap 2 detik (2000 ms) agar percakapan masuk & keluar tampil langsung tanpa refresh
        pollIntervalId = setInterval(pollUpdates, 2000);

        // Daftarkan ke interval manager global SPA jika tersedia
        if (window.appIntervals && Array.isArray(window.appIntervals)) {
            window.appIntervals.push(pollIntervalId);
        }
    }

    function bindGlobalEvents() {
        // Tangkap event pesan baru dari background poller main.js jika ada
        const onGlobalNewMsg = function(e) {
            if ($('#waweb-conversations-list').length > 0) {
                loadConversations(true);
                if (activeConversation) {
                    loadMessages(activeConversation.device_id, activeConversation.contact_phone, true);
                }
            }
        };
        window.addEventListener('wagtw:new_message', onGlobalNewMsg);
    }

    // ─── AMBIL DAFTAR CHAT DARI SERVER ──────────────────────────────────────────
    function loadConversations(isBackground = false) {
        if (isFetchingConversations) return;
        isFetchingConversations = true;

        const deviceId = $('#waweb-device-filter').val() || 0;
        const search   = $('#waweb-search-input').val() || '';

        if (!isBackground) {
            $('#conversations-skeleton').removeClass('hidden');
        }

        const reloadIcon = document.getElementById('reload-icon');
        if (reloadIcon && !isBackground) reloadIcon.classList.add('animate-spin');

        $.ajax({
            url: BASEURL + '/historywaweb/getConversations',
            type: 'GET',
            data: { device_id: deviceId, search: search },
            dataType: 'json',
            success: function(res) {
                if (reloadIcon) reloadIcon.classList.remove('animate-spin');
                if (res.status === 'success') {
                    allConversations = res.data || [];
                    const currentJson = JSON.stringify(allConversations);
                    // Hanya re-render jika data benar-benar berubah untuk menghindari jitter scroll
                    if (!isBackground || currentJson !== lastConversationsJson) {
                        lastConversationsJson = currentJson;
                        renderConversationsList();
                    }
                }
            },
            error: function() {
                if (reloadIcon) reloadIcon.classList.remove('animate-spin');
            },
            complete: function() {
                isFetchingConversations = false;
            }
        });
    }

    // ─── RENDER DAFTAR CHAT DI PANEL KIRI ──────────────────────────────────────
    function renderConversationsList() {
        $('#conversations-skeleton').addClass('hidden');
        const container = $('#waweb-conversations-list');
        const search = ($('#waweb-search-input').val() || '').toLowerCase().trim();

        let filtered = allConversations.filter(c => {
            if (currentFilter === 'in' && c.last_direction !== 'in') return false;
            if (currentFilter === 'unread' && c.unread_count <= 0) return false;
            if (search) {
                const matchPhone   = c.contact_phone.toLowerCase().includes(search);
                const matchDisplay = c.contact_display.toLowerCase().includes(search);
                const matchMsg     = (c.last_message || '').toLowerCase().includes(search);
                const matchDev     = (c.device_name || '').toLowerCase().includes(search);
                if (!matchPhone && !matchDisplay && !matchMsg && !matchDev) return false;
            }
            return true;
        });

        $('#badge-total-chats').text(filtered.length);

        if (filtered.length === 0) {
            container.html(`
                <div class="p-8 text-center text-gray-400">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-600">Tidak ada percakapan</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Pesan masuk atau keluar akan otomatis tampil di sini.</p>
                </div>
            `);
            return;
        }

        let html = '';
        filtered.forEach(c => {
            const cleanActivePhone  = activeConversation ? String(activeConversation.contact_phone).replace(/[^0-9]/g, '') : '';
            const cleanContactPhone = String(c.contact_phone).replace(/[^0-9]/g, '');
            const isActive = activeConversation && 
                             cleanActivePhone === cleanContactPhone &&
                             (activeConversation.device_id === 0 || c.device_id === 0 || activeConversation.device_id === c.device_id);

            // Checkmark icon untuk pesan keluar
            let checkmarkHtml = '';
            if (c.last_direction === 'out') {
                if (c.last_status === 'read') {
                    checkmarkHtml = `<svg class="w-3.5 h-3.5 text-[#53bdeb] inline-block mr-1 shrink-0" fill="currentColor" viewBox="0 0 16 16"><path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.5 7.5l-.708-.708 4.5-4.5.708.708-4.5 4.5z"/></svg>`;
                } else {
                    checkmarkHtml = `<svg class="w-3.5 h-3.5 text-gray-400 inline-block mr-1 shrink-0" fill="currentColor" viewBox="0 0 16 16"><path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.5 7.5l-.708-.708 4.5-4.5.708.708-4.5 4.5z"/></svg>`;
                }
            }

            // Unread badge (hanya tampil jika bukan percakapan aktif yang sedang dilihat)
            const showUnread = c.unread_count > 0 && !isActive;
            const unreadBadge = showUnread 
                ? `<span class="bg-emerald-500 text-white text-[10px] font-bold min-w-5 h-5 px-1.5 rounded-full flex items-center justify-center shrink-0 shadow-2xs">${c.unread_count}</span>` 
                : '';

            html += `
                <div class="chat-contact-item p-3 flex items-center gap-3 cursor-pointer transition-colors hover:bg-[#f5f6f6] ${isActive ? 'bg-[#f0f2f5] border-l-4 border-emerald-500' : ''}" 
                     data-device-id="${c.device_id}" 
                     data-contact="${escapeHtml(c.contact_phone)}" 
                     data-display="${escapeHtml(c.contact_display)}"
                     data-device-name="${escapeHtml(c.device_name)}"
                     data-device-phone="${escapeHtml(c.device_phone)}"
                     data-avatar-color="${c.avatar_color}"
                     data-avatar-text="${c.avatar_text}">
                    
                    <!-- Avatar Kontak -->
                    <div class="w-12 h-12 rounded-full ${c.avatar_color} flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-xs select-none">
                        ${c.avatar_text}
                    </div>

                    <!-- Info Kontak & Cuplikan Pesan -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5">
                            <h4 class="text-xs sm:text-sm font-bold text-gray-900 truncate">${escapeHtml(c.contact_display)}</h4>
                            <span class="text-[10px] text-gray-400 font-medium shrink-0 ml-2">${c.time_formatted}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs text-gray-500 truncate flex items-center">
                                ${checkmarkHtml}
                                <span class="truncate">${escapeHtml(c.last_message || 'Tidak ada pesan')}</span>
                            </p>
                            ${unreadBadge}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-mono truncate">📱 ${escapeHtml(c.device_name)}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    // ─── BUKA PERCAKAPAN KONTAK (PANEL KANAN) ──────────────────────────────────
    function openConversation(deviceId, contactPhone, contactDisplay, deviceName, devicePhone, avatarColor, avatarText) {
        deviceId       = parseInt(deviceId) || 0;
        contactPhone   = String(contactPhone || '').trim();
        contactDisplay = String(contactDisplay || contactPhone).trim();
        deviceName     = String(deviceName || '').trim();
        devicePhone    = String(devicePhone || '').trim();

        if (!contactPhone) return;

        activeConversation = {
            device_id: deviceId,
            contact_phone: contactPhone,
            contact_display: contactDisplay,
            device_name: deviceName,
            device_phone: devicePhone,
            avatar_color: avatarColor,
            avatar_text: avatarText
        };

        // Reset status unread di memori lokal secara instan
        const cleanPhone = contactPhone.replace(/[^0-9]/g, '');
        const targetConv = allConversations.find(c => String(c.contact_phone).replace(/[^0-9]/g, '') === cleanPhone);
        if (targetConv) {
            targetConv.unread_count = 0;
        }

        // Update form hidden inputs
        $('#active-chat-device-id').val(deviceId);
        $('#active-chat-contact').val(contactPhone);

        // Update Chat Header Info
        $('#chat-header-title').text(contactDisplay);
        const devLabel = (deviceName || ('Device #' + deviceId)) + (devicePhone ? ' (+' + devicePhone + ')' : '');
        $('#chat-header-device').text(devLabel);
        
        const avatarInitial = avatarText || (cleanPhone.length >= 2 ? cleanPhone.slice(-2) : 'WA');
        $('#chat-header-avatar')
            .attr('class', `w-10 h-10 rounded-full ${avatarColor || 'bg-emerald-600'} flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-xs select-none`)
            .text(avatarInitial);
        
        $('#link-direct-wa').attr('href', 'https://wa.me/' + cleanPhone);

        // Switch to chat screen
        $('#waweb-empty-state').addClass('hidden');
        $('#waweb-chat-screen').removeClass('hidden').addClass('flex');

        // Responsive Mobile handling
        $('#waweb-left-pane').addClass('hidden md:flex');
        $('#waweb-right-pane').removeClass('hidden').addClass('flex');

        // Highlight active contact item & bersihkan unread badge di list
        $('.chat-contact-item').removeClass('bg-[#f0f2f5] border-l-4 border-emerald-500');
        const activeItem = $(`.chat-contact-item[data-contact="${escapeHtml(contactPhone)}"]`);
        activeItem.addClass('bg-[#f0f2f5] border-l-4 border-emerald-500');
        activeItem.find('.bg-emerald-500').remove();

        // Muat pesan dari database
        loadMessages(deviceId, contactPhone, false);
    }

    // ─── AMBIL PESAN CHAT DARI SERVER ──────────────────────────────────────────
    function loadMessages(deviceId, contactPhone, isPolling = false) {
        if (isFetchingMessages) return;
        isFetchingMessages = true;

        deviceId = parseInt(deviceId) || 0;
        contactPhone = String(contactPhone || '').trim();

        if (!contactPhone) {
            isFetchingMessages = false;
            return;
        }

        const container = $('#waweb-messages-container');
        if (!isPolling) {
            container.html(`
                <div class="flex items-center justify-center h-full">
                    <div class="flex items-center gap-2 bg-white/90 backdrop-blur-xs px-4 py-2 rounded-xl shadow-xs text-xs text-gray-600 font-semibold">
                        <svg class="w-4 h-4 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Memuat riwayat chat...
                    </div>
                </div>
            `);
            latestMessageId = 0;
        }

        $.ajax({
            url: BASEURL + '/historywaweb/getMessages',
            type: 'GET',
            data: { 
                device_id: deviceId, 
                contact: contactPhone,
                after_id: isPolling ? latestMessageId : 0 
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const messages = res.messages || [];

                    if (!isPolling) {
                        renderAllMessages(messages);
                    } else if (messages.length > 0) {
                        appendNewMessages(messages);
                    }
                } else {
                    if (!isPolling) {
                        container.html(`
                            <div class="flex flex-col items-center justify-center h-full text-center p-6 text-gray-500">
                                <p class="text-xs font-semibold text-red-500 mb-1">Gagal memuat pesan</p>
                                <p class="text-[11px] text-gray-400">${escapeHtml(res.message || 'Terjadi kesalahan')}</p>
                            </div>
                        `);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('[WA Web] Gagal memuat pesan:', status, error, xhr.responseText);
                if (!isPolling) {
                    container.html(`
                        <div class="flex flex-col items-center justify-center h-full text-center p-6 text-gray-500">
                            <p class="text-xs font-semibold text-red-500 mb-1">Terjadi kesalahan koneksi</p>
                            <p class="text-[11px] text-gray-400">Silakan klik ikon segarkan untuk mencoba lagi.</p>
                        </div>
                    `);
                }
            },
            complete: function() {
                isFetchingMessages = false;
            }
        });
    }

    // ─── RENDER SELURUH PESAN KE BODY CHAT ─────────────────────────────────────
    function renderAllMessages(messages) {
        const container = $('#waweb-messages-container');
        container.empty();

        if (messages.length === 0) {
            container.html(`
                <div class="flex flex-col items-center justify-center h-full text-center p-6">
                    <div class="w-12 h-12 bg-white/80 rounded-2xl flex items-center justify-center text-gray-400 mb-2 shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-700">Belum ada percakapan dengan kontak ini</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Kirim pesan pertama Anda melalui kolom input di bawah.</p>
                </div>
            `);
            return;
        }

        let currentDateGroup = '';
        messages.forEach(m => {
            const numId = parseInt(m.id) || 0;
            if (numId > latestMessageId) latestMessageId = numId;

            // Tanggal Divider
            if (m.date_label !== currentDateGroup) {
                currentDateGroup = m.date_label;
                container.append(`
                    <div class="flex justify-center my-3">
                        <span class="bg-white/90 backdrop-blur-xs text-gray-600 text-[10px] font-bold px-3 py-1 rounded-lg shadow-2xs uppercase tracking-wider select-none">
                            ${m.date_label}
                        </span>
                    </div>
                `);
            }

            container.append(buildMessageBubbleHtml(m));
        });

        scrollToBottom();
    }

    // ─── TAMBAHKAN PESAN BARU DARI POLLING / PENGIRIMAN ────────────────────────
    function appendNewMessages(newMessages) {
        const container = $('#waweb-messages-container');
        let hasIncoming = false;

        newMessages.forEach(m => {
            const numId = parseInt(m.id) || 0;
            if (numId > latestMessageId) latestMessageId = numId;

            // Jika ini pesan keluar (out) terkonfirmasi, bersihkan bubble sementara (optimistic)
            if (m.direction === 'out') {
                const tempMatches = $('.temp-bubble').filter(function() {
                    return $(this).attr('data-msg-text') === m.message;
                });
                if (tempMatches.length > 0) {
                    tempMatches.first().remove();
                } else {
                    $('.temp-bubble').first().remove();
                }
            }

            // Mencegah duplikasi elemen bubble jika ID sudah ada di layar
            if ($('#msg-row-' + m.id).length === 0) {
                container.append(buildMessageBubbleHtml(m));
            }

            if (m.direction === 'in') {
                hasIncoming = true;
            }
        });

        scrollToBottom();

        // Bunyikan notifikasi jika ada pesan masuk baru
        if (hasIncoming && typeof playNotificationSound === 'function') {
            playNotificationSound();
        }
    }

    // ─── BUAT HTML BUBBLE PESAN ALA WHATSAPP WEB ──────────────────────────────
    function buildMessageBubbleHtml(m) {
        const isOut = (m.direction === 'out');
        const formattedText = formatWhatsAppText(m.message);

        // Checkmarks untuk bubble keluar
        let checkmarkHtml = '';
        if (isOut) {
            if (m.status === 'read') {
                checkmarkHtml = `<svg class="w-3.5 h-3.5 text-[#53bdeb] ml-1" fill="currentColor" viewBox="0 0 16 16"><path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.5 7.5l-.708-.708 4.5-4.5.708.708-4.5 4.5z"/></svg>`;
            } else if (m.status === 'pending') {
                // Ikon jam pasir berkedip halus untuk status pending optimistik
                checkmarkHtml = `<svg class="w-3 h-3 text-gray-400 ml-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
            } else {
                checkmarkHtml = `<svg class="w-3.5 h-3.5 text-gray-400 ml-1" fill="currentColor" viewBox="0 0 16 16"><path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.5 7.5l-.708-.708 4.5-4.5.708.708-4.5 4.5z"/></svg>`;
            }
        }

        if (isOut) {
            // Outgoing bubble (Kanan - WhatsApp Hijau Lembut #d9fdd3)
            return `
                <div class="flex justify-end mb-2 group msg-item" id="msg-row-${m.id}">
                    <div class="max-w-[85%] md:max-w-[72%] bg-[#d9fdd3] rounded-2xl rounded-tr-none px-3.5 py-2 shadow-xs text-gray-900 text-xs md:text-sm relative">
                        <div class="whitespace-pre-wrap break-words leading-relaxed select-text">${formattedText}</div>
                        <div class="flex items-center justify-end text-[10px] text-gray-500 mt-1 select-none">
                            <span>${m.time}</span>
                            ${checkmarkHtml}
                            <button type="button" onclick="deleteSingleMessage(${m.id})" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 ml-2 transition" title="Hapus pesan ini">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Incoming bubble (Kiri - Putih Bersih)
            return `
                <div class="flex justify-start mb-2 group msg-item" id="msg-row-${m.id}">
                    <div class="max-w-[85%] md:max-w-[72%] bg-white rounded-2xl rounded-tl-none px-3.5 py-2 shadow-xs text-gray-900 text-xs md:text-sm relative border border-gray-100">
                        <div class="whitespace-pre-wrap break-words leading-relaxed select-text">${formattedText}</div>
                        <div class="flex items-center justify-end text-[10px] text-gray-400 mt-1 select-none">
                            <span>${m.time}</span>
                            <button type="button" onclick="deleteSingleMessage(${m.id})" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 ml-2 transition" title="Hapus pesan ini">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    // ─── POLLING OTOMATIS BACKGROUND (SETIAP 2 DETIK) ──────────────────────────
    function pollUpdates() {
        // Segarkan daftar percakapan
        loadConversations(true);

        // Jika ada percakapan aktif terbuka, polling pesan baru
        if (activeConversation) {
            loadMessages(activeConversation.device_id, activeConversation.contact_phone, true);
        }
    }

    // ─── KIRIM PESAN LANGSUNG ─────────────────────────────────────────────────
    $('#form-send-message').on('submit', function(e) {
        e.preventDefault();
        sendMessageAction();
    });

    // Kirim pesan saat Enter ditekan (tanpa shift)
    $('#chat-input-text').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessageAction();
        }
    });

    function sendMessageAction() {
        if (isSending) return;

        const deviceId = $('#active-chat-device-id').val();
        const contact  = $('#active-chat-contact').val();
        const input    = $('#chat-input-text');
        const message  = input.val().trim();

        if (!deviceId || !contact || !message) return;

        isSending = true;
        const sendBtn = $('#btn-send-message');
        sendBtn.prop('disabled', true).addClass('opacity-75');

        // Optimistic UI Append dengan kelas temp-bubble
        const tempId = 'temp-' + Date.now();
        const tempMsg = {
            id: tempId,
            direction: 'out',
            message: message,
            time: formatCurrentTime(),
            status: 'pending'
        };
        const tempBubbleHtml = $(buildMessageBubbleHtml(tempMsg))
            .addClass('temp-bubble')
            .attr('data-msg-text', message);

        $('#waweb-messages-container').append(tempBubbleHtml);
        scrollToBottom();

        input.val('').focus();

        $.ajax({
            url: BASEURL + '/historywaweb/sendMessage',
            type: 'POST',
            data: {
                device_id: deviceId,
                contact: contact,
                message: message
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // Segarkan langsung pesan dan daftar percakapan
                    loadMessages(deviceId, contact, true);
                    loadConversations(true);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Kirim!', text: res.message });
                    $(`#msg-row-${tempId}`).remove();
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan koneksi saat mengirim pesan.' });
                $(`#msg-row-${tempId}`).remove();
            },
            complete: function() {
                isSending = false;
                sendBtn.prop('disabled', false).removeClass('opacity-75');
            }
        });
    }

    // ─── HAPUS PESAN TUNGGAL ──────────────────────────────────────────────────
    window.deleteSingleMessage = function(id) {
        Swal.fire({
            title: 'Hapus Pesan?',
            text: 'Pesan ini akan dihapus dari riwayat.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hapus'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: BASEURL + '/historywaweb/deleteMessage',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        $(`#msg-row-${id}`).fadeOut(200, function() { $(this).remove(); });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            });
        });
    };

    // ─── HAPUS SELURUH PERCAKAPAN ─────────────────────────────────────────────
    $('#btn-delete-conversation').on('click', function() {
        if (!activeConversation) return;

        Swal.fire({
            title: 'Hapus Seluruh Percakapan?',
            text: `Semua riwayat chat dengan ${activeConversation.contact_display} akan dihapus secara permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hapus Semua'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: BASEURL + '/historywaweb/deleteConversation',
                type: 'POST',
                data: {
                    device_id: activeConversation.device_id,
                    contact: activeConversation.contact_phone
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message, timer: 1200, showConfirmButton: false });
                        activeConversation = null;
                        $('#waweb-chat-screen').addClass('hidden').removeClass('flex');
                        $('#waweb-empty-state').removeClass('hidden');
                        loadConversations();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            });
        });
    });

    // ─── EVENT HANDLERS & NAVIGATION ──────────────────────────────────────────
    // Klik Kontak dari Daftar
    $(document).on('click', '.chat-contact-item', function(e) {
        e.preventDefault();
        const item = $(this);
        const deviceId    = parseInt(item.attr('data-device-id')) || 0;
        const contact     = String(item.attr('data-contact') || '').trim();
        const display     = String(item.attr('data-display') || contact).trim();
        const devName     = String(item.attr('data-device-name') || '').trim();
        const devPhone    = String(item.attr('data-device-phone') || '').trim();
        const avatarColor = String(item.attr('data-avatar-color') || 'bg-emerald-600').trim();
        const avatarText  = String(item.attr('data-avatar-text') || '').trim();

        if (!contact) return;
        openConversation(deviceId, contact, display, devName, devPhone, avatarColor, avatarText);
    });

    // Tombol Back di Mobile
    $('#btn-back-to-list').on('click', function() {
        $('#waweb-right-pane').addClass('hidden md:flex');
        $('#waweb-left-pane').removeClass('hidden');
    });

    // Filter Device Change
    $('#waweb-device-filter').on('change', function() {
        const label = $(this).find('option:selected').text().trim();
        $('#label-active-device').text(label.replace(/^[🟢🔴🌐]\s*/, ''));
        loadConversations();
    });

    // Filter Pills Change (Semua, Masuk, Belum Dibaca)
    $('.waweb-filter-pill').on('click', function() {
        $('.waweb-filter-pill').removeClass('bg-emerald-100 text-emerald-800').addClass('bg-gray-100 text-gray-600');
        $(this).removeClass('bg-gray-100 text-gray-600').addClass('bg-emerald-100 text-emerald-800');
        currentFilter = $(this).data('filter');
        renderConversationsList();
    });

    // Search Input Typing
    $('#waweb-search-input').on('input', function() {
        const val = $(this).val().trim();
        if (val) {
            $('#btn-clear-search').removeClass('hidden');
        } else {
            $('#btn-clear-search').addClass('hidden');
        }
        renderConversationsList();
    });

    // Clear Search Button
    $('#btn-clear-search').on('click', function() {
        $('#waweb-search-input').val('');
        $(this).addClass('hidden');
        renderConversationsList();
    });

    // Manual Reload
    $('#btn-manual-reload, #btn-refresh-chat').on('click', function() {
        loadConversations();
        if (activeConversation) {
            loadMessages(activeConversation.device_id, activeConversation.contact_phone, false);
        }
    });

    // Copy Phone Number
    $('#btn-copy-phone').on('click', function() {
        if (!activeConversation) return;
        const clean = activeConversation.contact_phone.replace(/[^0-9]/g, '');
        navigator.clipboard.writeText(clean).then(() => {
            Swal.fire({ icon: 'success', title: 'Tersalin!', text: 'Nomor WhatsApp berhasil disalin ke clipboard.', timer: 1000, showConfirmButton: false });
        });
    });

    // ─── UTILITY HELPERS ──────────────────────────────────────────────────────
    function scrollToBottom() {
        const el = document.getElementById('waweb-messages-container');
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatWhatsAppText(text) {
        if (!text) return '';
        let escaped = escapeHtml(text);

        // 1. Simpan URL ke token khusus bebas karakter markdown agar tidak tertabrak regex (*, _, ~)
        const urlTokens = [];
        escaped = escaped.replace(/(https?:\/\/[^\s<]+)/g, function(match) {
            const token = '@@@WAURLTOKEN' + urlTokens.length + '@@@';
            urlTokens.push(match);
            return token;
        });

        // 2. Monospace ```kode```
        escaped = escaped.replace(/```([^`]+)```/g, '<code class="bg-black/5 px-1 py-0.5 rounded font-mono text-[11px]">$1</code>');

        // 3. Bold *teks* (support nested di dalam _* atau ~*)
        escaped = escaped.replace(/(^|[\s\(\[\{>_~*])\*([^\s\*].*?[^\s\*]|\S)\*(?=[\s\)\]\}<.,!?:;_~*]|$)/g, '$1<strong class="font-bold">$2</strong>');

        // 4. Italic _teks_ (support nested di dalam *_ atau ~_)
        escaped = escaped.replace(/(^|[\s\(\[\{>*~_])_([^\s_].*?[^\s_]|\S)_(?=[\s\)\]\}<.,!?:;*~_]|$)/g, '$1<em class="italic">$2</em>');

        // 5. Strike ~teks~
        escaped = escaped.replace(/(^|[\s\(\[\{>*_~])~([^\s~].*?[^\s~]|\S)~(?=[\s\)\]\}<.,!?:;*_~]|$)/g, '$1<del class="line-through">$2</del>');

        // 6. Kembalikan URL tokens menjadi elemen <a> asli WhatsApp Web
        urlTokens.forEach((url, i) => {
            const token = '@@@WAURLTOKEN' + i + '@@@';
            const linkHtml = `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 underline hover:text-emerald-900 break-all">${url}</a>`;
            escaped = escaped.split(token).join(linkHtml);
        });

        return escaped;
    }

    // Jalankan inisialisasi
    initWaWeb();

})();
</script>
