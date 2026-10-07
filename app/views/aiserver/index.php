<?php
// Note: $groqModels map is no longer needed since we manage AI Models in DB natively
// Tab IDs: server, model, search, monitoring
?>

<div class="space-y-6 max-w-5xl mt-4 px-4 pb-12">
    <!-- Header -->
    <div>
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center text-white shadow-md">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Server</h2>
        </div>
        <p class="text-gray-500 text-sm">Kelola API Keys, Model AI</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 flex flex-wrap gap-2 w-fit">
        <button onclick="switchTab('server')" id="tab-btn-server" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all bg-purple-600 text-white shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            Server AI
        </button>
        <button onclick="switchTab('model')" id="tab-btn-model" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            Model AI
        </button>
        <button onclick="switchTab('search')" id="tab-btn-search" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Server Search
        </button>
        <button onclick="switchTab('monitoring')" id="tab-btn-monitoring" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Monitoring Aktivasi
            <?php
                $activeCount = count(array_filter($data['ai_activations'] ?? [], fn($a) => $a['ai_enabled'] == 1));
            ?>
            <span id="monitoring-active-badge" class="<?= $activeCount > 0 ? '' : 'hidden ' ?>bg-green-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none"><?= $activeCount ?></span>
        </button>
    </div>

    <!-- TAB 1: API KEYS -->
    <div id="tab-content-server" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 flex items-start justify-between border-b border-gray-50 flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    API Keys Groq
                    <span id="server-total-badge" class="bg-purple-100 text-purple-700 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($data['servers'] ?? []) ?></span>
                </h3>
                <p class="text-sm text-gray-400 mt-1">Key pertama yang aktif akan dipakai. Fallback otomatis ke key berikutnya jika gagal.</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openAddModal()" class="bg-purple-600 hover:bg-purple-700 text-white flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-purple-600/20 transition-all flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Key
                </button>
            </div>
        </div>

        <!-- Toolbar Pencarian & Info Maks 10/halaman -->
        <div class="px-6 py-3 bg-gray-50/50 border-b border-gray-50 flex flex-wrap items-center justify-between gap-3 <?= empty($data['servers']) ? 'hidden' : '' ?>" id="server-toolbar">
            <div class="relative w-full sm:w-72">
                <input type="text" id="server-search-input" oninput="filterServerList()" placeholder="Cari nama / key..." class="w-full pl-9 pr-4 py-2 text-xs bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none transition">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <span class="inline-block w-2 h-2 rounded-full bg-purple-500"></span>
                <span>Maksimal 10 API Key per halaman</span>
            </div>
        </div>

        <div class="divide-y divide-gray-50" id="server-list-container">
            <?php if (empty($data['servers'])): ?>
            <div class="p-10 text-center text-gray-500" id="server-empty-msg">Belum ada API Key.</div>
            <?php else: ?>
            <?php foreach ($data['servers'] as $i => $srv): ?>
            <?php $maskedKey = substr($srv['api_key'], 0, 11) . '...' . substr($srv['api_key'], -8); ?>
            <div class="p-6 flex items-center justify-between hover:bg-gray-50/50 transition group server-row-item" id="server-row-<?= $srv['id'] ?>" data-label="<?= strtolower(htmlspecialchars($srv['label'])) ?>" data-key="<?= strtolower(htmlspecialchars($srv['api_key'])) ?>">
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 font-bold flex items-center justify-center text-lg server-row-index"><?= $i+1 ?></div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-base"><?= htmlspecialchars($srv['label']) ?></h4>
                        <p class="text-sm font-mono text-gray-400 mt-0.5"><?= $maskedKey ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <?php if ($srv['is_active']): ?>
                        <span class="bg-green-100 text-green-700 text-xs font-bold px-4 py-1.5 rounded-full">AKTIF</span>
                    <?php else: ?>
                        <span class="bg-gray-100 text-gray-500 text-xs font-bold px-4 py-1.5 rounded-full">NONAKTIF</span>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-1.5">
                        <button onclick="toggleActive(<?= $srv['id'] ?>)" class="p-2 <?= $srv['is_active'] ? 'text-green-600 bg-green-50 hover:bg-green-100' : 'text-gray-400 bg-gray-50 hover:bg-gray-100' ?> rounded-xl transition shadow-xs" title="<?= $srv['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button>
                        <button onclick="openEditModal(<?= $srv['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-100 bg-blue-50 rounded-xl transition shadow-xs" title="Edit"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                        <button onclick="deleteServer(<?= $srv['id'] ?>)" class="p-2 text-red-600 hover:bg-red-100 bg-red-50 rounded-xl transition shadow-xs" title="Hapus"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div id="server-no-results" class="p-10 text-center text-gray-400 hidden">Tidak ditemukan API Key yang cocok dengan kata kunci.</div>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <div id="server-pagination-footer" class="p-4 sm:p-5 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm bg-gray-50/40 <?= empty($data['servers']) ? 'hidden' : '' ?>">
            <div class="text-xs sm:text-sm text-gray-500">
                Menampilkan <span id="server-page-start" class="font-bold text-gray-800">1</span> - <span id="server-page-end" class="font-bold text-gray-800">10</span> dari <span id="server-total-count" class="font-bold text-gray-800"><?= count($data['servers'] ?? []) ?></span> API Key
            </div>
            <div class="flex items-center gap-1.5 flex-wrap justify-center" id="server-page-buttons">
                <!-- Tombol Pagination di-render via JavaScript -->
            </div>
        </div>
    </div>


    <!-- TAB 2: AI MODELS -->
    <div id="tab-content-model" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hidden">
        <div class="p-6 flex items-start justify-between border-b border-gray-50 flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                    AI Models
                </h3>
                <p class="text-sm text-gray-400 mt-1">Model diurutkan dari atas, dipakai secara fallback jika model sebelumnya gagal.</p>
            </div>
            <button onclick="openAddModelModal()" class="bg-purple-600 hover:bg-purple-700 text-white flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-purple-600/20 transition-all flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Model
            </button>
        </div>

        <div class="divide-y divide-gray-50">
            <?php if (empty($data['models'])): ?>
            <div class="p-10 text-center text-gray-500">Belum ada AI Model.</div>
            <?php else: ?>
            <?php foreach ($data['models'] as $i => $mod): ?>
            <div class="p-6 flex items-center justify-between hover:bg-gray-50/50 transition group" id="model-row-<?= $mod['id'] ?>">
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center text-lg"><?= $i+1 ?></div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-base"><?= htmlspecialchars($mod['label']) ?></h4>
                        <p class="text-sm font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($mod['model_name']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <?php if ($mod['is_active']): ?>
                        <span class="bg-green-100 text-green-700 text-xs font-bold px-4 py-1.5 rounded-full">AKTIF</span>
                    <?php else: ?>
                        <span class="bg-gray-100 text-gray-500 text-xs font-bold px-4 py-1.5 rounded-full">NONAKTIF</span>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-1.5">
                        <button onclick="toggleActiveModel(<?= $mod['id'] ?>)" class="p-2 <?= $mod['is_active'] ? 'text-green-600 bg-green-50 hover:bg-green-100' : 'text-gray-400 bg-gray-50 hover:bg-gray-100' ?> rounded-xl transition shadow-xs" title="<?= $mod['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button>
                        <button onclick="openEditModelModal(<?= $mod['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-100 bg-blue-50 rounded-xl transition shadow-xs" title="Edit"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                        <button onclick="deleteModel(<?= $mod['id'] ?>)" class="p-2 text-red-600 hover:bg-red-100 bg-red-50 rounded-xl transition shadow-xs" title="Hapus"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 3: SERVER SEARCH (redesigned) -->
    <div id="tab-content-search" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hidden">
        <!-- Header -->
        <div class="p-6 flex items-start justify-between border-b border-gray-50 flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <h3 class="text-lg font-bold text-gray-800">Pilihan Mesin Pencari</h3>
            </div>
            <div class="flex flex-col items-end gap-1">
                <button onclick="resetSearchStats()" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reset Statistik
                </button>
                <?php
                    // Get the latest reset_at from all search engines
                    $resetAt = null;
                    foreach (($data['search_engines'] ?? []) as $se) {
                        if (!empty($se['reset_at'])) { $resetAt = $se['reset_at']; break; }
                    }
                ?>
                <span id="search-reset-label" class="text-xs text-gray-400">
                    Reset terakhir:<br>
                    <span id="search-reset-date"><?= $resetAt ? date('d M Y, H:i', strtotime($resetAt)) . ' WIB' : 'Belum pernah direset' ?></span>
                </span>
            </div>
        </div>

        <!-- Engine Cards -->
        <div class="divide-y divide-gray-50 px-2 py-2 space-y-2">
            <?php
            // Define provider icon SVGs inline
            $providerIcons = [
                'google' => '<svg viewBox="0 0 48 48" class="w-8 h-8" xmlns="http://www.w3.org/2000/svg"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>',
                'bing'   => '<svg viewBox="0 0 24 24" class="w-8 h-8" xmlns="http://www.w3.org/2000/svg"><path d="M5 2v14.27l3.79 1.43 8.85-5.15-4.97-2.02L5 2zm0 0" fill="#008373"/><path d="M8.79 17.7L5 16.27V21l4.21-1.28 6.43-3.87-1.57-1.02-5.28 2.87zm0 0" fill="#005E50"/><path d="M12.67 10.53L8.79 9V17.7l6.43-3.85-2.55-3.32zm0 0" fill="#00B294"/></svg>',
                'duckduckgo' => '<svg viewBox="0 0 48 48" class="w-8 h-8" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="24" fill="#DE5833"/><path d="M24 10c-7.7 0-14 6.3-14 14s6.3 14 14 14 14-6.3 14-14-6.3-14-14-14zm0 5c4.97 0 9 4.03 9 9s-4.03 9-9 9-9-4.03-9-9 4.03-9 9-9z" fill="#fff"/><circle cx="20.5" cy="21.5" r="2" fill="#3E3F42"/><circle cx="27.5" cy="21.5" r="2" fill="#3E3F42"/></svg>',
            ];

            $providerBg = [
                'google'     => 'bg-red-50 border-red-100',
                'bing'       => 'bg-blue-50 border-blue-100',
                'duckduckgo' => 'bg-orange-50 border-orange-100',
            ];

            if (!empty($data['search_engines'])): ?>
            <?php foreach ($data['search_engines'] as $se): ?>
            <div class="flex items-start justify-between p-5 rounded-2xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/40 transition-all group" id="search-row-<?= $se['id'] ?>">
                <div class="flex items-start gap-4">
                    <!-- Provider Logo -->
                    <div class="w-14 h-14 rounded-2xl <?= $providerBg[$se['provider']] ?? 'bg-gray-50 border-gray-100' ?> border flex items-center justify-center flex-shrink-0 shadow-sm">
                        <?= $providerIcons[$se['provider']] ?? '' ?>
                    </div>
                    <!-- Info -->
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h4 class="font-bold text-gray-900 text-base"><?= htmlspecialchars($se['label']) ?></h4>
                            <?php if ($se['is_active']): ?>
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-bold px-2.5 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> AKTIF
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> NONAKTIF
                                </span>
                            <?php endif; ?>
                            <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2.5 py-0.5 rounded-full search-pull-badge" data-id="<?= $se['id'] ?>">
                                <?= number_format($se['pull_count'] ?? 0) ?> TARIKAN
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 leading-relaxed max-w-md"><?= htmlspecialchars($se['description'] ?? '') ?></p>
                    </div>
                </div>
                <!-- Toggle Switch -->
                <div class="flex flex-col items-center gap-1 flex-shrink-0 ml-4">
                    <label class="relative inline-flex items-center cursor-pointer" title="Aktifkan/Nonaktifkan">
                        <input type="checkbox" class="sr-only peer search-toggle" data-id="<?= $se['id'] ?>" <?= $se['is_active'] ? 'checked' : '' ?>>
                        <div class="w-12 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 shadow-inner"></div>
                    </label>
                    <span class="text-xs font-semibold search-toggle-label-<?= $se['id'] ?> <?= $se['is_active'] ? 'text-green-600' : 'text-gray-400' ?>">
                        <?= $se['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Triple Layer Fallback Info -->
        <div class="mx-6 mb-6 mt-4 p-4 bg-amber-50 border border-amber-200 rounded-2xl">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-amber-400 flex items-center justify-center mt-0.5">
                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </div>
                <div class="text-sm text-amber-900 leading-relaxed">
                    <strong class="font-bold text-amber-800">Sistem Triple Layer Fallback:</strong><br>
                    Sangat disarankan menyalakan ketiganya. Sistem secara otomatis akan terjun dari <strong>Layer 1 → Layer 2 → Layer 3</strong> setiap kali mengalami kebuntuan penarikan data akibat limit (CAPTCHA). Ini akan menciptakan level <em>"Anti-Bocor"</em> tertinggi pada kapabilitas akses internet AI Anda.
                </div>
            </div>
        </div>
    </div>


    <!-- TAB 4: MONITORING AKTIVASI AI -->
    <div id="tab-content-monitoring" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hidden">
        <div class="p-6 border-b border-gray-50">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Monitoring Aktivasi AI
            </h3>
            <p class="text-sm text-gray-400 mt-1">Daftar semua device yang AI-nya sedang aktif, beserta informasi siapa yang mengaktifkan.</p>
        </div>

        <?php if (empty($data['ai_activations'])): ?>
        <div class="p-10 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2"/></svg>
            </div>
            <p class="text-gray-500 font-medium">Belum ada user yang mengaktifkan AI</p>
            <p class="text-sm text-gray-400 mt-1">Saat user mengaktifkan AI di device mereka, akan tampil di sini.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-left">
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pemilik Device</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Diaktifkan Oleh</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Server AI</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Mode</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Diperbarui</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($data['ai_activations'] as $act): ?>
                    <tr class="hover:bg-gray-50/60 transition-colors" id="monitoring-row-<?= $act['device_id'] ?>">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($act['device_name'] ?? 'Device #' . $act['device_id']) ?></p>
                                    <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($act['session_id'] ?? '') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                <?= htmlspecialchars($act['owner_username'] ?? 'Unknown') ?>
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <?php
                                $activator = $act['activated_by_username'] ?? null;
                                $isOwner   = ($act['activated_by_user_id'] == $act['user_id']);
                            ?>
                            <?php if ($activator): ?>
                            <span class="inline-flex items-center gap-1.5 <?= $isOwner ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700' ?> text-xs font-semibold px-2.5 py-1 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                <?= htmlspecialchars($activator) ?>
                                <?php if (!$isOwner): ?>
                                <span class="opacity-75">(bukan pemilik)</span>
                                <?php endif; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php if (!empty($act['server_label'])): ?>
                            <span class="text-xs text-gray-700 font-medium"><?= htmlspecialchars($act['server_label']) ?></span>
                            <span class="block text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($act['server_model'] ?? '') ?></span>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">Auto-Switch</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php
                                $modeLabels = ['auto' => 'AI Otomatis', 'manual' => 'Manual', 'autoreply' => 'Autoreply'];
                                $modeBadge  = ['auto' => 'bg-purple-100 text-purple-700', 'manual' => 'bg-gray-100 text-gray-600', 'autoreply' => 'bg-indigo-100 text-indigo-700'];
                                $rm = $act['reply_mode'] ?? 'manual';
                            ?>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $modeBadge[$rm] ?? 'bg-gray-100 text-gray-500' ?>">
                                <?= $modeLabels[$rm] ?? ucfirst($rm) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-xs text-gray-500"><?= $act['updated_at'] ? date('d M Y, H:i', strtotime($act['updated_at'])) : '—' ?></span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <button onclick="deleteActivation(<?= $act['device_id'] ?>, '<?= htmlspecialchars(addslashes($act['device_name'] ?? 'Device #' . $act['device_id'])) ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 text-xs font-semibold rounded-xl transition shadow-xs" title="Hapus / Nonaktifkan AI di device ini">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>



<!-- ═══════════════════════════════════════════════════════════ -->
<!--  MODAL: TAMBAH / EDIT API KEY                             -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="modal-key" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md transform transition-all">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 id="modal-key-title" class="text-lg font-bold text-gray-800">Tambah API Key</h3>
            <button onclick="closeModal('modal-key')" class="text-gray-400 hover:text-gray-600 transition bg-gray-50 rounded-full p-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-key" class="p-6 space-y-5">
            <input type="hidden" name="id" id="key-id">
            <input type="hidden" name="model" value="llama3-8b-8192"> <!-- Default hidden model, tak lagi dipakai -->
            
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-semibold text-gray-700">Nama / Label</label>
                    <button type="button" onclick="setKeyDefaultName()" class="text-xs text-purple-600 hover:text-purple-700 font-medium">Reset nama default</button>
                </div>
                <input type="text" id="key-label" name="label" placeholder="Misal: Groq Key 1" class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none text-sm transition">
                <p class="text-[11px] text-gray-400 mt-1">Bisa custom nama sendiri atau biarkan otomatis (default: Groq Key #).</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">API Key Groq <span class="text-red-500">*</span></label>
                <div class="relative">
                    <textarea id="key-apikey" name="api_key" required rows="3" placeholder="gsk_..." style="-webkit-text-security: disc;" class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none text-sm font-mono pr-11 transition resize-y"></textarea>
                    <button type="button" onclick="toggleKeyTextareaVisibility()" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 p-1" title="Lihat/Sembunyikan Key">
                        <svg id="eye-icon-apikey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Bisa paste 1 API Key atau banyak sekaligus (satu baris per key).</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Prioritas</label>
                    <input type="number" id="key-priority" name="priority" value="0" min="0" max="100" class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none text-sm transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <label class="flex items-center gap-3 mt-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" id="key-active" name="is_active" checked class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 pt-3">
                <button type="submit" id="btn-save-key" class="flex-1 bg-purple-600 text-white font-bold py-3 rounded-xl text-sm hover:bg-purple-700 transition shadow-lg shadow-purple-200">
                    Simpan Key
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  MODAL: TAMBAH / EDIT AI MODEL                            -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="modal-model" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md transform transition-all">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 id="modal-model-title" class="text-lg font-bold text-gray-800">Tambah AI Model</h3>
            <button onclick="closeModal('modal-model')" class="text-gray-400 hover:text-gray-600 transition bg-gray-50 rounded-full p-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-model" class="p-6 space-y-5">
            <input type="hidden" name="id" id="model-id">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Display Name <span class="text-red-500">*</span></label>
                <input type="text" id="model-label" name="label" required placeholder="Misal: LLaMA 3.3 70B" class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none text-sm transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Model ID (Groq) <span class="text-red-500">*</span></label>
                <input type="text" id="model-name" name="model_name" required placeholder="Misal: llama-3.3-70b-versatile" class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none text-sm transition font-mono">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Prioritas</label>
                    <input type="number" id="model-priority" name="priority" value="0" min="0" max="100" class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none text-sm transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <label class="flex items-center gap-3 mt-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" id="model-active" name="is_active" checked class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 pt-3">
                <button type="submit" id="btn-save-model" class="flex-1 bg-indigo-600 text-white font-bold py-3 rounded-xl text-sm hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                    Simpan Model
                </button>
            </div>
        </form>
    </div>
</div>


<script>
// ─── Modal Utility ────────────────────────────────────────────────────────────
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function toggleVisibility(inputId) {
    const el = document.getElementById(inputId);
    el.type = el.type === 'password' ? 'text' : 'password';
}

// ─── API Keys Actions & Pagination ──────────────────────────────────────────
var serverCurrentPage = 1;
var SERVER_PAGE_SIZE = 10;

function getVisibleServerRows() {
    const all = Array.from(document.querySelectorAll('.server-row-item'));
    return all.filter(r => !r.classList.contains('search-hidden'));
}

function getNextKeyDefaultLabel() {
    const rows = document.querySelectorAll('.server-row-item');
    return 'Groq Key ' + (rows.length + 1);
}

function setKeyDefaultName() {
    const defaultLabel = getNextKeyDefaultLabel();
    document.getElementById('key-label').value = defaultLabel;
}

function toggleKeyTextareaVisibility() {
    const el = document.getElementById('key-apikey');
    if (!el) return;
    const isMasked = el.style.webkitTextSecurity !== 'none';
    el.style.webkitTextSecurity = isMasked ? 'none' : 'disc';
}

function renderServerPagination() {
    const visibleRows = getVisibleServerRows();
    const total = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(total / SERVER_PAGE_SIZE));

    if (serverCurrentPage > totalPages) serverCurrentPage = totalPages;
    if (serverCurrentPage < 1) serverCurrentPage = 1;

    const startIdx = (serverCurrentPage - 1) * SERVER_PAGE_SIZE;
    const endIdx   = startIdx + SERVER_PAGE_SIZE;

    // Tampilkan / sembunyikan baris sesuai halaman
    visibleRows.forEach((row, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });

    // Pastikan baris yang terfilter search tetap disembunyikan
    const allRows = document.querySelectorAll('.server-row-item');
    allRows.forEach(r => {
        if (r.classList.contains('search-hidden')) {
            r.classList.add('hidden');
        }
    });

    // Pesan jika hasil search kosong
    const noResultsEl = document.getElementById('server-no-results');
    if (noResultsEl) {
        if (allRows.length > 0 && total === 0) {
            noResultsEl.classList.remove('hidden');
        } else {
            noResultsEl.classList.add('hidden');
        }
    }

    // Update info teks
    const infoStart = total === 0 ? 0 : startIdx + 1;
    const infoEnd   = Math.min(endIdx, total);
    const startEl = document.getElementById('server-page-start');
    const endEl   = document.getElementById('server-page-end');
    const countEl = document.getElementById('server-total-count');
    if (startEl) startEl.textContent = infoStart;
    if (endEl)   endEl.textContent   = infoEnd;
    if (countEl) countEl.textContent = total;

    // Render Tombol Pagination
    const btnContainer = document.getElementById('server-page-buttons');
    if (!btnContainer) return;
    btnContainer.innerHTML = '';

    const footer = document.getElementById('server-pagination-footer');
    if (footer) {
        if (allRows.length === 0) {
            footer.classList.add('hidden');
            return;
        } else {
            footer.classList.remove('hidden');
        }
    }

    // Jika hanya 1 halaman dan item <= SERVER_PAGE_SIZE, tombol tidak perlu muncul
    if (totalPages <= 1) {
        return;
    }

    // Tombol Prev
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'px-3 py-1.5 rounded-xl border text-xs font-semibold transition flex items-center gap-1 ' + 
        (serverCurrentPage === 1 
            ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50' 
            : 'border-gray-200 text-gray-700 bg-white hover:bg-gray-100 hover:border-gray-300 shadow-xs cursor-pointer');
    prevBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Prev';
    prevBtn.disabled = (serverCurrentPage === 1);
    prevBtn.onclick = () => { if (serverCurrentPage > 1) { serverCurrentPage--; renderServerPagination(); } };
    btnContainer.appendChild(prevBtn);

    // Tombol Angka Halaman
    const range = getPaginationRange(serverCurrentPage, totalPages);
    range.forEach(p => {
        if (p === '...') {
            const dots = document.createElement('span');
            dots.className = 'px-2 py-1 text-gray-400 text-xs select-none';
            dots.textContent = '...';
            btnContainer.appendChild(dots);
        } else {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            const isActive = (p === serverCurrentPage);
            pageBtn.className = 'w-8 h-8 rounded-xl text-xs font-bold transition cursor-pointer ' + 
                (isActive 
                    ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 ring-2 ring-purple-400/20' 
                    : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200');
            pageBtn.textContent = p;
            pageBtn.onclick = () => { serverCurrentPage = p; renderServerPagination(); };
            btnContainer.appendChild(pageBtn);
        }
    });

    // Tombol Next
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'px-3 py-1.5 rounded-xl border text-xs font-semibold transition flex items-center gap-1 ' + 
        (serverCurrentPage === totalPages 
            ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50' 
            : 'border-gray-200 text-gray-700 bg-white hover:bg-gray-100 hover:border-gray-300 shadow-xs cursor-pointer');
    nextBtn.innerHTML = 'Next <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
    nextBtn.disabled = (serverCurrentPage === totalPages);
    nextBtn.onclick = () => { if (serverCurrentPage < totalPages) { serverCurrentPage++; renderServerPagination(); } };
    btnContainer.appendChild(nextBtn);
}

function getPaginationRange(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    if (current <= 4) {
        return [1, 2, 3, 4, 5, '...', total];
    }
    if (current >= total - 3) {
        return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
    }
    return [1, '...', current - 1, current, current + 1, '...', total];
}

function filterServerList() {
    const q = (document.getElementById('server-search-input')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.server-row-item');
    rows.forEach(r => {
        const label = r.getAttribute('data-label') || '';
        const key   = r.getAttribute('data-key') || '';
        if (!q || label.includes(q) || key.includes(q)) {
            r.classList.remove('search-hidden');
        } else {
            r.classList.add('search-hidden');
        }
    });
    serverCurrentPage = 1;
    renderServerPagination();
}

function openAddModal() {
    document.getElementById('form-key').reset();
    document.getElementById('key-id').value = '';
    document.getElementById('modal-key-title').textContent = 'Tambah API Key';
    
    // Otomatis isi nama default
    const defaultLabel = getNextKeyDefaultLabel();
    document.getElementById('key-label').value = defaultLabel;
    document.getElementById('key-label').placeholder = defaultLabel;

    // Reset textarea key
    const keyInput = document.getElementById('key-apikey');
    if (keyInput) {
        keyInput.value = '';
        keyInput.style.webkitTextSecurity = 'disc';
    }

    document.getElementById('modal-key').classList.remove('hidden');
    setTimeout(() => {
        if (keyInput) keyInput.focus();
    }, 150);
}

function openEditModal(id) {
    fetch(BASEURL + '/aiserver/getServer?id=' + id)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            document.getElementById('key-id').value       = d.id;
            document.getElementById('key-label').value    = d.label;
            const keyInput = document.getElementById('key-apikey');
            if (keyInput) {
                keyInput.value = d.api_key;
                keyInput.style.webkitTextSecurity = 'disc';
            }
            document.getElementById('key-priority').value = d.priority;
            document.getElementById('key-active').checked = d.is_active == 1;
            document.getElementById('modal-key-title').textContent = 'Edit API Key';
            document.getElementById('modal-key').classList.remove('hidden');
            setTimeout(() => {
                const labelInput = document.getElementById('key-label');
                if (labelInput) labelInput.focus();
            }, 150);
        });
}

const formKey = document.getElementById('form-key');
if (formKey) {
    formKey.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const url = fd.get('id') ? '/aiserver/edit' : '/aiserver/add';
        
        fetch(BASEURL + url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                    closeModal('modal-key');
                    setTimeout(() => window.reloadCurrentPage(), 1600);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            });
    });
}

function deleteServer(id) {
    Swal.fire({
        title: 'Hapus API Key?', text: 'Tindakan ini tidak dapat dibatalkan.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#7c3aed', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Hapus!'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData(); fd.append('id', id);
        fetch(BASEURL + '/aiserver/delete', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    document.getElementById('server-row-' + id)?.remove();
                    // Update index badges dan badge total
                    const allRows = document.querySelectorAll('.server-row-item');
                    allRows.forEach((r, idx) => {
                        const badge = r.querySelector('.server-row-index');
                        if (badge) badge.textContent = idx + 1;
                    });
                    const totalBadge = document.getElementById('server-total-badge');
                    if (totalBadge) totalBadge.textContent = allRows.length;
                    renderServerPagination();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            });
    });
}

function toggleActive(id) {
    const fd = new FormData(); fd.append('id', id);
    fetch(BASEURL + '/aiserver/toggleActive', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') window.reloadCurrentPage();
        });
}

// Inisialisasi Pagination API Keys saat halaman selesai dimuat
renderServerPagination();

function testApiKey(id, apiKey) {
    Swal.fire({ title: '⚡ Testing API...', text: 'Mengirim request ke Groq...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    const fd = new FormData(); fd.append('api_key', apiKey); fd.append('model', 'llama3-8b-8192');
    fetch(BASEURL + '/aiserver/testApi', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.status === 'success' ? '✅ API Valid!' : '❌ Gagal!', text: res.message });
        });
}

// ─── AI Models Actions ────────────────────────────────────────────────────────
function openAddModelModal() {
    document.getElementById('form-model').reset();
    document.getElementById('model-id').value = '';
    document.getElementById('modal-model-title').textContent = 'Tambah AI Model';
    document.getElementById('modal-model').classList.remove('hidden');
}

function openEditModelModal(id) {
    fetch(BASEURL + '/aiserver/getModel?id=' + id)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            document.getElementById('model-id').value       = d.id;
            document.getElementById('model-label').value    = d.label;
            document.getElementById('model-name').value     = d.model_name;
            document.getElementById('model-priority').value = d.priority;
            document.getElementById('model-active').checked = d.is_active == 1;
            document.getElementById('modal-model-title').textContent = 'Edit AI Model';
            document.getElementById('modal-model').classList.remove('hidden');
        });
}

const formModel = document.getElementById('form-model');
if (formModel) {
    formModel.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const url = fd.get('id') ? '/aiserver/editModel' : '/aiserver/addModel';
        
        fetch(BASEURL + url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                    closeModal('modal-model');
                    setTimeout(() => window.reloadCurrentPage(), 1600);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            });
    });
}

function deleteModel(id) {
    Swal.fire({
        title: 'Hapus Model AI?', text: 'Tindakan ini tidak dapat dibatalkan.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#4f46e5', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Hapus!'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData(); fd.append('id', id);
        fetch(BASEURL + '/aiserver/deleteModel', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    document.getElementById('model-row-' + id)?.remove();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            });
    });
}

function toggleActiveModel(id) {
    const fd = new FormData(); fd.append('id', id);
    fetch(BASEURL + '/aiserver/toggleActiveModel', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') window.reloadCurrentPage();
        });
}

// ─── AI Search Engines Actions ──────────────────────────────────────────────
function openAddSearchModal() {
    const form = document.getElementById('form-search');
    if (form) form.reset();
    const searchId = document.getElementById('search-id');
    if (searchId) searchId.value = '';
    const titleEl = document.getElementById('modal-search-title');
    if (titleEl) titleEl.textContent = 'Tambah Search Engine';
    const modalEl = document.getElementById('modal-search');
    if (modalEl) modalEl.classList.remove('hidden');
}

function openEditSearchModal(id) {
    fetch(BASEURL + '/aiserver/getSearch?id=' + id)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            if (document.getElementById('search-id')) document.getElementById('search-id').value       = d.id;
            if (document.getElementById('search-label')) document.getElementById('search-label').value    = d.label;
            if (document.getElementById('search-provider')) document.getElementById('search-provider').value = d.provider;
            if (document.getElementById('search-apikey')) document.getElementById('search-apikey').value   = d.api_key;
            if (document.getElementById('search-priority')) document.getElementById('search-priority').value = d.priority;
            if (document.getElementById('search-active')) document.getElementById('search-active').checked = d.is_active == 1;
            if (document.getElementById('modal-search-title')) document.getElementById('modal-search-title').textContent = 'Edit Search Engine';
            if (document.getElementById('modal-search')) document.getElementById('modal-search').classList.remove('hidden');
        });
}

const formSearch = document.getElementById('form-search');
if (formSearch) {
    formSearch.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const url = fd.get('id') ? '/aiserver/editSearch' : '/aiserver/addSearch';
        
        fetch(BASEURL + url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                    closeModal('modal-search');
                    setTimeout(() => window.reloadCurrentPage(), 1600);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            });
    });
}

function deleteSearch(id) {
    Swal.fire({
        title: 'Hapus Search Engine?', text: 'Tindakan ini tidak dapat dibatalkan.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#0d9488', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Hapus!'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData(); fd.append('id', id);
        fetch(BASEURL + '/aiserver/deleteSearch', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    document.getElementById('search-row-' + id)?.remove();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            });
    });
}

function toggleActiveSearch(id) {
    const fd = new FormData(); fd.append('id', id);
    fetch(BASEURL + '/aiserver/toggleActiveSearch', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') window.reloadCurrentPage();
        });
}

// Handle toggle checkbox clicks via delegation
document.addEventListener('change', function(e) {
    const cb = e.target.closest('.search-toggle');
    if (!cb) return;
    const id = cb.dataset.id;
    const fd = new FormData(); fd.append('id', id);
    fetch(BASEURL + '/aiserver/toggleActiveSearch', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const lbl = document.querySelector('.search-toggle-label-' + id);
                const badge = document.querySelector('#search-row-' + id + ' span.inline-flex');
                const isNowActive = res.is_active == 1;
                if (lbl) {
                    lbl.textContent = isNowActive ? 'Aktif' : 'Nonaktif';
                    lbl.className = lbl.className.replace(/text-(green|gray)-\d+/g, isNowActive ? 'text-green-600' : 'text-gray-400');
                }
            }
        });
});

function resetSearchStats() {
    Swal.fire({
        title: 'Reset Statistik Tarikan?',
        text: 'Semua hitungan tarikan akan direset ke 0.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6b7280',
        cancelButtonText: 'Batal',
        confirmButtonText: 'Ya, Reset!'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(BASEURL + '/aiserver/resetSearchStats', { method: 'POST' })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    // Update all tarikan badges to 0
                    document.querySelectorAll('.search-pull-badge').forEach(el => {
                        el.textContent = '0 TARIKAN';
                    });
                    // Update reset date label
                    const dateEl = document.getElementById('search-reset-date');
                    if (dateEl) dateEl.textContent = res.reset_at;
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                }
            });
    });
}

// ─── Monitoring Aktivasi Actions ─────────────────────────────────────────────
function deleteActivation(deviceId, deviceName) {
    Swal.fire({
        title: 'Hapus Aktivasi AI?',
        text: 'Nonaktifkan AI pada ' + deviceName + '? AI pada device ini akan dimatikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonText: 'Batal',
        confirmButtonText: 'Ya, Hapus!'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('device_id', deviceId);
        fetch(BASEURL + '/aiserver/deleteActivation', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    const row = document.getElementById('monitoring-row-' + deviceId);
                    if (row) row.remove();
                    
                    // Update badge count
                    const badge = document.getElementById('monitoring-active-badge');
                    const remainingRows = document.querySelectorAll('#tab-content-monitoring tbody tr').length;
                    if (badge) {
                        if (remainingRows > 0) {
                            badge.textContent = remainingRows;
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                    if (remainingRows === 0) {
                        window.reloadCurrentPage();
                    } else {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan pada server.' });
            });
    });
}

function switchTab(tab) {
    const tabs = ['server', 'model', 'search', 'monitoring'];
    
    tabs.forEach(t => {
        const btn = document.getElementById('tab-btn-' + t);
        const content = document.getElementById('tab-content-' + t);
        if (t === tab) {
            if (btn) btn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all bg-purple-600 text-white shadow-md';
            if (content) content.classList.remove('hidden');
        } else {
            if (btn) btn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50';
            if (content) content.classList.add('hidden');
        }
    });
    if (tab === 'server' && typeof renderServerPagination === 'function') {
        renderServerPagination();
    }
}
</script>
