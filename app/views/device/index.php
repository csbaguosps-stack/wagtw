<?php
// ─── AI Styles List ───────────────────────────────────────────────────────────
$aiStyles = [
    'Professional','Helpful','Friendly','Youthful','Formal','Casual','Humorous','Empathetic',
    'Conversational','Informative','Courteous','Authoritative','Supportive','Enthusiastic','Inspirational',
    'Sarcastic','Cheerful','Analytical','Direct','Reassuring','Playful','Knowledgeable',
];

// ─── Groq Models ──────────────────────────────────────────────────────────────
$dbModels = $this->model('AiServer_model')->getActiveModelsGlobal();
$groqModels = [];
if ($dbModels) {
    foreach ($dbModels as $m) {
        $groqModels[$m['model_name']] = $m['label'];
    }
} else {
    // Fallback if db is empty
    $groqModels = [
        'llama3-8b-8192'           => 'LLaMA 3 8B (Cepat)',
        'llama-3.3-70b-versatile'  => 'LLaMA 3.3 70B Versatile'
    ];
}
?>

<!-- ─── Page Header ─────────────────────────────────────────────────────────── -->
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Device</h2>
        <p class="text-sm text-gray-500 mt-1">Kelola sesi WhatsApp Multi-Device. Powered by <strong class="text-emerald-600">WAGTW Engine</strong>.</p>
    </div>
    <div class="flex items-center gap-3">
        <?php if(!empty($data['filter_users'])): ?>
        <select name="filter_user" onchange="loadPage('<?= BASEURL ?>/device?filter_user='+this.value)" class="bg-white border border-gray-200 text-sm rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-400">
            <option value="all" <?= $data['current_filter'] === 'all' ? 'selected' : '' ?>>Semua Device</option>
            <?php foreach($data['filter_users'] as $fu): ?>
            <option value="<?= $fu['id'] ?>" <?= $data['current_filter'] == $fu['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($fu['name']) ?> (<?= htmlspecialchars($fu['username']) ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        
        <span id="engineBadge" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full">
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></span> Cek Engine...
        </span>
        <button onclick="openModal('addDeviceModal')"
                class="bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg px-5 py-2.5 transition transform hover:scale-[1.02] shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Tambah Device
        </button>
    </div>
</div>

<!-- Engine Offline Warning -->
<div id="engineOfflineWarning" class="mb-5 bg-amber-50 border border-amber-200 rounded-xl p-4 items-start gap-3 hidden">
    <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div class="text-sm text-amber-700">
        <p class="font-semibold mb-1">⚡ WAGTW Engine belum berjalan</p>
        <p>Buka terminal di folder <code class="bg-amber-100 px-1.5 py-0.5 rounded text-xs font-mono">wagtw/server/</code> dan jalankan:</p>
        <pre class="mt-2 bg-amber-100 rounded-lg px-3 py-2 text-xs font-mono">node server.js</pre>
    </div>
</div>

<!-- ─── Device Table ──────────────────────────────────────────────────────────── -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <?php if (!empty($data['devices'])): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Package</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide pr-6">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            <?php foreach ($data['devices'] as $dev):
                $statusColors = [
                    'connected'     => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50 border-emerald-200', 'label' => 'Terhubung'],
                    'disconnected'  => ['dot' => 'bg-rose-500',    'text' => 'text-rose-700',    'bg' => 'bg-rose-50 border-rose-200',       'label' => 'Terputus'],
                    'qr'            => ['dot' => 'bg-sky-500',     'text' => 'text-sky-700',     'bg' => 'bg-sky-50 border-sky-200',         'label' => 'Scan QR'],
                    'initializing'  => ['dot' => 'bg-purple-500',  'text' => 'text-purple-700',  'bg' => 'bg-purple-50 border-purple-200',   'label' => 'Memulai'],
                    'authenticated' => ['dot' => 'bg-teal-500',    'text' => 'text-teal-700',    'bg' => 'bg-teal-50 border-teal-200',       'label' => 'Sinkronisasi'],
                    'pending'       => ['dot' => 'bg-amber-400',   'text' => 'text-amber-700',   'bg' => 'bg-amber-50 border-amber-200',     'label' => 'Menunggu'],
                ][$dev['status']] ?? ['dot' => 'bg-gray-400', 'text' => 'text-gray-600', 'bg' => 'bg-gray-50 border-gray-200', 'label' => $dev['status']];
            ?>
            <tr class="hover:bg-gray-50/70 transition-colors" id="dev-row-<?= $dev['id'] ?>" data-session-id="<?= htmlspecialchars($dev['session_id']) ?>">
                <!-- Device Info -->
                <td class="px-4 py-3.5 min-w-[200px]">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <span class="font-bold text-gray-800 text-sm dev-phone-text block">
                                <?= htmlspecialchars($dev['phone'] ? '+' . preg_replace('/^\+/', '', $dev['phone']) : 'Belum Ditautkan') ?>
                            </span>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="font-medium text-gray-700"><?= htmlspecialchars($dev['name']) ?></span>
                                <span class="text-[10px] text-gray-400 px-1.5 py-0.2 bg-gray-100 rounded-md">
                                    By: <?= htmlspecialchars($dev['creator_username'] ?? 'User') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5 ml-10 dev-status-badge">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold border <?= $statusColors['bg'] ?> <?= $statusColors['text'] ?>">
                            <span class="w-1.5 h-1.5 <?= $statusColors['dot'] ?> rounded-full <?= $dev['status'] === 'connected' ? 'animate-pulse' : '' ?>"></span>
                            <?= $statusColors['label'] ?>
                        </span>
                    </div>
                </td>

                <!-- Package Info -->
                <td class="px-4 py-3.5 min-w-[140px] hidden sm:table-cell">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Free Plan</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Unlimited Kuota</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span><?= date('d M Y', strtotime($dev['created_at'] ?? 'now')) ?></span>
                        </div>
                    </div>
                </td>

                <!-- Actions -->
                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                        <!-- Primary Button: Scan QR or Reconnect -->
                        <button onclick="doReconnect('<?= htmlspecialchars($dev['session_id']) ?>')"
                                title="<?= $dev['status'] === 'connected' ? 'Hubungkan Ulang' : 'Scan QR WhatsApp' ?>"
                                class="inline-flex items-center gap-1.5 <?= $dev['status'] === 'connected' ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-500/20' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/25 animate-pulse' ?> text-xs font-semibold px-3 py-1.5 rounded-lg transition shadow-sm active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            <?= $dev['status'] === 'connected' ? 'Reconnect' : 'Scan QR' ?>
                        </button>

                        <!-- Disconnect -->
                        <?php if ($dev['status'] === 'connected'): ?>
                        <button onclick="doDisconnect('<?= htmlspecialchars($dev['session_id']) ?>')"
                                title="Putuskan Sesi WhatsApp"
                                class="inline-flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Putus
                        </button>
                        <?php endif; ?>

                        <!-- Token -->
                        <button onclick="openTokenModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name'], ENT_QUOTES) ?>')"
                                title="API Token"
                                class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium px-2.5 py-1.5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Token
                        </button>

                        <!-- AI Setting -->
                        <button onclick="openAiModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name'], ENT_QUOTES) ?>')"
                                title="Pengaturan AI Auto-Reply"
                                class="inline-flex items-center gap-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2"/></svg>
                            AI
                        </button>

                        <!-- AI Data -->
                        <button onclick="openAiDataModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name'], ENT_QUOTES) ?>')"
                                title="Knowledge Base AI"
                                class="inline-flex items-center gap-1 bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                            Data
                        </button>

                        <!-- Edit -->
                        <button onclick="openEditDeviceModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name'], ENT_QUOTES) ?>')"
                                title="Edit Nama Device"
                                class="inline-flex items-center p-1.5 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-500 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        <!-- Delete -->
                        <button onclick="deleteDevice(<?= $dev['id'] ?>)"
                                title="Hapus Device"
                                class="inline-flex items-center p-1.5 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-500 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-700 mb-1">Belum Ada Device</h3>
        <p class="text-sm text-gray-400 mb-5">Tambahkan device WhatsApp pertama Anda.</p>
        <button onclick="openModal('addDeviceModal')" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg px-5 py-2.5 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Tambah Device Pertama
        </button>
    </div>
    <?php endif; ?>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: Tambah Device                                       -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="addDeviceModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="formAddDevice">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Tambah Device Baru</h3>
                <button type="button" onclick="closeModal('addDeviceModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <label class="block mb-1.5 text-sm font-medium text-gray-700">Nama Device</label>
                <input type="text" name="name" id="inputDeviceName"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5"
                       placeholder="Contoh: CS Pusat 1" required>
                <p class="mt-2 text-xs text-gray-400">Setelah disimpan, klik <strong>Reconnect</strong> untuk menghubungkan WhatsApp.</p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="closeModal('addDeviceModal')" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition text-sm">Batal</button>
                <button type="submit" id="btnSaveDevice" class="px-5 py-2 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 transition text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: Edit Device                                         -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="editDeviceModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="formEditDevice">
            <input type="hidden" id="editDeviceId" name="id">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Edit Device</h3>
                <button type="button" onclick="closeModal('editDeviceModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <label class="block mb-1.5 text-sm font-medium text-gray-700">Nama Device</label>
                <input type="text" name="name" id="editDeviceName"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                       placeholder="Nama device" required>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="closeModal('editDeviceModal')" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition text-sm">Batal</button>
                <button type="submit" class="px-5 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: Token                                               -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="tokenModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gray-800 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Token Device</h3>
                    <p class="text-xs text-gray-500" id="tokenDeviceName">—</p>
                </div>
            </div>
            <button onclick="closeModal('tokenModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <input type="hidden" id="tokenDeviceId">
            <p class="text-sm text-gray-600 mb-4">Token ini digunakan untuk mengakses API device dari luar sistem. Jaga kerahasiaannya.</p>

            <div class="bg-gray-900 rounded-xl p-4 flex items-center gap-3 group">
                <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <code id="tokenValue" class="text-green-400 font-mono text-sm flex-1 break-all">—</code>
                <button onclick="copyToken()" class="text-gray-400 hover:text-white transition flex-shrink-0" title="Salin Token">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-gray-400">Klik tombol di samping untuk generate token baru</p>
                <button onclick="doRegenerateToken()" class="flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 font-semibold border border-red-200 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Regenerate Token
                </button>
            </div>

            <!-- Webhook URL Setting -->
            <div class="mt-5 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Webhook URL (Response Engine)
                    </label>
                    <a href="<?= BASEURL ?>/webhook" class="text-xs text-cyan-600 hover:text-cyan-700 font-medium hover:underline flex items-center gap-1">
                        Buka Panduan
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="flex gap-2">
                    <input type="url" id="webhookUrlInput" placeholder="https://domain-anda.com/webhook" class="flex-1 text-xs px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-cyan-500 font-mono text-gray-700">
                    <button onclick="saveDeviceWebhook()" id="btnSaveWebhook" class="px-3.5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold rounded-lg transition shadow-sm flex items-center gap-1">
                        <span>Simpan</span>
                    </button>
                </div>
                <p class="text-[11px] text-gray-400 mt-1.5">Pesan masuk akan diteruskan ke URL ini. Server Anda dapat membalas via direct JSON.</p>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: Setting AI                                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="aiModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Setting AI</h3>
                    <p class="text-xs text-gray-500" id="aiModalDeviceName">—</p>
                </div>
            </div>
            <button onclick="closeModal('aiModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body — scrollable -->
        <form id="formAiSettings" class="overflow-y-auto flex-1">
            <input type="hidden" id="aiDeviceId" name="device_id">
            <div class="p-6 space-y-6">

                <!-- Enable AI Toggle -->
                <div class="flex items-center justify-between bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-100 rounded-xl px-4 py-3">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Aktifkan AI</p>
                        <p class="text-xs text-gray-500">AI akan merespons pesan masuk secara otomatis</p>
                    </div>
                    <label class="relative cursor-pointer">
                        <input type="checkbox" id="aiEnabled" name="ai_enabled" class="sr-only peer" onchange="document.getElementById('aiExtraSettings').classList.toggle('hidden', !this.checked)">
                        <div class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <!-- AI Extra Settings (Search & Target) -->
                <div id="aiExtraSettings" class="hidden space-y-4 pt-2 border-t border-gray-100">
                    <!-- Mode Balasan -->
                    <div>
                        <p class="font-semibold text-gray-700 text-sm mb-2">Mode Balasan</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="reply_mode" value="auto" class="peer sr-only" checked>
                                <div class="px-3 py-2 text-center text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg peer-checked:bg-purple-50 peer-checked:text-purple-700 peer-checked:border-purple-300 transition">
                                    AI Otomatis
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="reply_mode" value="manual" class="peer sr-only">
                                <div class="px-3 py-2 text-center text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg peer-checked:bg-purple-50 peer-checked:text-purple-700 peer-checked:border-purple-300 transition">
                                    AI Manual
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="reply_mode" value="autoreply" class="peer sr-only">
                                <div class="px-3 py-2 text-center text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg peer-checked:bg-purple-50 peer-checked:text-purple-700 peer-checked:border-purple-300 transition">
                                    Hanya Autoreply
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Search Engine Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-700 text-sm">Search Engine (Web Data)</p>
                            <p class="text-xs text-gray-500">AI bisa mencari berita/data terkini secara otomatis</p>
                        </div>
                        <label class="relative cursor-pointer">
                            <input type="checkbox" id="aiSearchEnabled" name="search_enabled" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-500"></div>
                        </label>
                    </div>

                    <!-- Target Balasan -->
                    <div>
                        <p class="font-semibold text-gray-700 text-sm mb-2">Target Balasan</p>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="target_reply" value="both" class="peer sr-only" checked>
                                <div class="px-3 py-2 text-center text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg peer-checked:bg-purple-50 peer-checked:text-purple-700 peer-checked:border-purple-300 transition">
                                    Keduanya
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="target_reply" value="private" class="peer sr-only">
                                <div class="px-3 py-2 text-center text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg peer-checked:bg-purple-50 peer-checked:text-purple-700 peer-checked:border-purple-300 transition">
                                    Pribadi Saja
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="target_reply" value="group" class="peer sr-only">
                                <div class="px-3 py-2 text-center text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg peer-checked:bg-purple-50 peer-checked:text-purple-700 peer-checked:border-purple-300 transition">
                                    Grup Saja
                                </div>
                            </label>
                        </div>
                    </div>
                </div>


                <!-- Model AI -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Model AI</label>
                    <select name="model" id="aiModel" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none text-sm transition">
                        <option value="auto">— Auto-Switch (prioritas) —</option>
                        <?php foreach ($groqModels as $val => $lbl): ?>
                        <option value="<?= $val ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Server AI -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Server AI (API Key)</label>
                    <select name="ai_server_id" id="aiServerId" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none text-sm transition">
                        <option value="">— Auto-Switch (prioritas) —</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Pilih server atau biarkan kosong untuk auto-switch</p>
                </div>



                <!-- Style -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Style</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($aiStyles as $style): ?>
                        <label class="ai-style-chip inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-200 text-xs font-medium text-gray-600 cursor-pointer hover:border-purple-400 hover:text-purple-700 hover:bg-purple-50 transition select-none">
                            <input type="checkbox" name="style[]" value="<?= $style ?>" class="hidden ai-style-cb">
                            <span class="chip-check hidden text-purple-600">✓</span>
                            <?= $style ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Brand -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Brand</label>
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-purple-300">
                        <div class="px-3 py-2.5 bg-gray-50 border-r border-gray-200">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <input type="text" name="brand" id="aiBrand" placeholder="Your brand/business name" class="flex-1 px-3 py-2.5 text-sm outline-none">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Your brand/business name</p>
                </div>

                <!-- AI Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">AI Name</label>
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-purple-300">
                        <div class="px-3 py-2.5 bg-gray-50 border-r border-gray-200">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2"/></svg>
                        </div>
                        <input type="text" name="ai_name" id="aiNameInput" placeholder="Your AI name" class="flex-1 px-3 py-2.5 text-sm outline-none">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Your AI name</p>
                </div>

                <!-- Language -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Language</label>
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-purple-300">
                        <div class="px-3 py-2.5 bg-gray-50 border-r border-gray-200">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                        </div>
                        <input type="text" name="language" id="aiLanguage" placeholder="Your preferred language" class="flex-1 px-3 py-2.5 text-sm outline-none">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Your preferred language response</p>
                </div>

                <!-- Rules -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Rules / System Prompt</label>
                    <div class="flex border border-gray-200 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-purple-300">
                        <div class="px-3 pt-3 bg-gray-50 border-r border-gray-200 self-start">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <textarea name="rules" id="aiRules" rows="5"
                                  placeholder="Contoh:&#10;Kamu adalah asisten toko bernama Andi.&#10;Jawab dengan ramah dan singkat dalam Bahasa Indonesia.&#10;Fokus hanya pada produk yang kami jual."
                                  class="flex-1 px-3 py-2.5 text-sm outline-none resize-none font-mono"></textarea>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">System prompt yang dikirim ke AI sebelum setiap pesan user</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3 bg-gray-50 flex-shrink-0 sticky bottom-0">
                <button type="button" onclick="closeModal('aiModal')" class="flex-1 bg-white border border-gray-200 text-gray-700 font-medium py-2.5 rounded-xl text-sm hover:bg-gray-50 transition">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-2.5 rounded-xl text-sm hover:from-purple-700 hover:to-indigo-700 transition shadow-md">Simpan Setting AI</button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: AI Data                                             -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="aiDataModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-teal-500 to-green-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">AI Data</h3>
                    <p class="text-xs text-gray-500" id="aiDataDeviceName">—</p>
                </div>
            </div>
            <button onclick="closeAiDataModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 p-6 space-y-6">
            <input type="hidden" id="aiDataDeviceId">

            <!-- Add Form -->
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h4 class="font-semibold text-gray-800 mb-4 text-sm" id="aiDataFormTitle">Add Data</h4>
                <input type="hidden" id="editAiDataId">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-white focus-within:ring-2 focus-within:ring-teal-300">
                        <div class="px-3 py-2.5 bg-gray-50 border-r border-gray-200">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <input type="text" id="aiDataTitle" placeholder="data name" class="flex-1 px-3 py-2.5 text-sm outline-none bg-white">
                    </div>
                </div>

                <div class="mb-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Data</label>
                    <div class="flex border border-gray-200 rounded-xl overflow-hidden bg-white focus-within:ring-2 focus-within:ring-teal-300">
                        <div class="px-3 pt-3 bg-gray-50 border-r border-gray-200 self-start">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>
                        <textarea id="aiDataContent" rows="6" placeholder="input your data" class="flex-1 px-3 py-2.5 text-sm outline-none resize-y bg-white min-h-[120px]"></textarea>
                    </div>
                    <p class="text-xs text-teal-600 mt-1" id="aiDataCharCount">max 50.000 characters. per 2000-2500 characters cost around 1 quota ai</p>
                </div>

                <div class="flex gap-2 mt-4">
                    <button onclick="cancelEditAiData()" id="btnCancelAiData" class="hidden px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitAiData()" id="btnSubmitAiData" class="flex-1 bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add
                    </button>
                </div>
            </div>

            <!-- Data List -->
            <div>
                <h4 class="font-semibold text-gray-800 mb-3 text-sm">Data List</h4>
                <div id="aiDataList" class="space-y-2">
                    <div class="text-center py-6 text-gray-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7"/></svg>
                        Belum ada data AI
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- ══════════════════════════════════════════════════════════ -->
<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: Tautkan WhatsApp (QR Code & Pairing Code)             -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="qrModal" class="fixed inset-0 bg-gray-900/80 hidden items-center justify-center z-50 p-3 sm:p-4 backdrop-blur-md transition-all overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[92vh] sm:max-h-[90vh] flex flex-col overflow-hidden border border-gray-100 transform transition-all my-auto">
        <!-- Header -->
        <div class="px-5 sm:px-6 py-3.5 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-emerald-50/60 via-white to-gray-50/60 flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </span>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-gray-800 leading-snug">Tautkan WhatsApp</h3>
                    <p class="text-[11px] text-gray-400">Scan QR Code atau gunakan Kode Pairing (WA Business)</p>
                </div>
            </div>
            <button id="btnCloseQR" onclick="closeQRModal()" class="text-gray-400 hover:text-gray-700 p-1.5 hover:bg-gray-100 rounded-xl transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Tab Switcher -->
        <div class="flex border-b border-gray-100 bg-gray-50/70 p-1.5 gap-1.5 rounded-2xl mx-4 sm:mx-6 mt-3 flex-shrink-0">
            <button type="button" id="tabBtnQR" onclick="switchConnectTab('qr')" class="flex-1 py-2 px-3 text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5 bg-white text-emerald-700 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Scan QR Code</span>
            </button>
            <button type="button" id="tabBtnCode" onclick="switchConnectTab('code')" class="flex-1 py-2 px-3 text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>Kode Pairing</span>
                <span class="text-[9px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-md font-bold uppercase tracking-wider">WA Business</span>
            </button>
        </div>

        <div class="p-4 sm:p-5 text-center overflow-y-auto flex-1">
            <!-- Dynamic Status Banner -->
            <div id="qrStatusBanner" class="mb-3 px-3.5 py-2 rounded-2xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center gap-2 shadow-sm">
                <span id="qrStatusText">⏳ Mempersiapkan koneksi...</span>
            </div>

            <!-- Loading State -->
            <div id="qrSpinner" class="flex flex-col items-center gap-3 text-gray-400 py-8">
                <div class="relative flex items-center justify-center">
                    <div class="w-12 h-12 rounded-full border-4 border-emerald-100 border-t-emerald-500 animate-spin"></div>
                    <div class="absolute w-6 h-6 bg-emerald-50 rounded-full flex items-center justify-center">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></span>
                    </div>
                </div>
                <p class="text-xs font-semibold text-gray-600">Menyiapkan QR Code Definisi Tinggi...</p>
                <p class="text-[11px] text-gray-400">Harap tunggu beberapa saat</p>
            </div>

            <!-- TAB 1 CONTENT: QR CODE -->
            <div id="tabContentQR" class="flex flex-col items-center">
                <!-- QR Container with High Contrast & Camera Viewfinder Corners -->
                <div id="qrContainer" class="hidden flex-col items-center">
                    <div class="relative p-3 bg-white rounded-2xl shadow-lg border border-gray-100 inline-block">
                        <!-- Camera Viewfinder Corner Markers -->
                        <div class="absolute top-1.5 left-1.5 w-4 h-4 border-t-2 border-l-2 border-emerald-500 rounded-tl pointer-events-none"></div>
                        <div class="absolute top-1.5 right-1.5 w-4 h-4 border-t-2 border-r-2 border-emerald-500 rounded-tr pointer-events-none"></div>
                        <div class="absolute bottom-1.5 left-1.5 w-4 h-4 border-b-2 border-l-2 border-emerald-500 rounded-bl pointer-events-none"></div>
                        <div class="absolute bottom-1.5 right-1.5 w-4 h-4 border-b-2 border-r-2 border-emerald-500 rounded-br pointer-events-none"></div>

                        <img id="qrImage" src="" alt="WhatsApp QR Code" 
                             class="w-48 h-48 sm:w-56 sm:h-56 block select-none bg-white rounded-lg" 
                             style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; image-rendering: pixelated;">
                    </div>

                    <!-- Actions under QR -->
                    <div class="mt-3 flex items-center justify-center gap-3">
                        <button type="button" onclick="refreshQR()" class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition border border-emerald-200/80 shadow-sm active:scale-95">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Perbarui QR Code
                        </button>
                    </div>
                </div>

                <!-- Instructions & Scan Assistance -->
                <div id="qrInstructions" class="mt-3 text-left bg-gray-50/80 border border-gray-100 rounded-2xl p-3 sm:p-3.5 space-y-1.5 w-full">
                    <p class="text-xs font-bold text-gray-700 flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">✓</span>
                        Langkah Scan WhatsApp & WA Business:
                    </p>
                    <ol class="text-xs text-gray-600 space-y-1 list-decimal list-inside pl-1">
                        <li>Buka <strong>WhatsApp</strong> / <strong>WA Business</strong> di HP Anda.</li>
                        <li>Buka menu (titik tiga) > <strong>Perangkat Tertaut</strong> > <strong>Tautkan Perangkat</strong>.</li>
                        <li>Arahkan kamera ke QR code di atas (jarak <strong>20 - 30 cm</strong>).</li>
                    </ol>
                    <div class="mt-2 pt-2 border-t border-gray-200/60 text-[11px] text-amber-800 flex items-start gap-1.5 bg-amber-50/60 p-2 rounded-xl">
                        <span class="flex-shrink-0">💡</span>
                        <span><strong>Tips WA Business:</strong> Jika kamera ponsel terkendala pantulan layar, gunakan tab <strong>Kode Pairing</strong> di atas untuk menautkan tanpa kamera!</span>
                    </div>
                </div>
            </div>

            <!-- TAB 2 CONTENT: PAIRING CODE (KODE 8 DIGIT) -->
            <div id="tabContentCode" class="hidden flex-col items-center w-full">
                <div class="w-full text-left bg-gradient-to-br from-emerald-50/50 to-gray-50 border border-emerald-100/80 rounded-2xl p-4 mb-3">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="w-5 h-5 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-[11px] font-bold">1</span>
                        <h4 class="text-xs font-bold text-gray-800">Masukkan Nomor WhatsApp Business</h4>
                    </div>
                    <p class="text-[11px] text-gray-500 mb-3">Tautkan akun tanpa perlu kamera pemindai. Cocok untuk WhatsApp Business.</p>
                    
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="tel" id="inputPairingPhone" placeholder="Contoh: 08123456789 atau 62812..." 
                                   class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-mono outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-500 transition">
                        </div>
                        <button type="button" id="btnGetPairingCode" onclick="doRequestPairingCode()" 
                                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-semibold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5 flex-shrink-0">
                            <span id="btnGetPairingText">Dapatkan Kode</span>
                            <div id="btnGetPairingSpinner" class="hidden w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </button>
                    </div>
                </div>

                <!-- Code Display Box (Muncul setelah kode di-generate) -->
                <div id="pairingCodeBox" class="hidden w-full flex-col items-center mb-3">
                    <p class="text-xs text-gray-600 mb-1.5 font-semibold">Kode Pairing WhatsApp Business Anda:</p>
                    <div class="relative bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border-2 border-dashed border-emerald-400 rounded-2xl p-4 w-full flex items-center justify-center gap-3 shadow-inner">
                        <span id="displayPairingCode" class="text-2xl sm:text-3xl font-black font-mono tracking-widest text-emerald-800 select-all"></span>
                        <button type="button" onclick="copyPairingCode()" title="Salin Kode" class="p-2 text-emerald-700 hover:bg-emerald-100 active:scale-90 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        </button>
                    </div>
                    <p class="text-[11px] text-emerald-700 mt-1.5 font-medium">⏳ Masukkan kode di atas pada aplikasi WhatsApp Business Anda.</p>
                </div>

                <!-- Instructions for Pairing Code -->
                <div class="w-full text-left bg-gray-50 border border-gray-100 rounded-2xl p-4 space-y-2">
                    <p class="text-xs font-bold text-gray-700 flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">✓</span>
                        Cara Menautkan di WhatsApp Business:
                    </p>
                    <ol class="text-xs text-gray-600 space-y-1.5 list-decimal list-inside pl-1">
                        <li>Buka <strong>WhatsApp Business</strong> di HP Anda.</li>
                        <li>Buka menu (titik tiga) > <strong>Perangkat Tertaut</strong> > <strong>Tautkan Perangkat</strong>.</li>
                        <li>Di bagian bawah layar pemindai, ketuk <strong>"Tautkan dengan nomor telepon saja"</strong>.</li>
                        <li>Ketik 8 karakter kode yang tertera di atas.</li>
                        <li>Selesai! Akun WhatsApp Business langsung terhubung otomatis.</li>
                    </ol>
                </div>
            </div>

            <!-- Success State -->
            <div id="qrSuccess" class="hidden flex-col items-center gap-3 text-emerald-600 py-8">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-lg font-bold text-gray-800">WhatsApp Terhubung!</p>
                <p class="text-sm font-semibold text-emerald-700 bg-emerald-50 px-3.5 py-1 rounded-full border border-emerald-200/70" id="qrConnectedPhone"></p>
                <p class="text-xs text-gray-400">Menyinkronkan sesi ke sistem...</p>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!-- JavaScript                                                  -->
<!-- ══════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    let qrPollTimer    = null;
    let currentSession = null;

    // ── Engine Status ─────────────────────────────────────────────────────
    function checkEngineStatus() {
        $.ajax({
            url: BASEURL + '/device/engineStatus', type: 'GET', dataType: 'json', timeout: 8000,
            success(res) {
                if (res.engine_ok) {
                    $('#engineBadge').attr('class', 'inline-flex items-center gap-1.5 text-xs font-medium text-green-700 bg-green-100 px-3 py-1.5 rounded-full')
                        .html('<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Engine Online');
                    $('#engineOfflineWarning').removeClass('flex').addClass('hidden');
                } else {
                    $('#engineBadge').attr('class', 'inline-flex items-center gap-1.5 text-xs font-medium text-red-600 bg-red-100 px-3 py-1.5 rounded-full')
                        .html('<span class="w-2 h-2 bg-red-500 rounded-full"></span> Engine Offline');
                    $('#engineOfflineWarning').removeClass('hidden').addClass('flex');
                }
            },
            error() {
                $('#engineBadge').attr('class', 'inline-flex items-center gap-1.5 text-xs font-medium text-red-600 bg-red-100 px-3 py-1.5 rounded-full')
                    .html('<span class="w-2 h-2 bg-red-500 rounded-full"></span> Engine Offline');
                $('#engineOfflineWarning').removeClass('hidden').addClass('flex');
            }
        });
    }
    checkEngineStatus();
    if (typeof window.registerInterval === 'function') {
        window.registerInterval(checkEngineStatus, 30000);
    } else {
        setInterval(checkEngineStatus, 30000);
    }

    // ── Modal Helpers ─────────────────────────────────────────────────────
    window.openModal  = id => $('#' + id).removeClass('hidden').addClass('flex');
    window.closeModal = id => $('#' + id).removeClass('flex').addClass('hidden');

    function reloadPage() {
        if (typeof window.loadPage === 'function') {
            const currentFilter = $('select[name="filter_user"]').val() || '<?= $data['current_filter'] ?? 'all' ?>';
            const url = BASEURL + '/device' + (currentFilter && currentFilter !== 'all' ? '?filter_user=' + currentFilter : '');
            window.loadPage(url, false);
        } else {
            location.reload();
        }
    }

    // ── Add Device ────────────────────────────────────────────────────────
    $('#formAddDevice').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#btnSaveDevice').prop('disabled', true).text('Menyimpan...');
        $.ajax({
            url: BASEURL + '/device/add', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success(res) {
                if (res.status === 'success') {
                    closeModal('addDeviceModal');
                    document.getElementById('formAddDevice').reset();
                    if (res.session_id) {
                        scanQR(res.session_id, true);
                    } else {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false })
                            .then(() => reloadPage());
                    }
                } else { Swal.fire('Gagal', res.message, 'error'); }
            },
            complete() { btn.prop('disabled', false).text('Simpan'); }
        });
    });

    // ── Edit Device ───────────────────────────────────────────────────────
    window.openEditDeviceModal = function (id, name) {
        $('#editDeviceId').val(id);
        $('#editDeviceName').val(name);
        openModal('editDeviceModal');
    };

    $('#formEditDevice').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: BASEURL + '/device/editDevice', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success(res) {
                if (res.status === 'success') {
                    closeModal('editDeviceModal');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => reloadPage());
                } else { Swal.fire('Gagal', res.message, 'error'); }
            }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────
    window.deleteDevice = function (id) {
        Swal.fire({
            title: 'Hapus Device?', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: BASEURL + '/device/delete', type: 'POST', data: { id }, dataType: 'json',
                success(res) {
                    Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => reloadPage());
                }
            });
        });
    };

    // ── Reconnect ─────────────────────────────────────────────────────────
    window.doReconnect = function (sessionId) {
        scanQR(sessionId, true);
    };

    // ── Disconnect ────────────────────────────────────────────────────────
    window.doDisconnect = function (sessionId) {
        Swal.fire({
            title: 'Putuskan koneksi?', icon: 'question', showCancelButton: true,
            confirmButtonColor: '#db2777', confirmButtonText: 'Ya, Putuskan!', cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            $.ajax({
                url: BASEURL + '/device/disconnect', type: 'POST', data: { session_id: sessionId }, dataType: 'json',
                success(res) {
                    Swal.fire({ icon: 'success', title: res.message || 'Berhasil diputus', timer: 1500, showConfirmButton: false })
                        .then(() => reloadPage());
                },
                error() { Swal.fire('Error', 'Gagal menghubungi engine.', 'error'); }
            });
        });
    };

    // ── Token Modal ───────────────────────────────────────────────────────
    window.openTokenModal = function (id, name) {
        $('#tokenDeviceId').val(id);
        $('#tokenDeviceName').text(name);
        $('#tokenValue').text('Memuat...');
        $('#webhookUrlInput').val('');
        openModal('tokenModal');
        $.ajax({
            url: BASEURL + '/device/getToken?id=' + id, dataType: 'json',
            success(res) {
                if (res.status === 'success') {
                    $('#tokenValue').text(res.token);
                    $('#webhookUrlInput').val(res.webhook_url || '');
                } else { 
                    $('#tokenValue').text('Error: ' + res.message); 
                }
            }
        });
    };

    window.saveDeviceWebhook = function () {
        const id = $('#tokenDeviceId').val();
        const webhook_url = $('#webhookUrlInput').val().trim();
        const btn = $('#btnSaveWebhook');
        
        btn.prop('disabled', true).addClass('opacity-70');
        $.ajax({
            url: BASEURL + '/device/updateWebhook',
            type: 'POST',
            data: { id: id, webhook_url: webhook_url },
            dataType: 'json',
            success(res) {
                btn.prop('disabled', false).removeClass('opacity-70');
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error() {
                btn.prop('disabled', false).removeClass('opacity-70');
                Swal.fire('Error', 'Gagal menyimpan Webhook URL.', 'error');
            }
        });
    };

    window.copyToken = function () {
        const token = $('#tokenValue').text();
        navigator.clipboard.writeText(token).then(() => {
            Swal.fire({ icon: 'success', title: 'Token disalin!', timer: 1000, showConfirmButton: false });
        });
    };

    window.doRegenerateToken = function () {
        Swal.fire({
            title: 'Regenerate Token?', text: 'Token lama akan tidak berlaku!', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Regenerate!'
        }).then(r => {
            if (!r.isConfirmed) return;
            const id = $('#tokenDeviceId').val();
            $.ajax({
                url: BASEURL + '/device/regenerateToken', type: 'POST', data: { id }, dataType: 'json',
                success(res) {
                    if (res.status === 'success') {
                        $('#tokenValue').text(res.token);
                        Swal.fire({ icon: 'success', title: 'Token baru dibuat!', timer: 1200, showConfirmButton: false });
                    }
                }
            });
        });
    };

    // ── AI Settings Modal ─────────────────────────────────────────────────
    window.openAiModal = function (deviceId, deviceName) {
        $('#aiDeviceId').val(deviceId);
        $('#aiModalDeviceName').text(deviceName);
        // Reset form
        document.getElementById('formAiSettings').reset();
        // Clear style chips
        document.querySelectorAll('.ai-style-chip').forEach(chip => {
            chip.classList.remove('border-purple-500','bg-purple-50','text-purple-700');
            chip.classList.add('border-gray-200','text-gray-600');
            chip.querySelector('.chip-check').classList.add('hidden');
            chip.querySelector('.ai-style-cb').checked = false;
        });
        // Clear style chips

        // Load data dari server
        $.ajax({
            url: BASEURL + '/device/aiSettings?device_id=' + deviceId, dataType: 'json',
            success(res) {
                // Populate servers dropdown
                const sel = $('#aiServerId');
                sel.empty().append('<option value="">— Auto-Switch (prioritas) —</option>');
                if (res.servers && res.servers.length) {
                    res.servers.forEach(s => {
                        sel.append(`<option value="${s.id}">[${s.is_active ? 'Aktif' : 'Nonaktif'}] ${s.label}</option>`);
                    });
                }

                // Populate models dropdown
                if (res.models && Object.keys(res.models).length) {
                    const modelSel = $('#aiModel');
                    modelSel.empty().append('<option value="auto">— Auto-Switch (prioritas) —</option>');
                    for (const [val, lbl] of Object.entries(res.models)) {
                        modelSel.append(`<option value="${val}">${lbl}</option>`);
                    }
                }

                if (res.settings) {
                    const s = res.settings;
                    $('#aiEnabled').prop('checked', s.ai_enabled == 1);
                    document.getElementById('aiExtraSettings').classList.toggle('hidden', s.ai_enabled != 1);
                    $('#aiSearchEnabled').prop('checked', s.search_enabled == 1);
                    $(`input[name="target_reply"][value="${s.target_reply || 'both'}"]`).prop('checked', true);
                    
                    let rMode = s.reply_mode || 'manual';
                    if(rMode === 'ai') rMode = 'auto'; // legacy conversion
                    $(`input[name="reply_mode"][value="${rMode}"]`).prop('checked', true);

                    $('#aiModel').val(s.model || 'llama3-8b-8192');
                    $('#aiServerId').val(s.ai_server_id || '');
                    $('#aiBrand').val(s.brand || '');
                    $('#aiNameInput').val(s.ai_name || '');
                    $('#aiLanguage').val(s.language || '');
                    $('#aiRules').val(s.rules || '');
                    $('#aiRules').val(s.rules || '');

                    // Restore styles
                    if (s.style) {
                        let styles = [];
                        try { styles = JSON.parse(s.style); } catch(e) {}
                        document.querySelectorAll('.ai-style-cb').forEach(cb => {
                            if (styles.includes(cb.value)) {
                                cb.checked = true;
                                const chip = cb.closest('.ai-style-chip');
                                chip.classList.remove('border-gray-200','text-gray-600');
                                chip.classList.add('border-purple-500','bg-purple-50','text-purple-700');
                                chip.querySelector('.chip-check').classList.remove('hidden');
                            }
                        });
                    }
                }
            }
        });

        openModal('aiModal');
    };

    // Style chip toggle
    document.querySelectorAll('.ai-style-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            const cb = this.querySelector('.ai-style-cb');
            const check = this.querySelector('.chip-check');
            cb.checked = !cb.checked;
            if (cb.checked) {
                this.classList.remove('border-gray-200','text-gray-600');
                this.classList.add('border-purple-500','bg-purple-50','text-purple-700');
                check.classList.remove('hidden');
            } else {
                this.classList.remove('border-purple-500','bg-purple-50','text-purple-700');
                this.classList.add('border-gray-200','text-gray-600');
                check.classList.add('hidden');
            }
        });
    });

    // Save AI Settings
    $('#formAiSettings').on('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        if (!$('#aiEnabled').is(':checked')) fd.delete('ai_enabled');
        fetch(BASEURL + '/device/aiSettings', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Tersimpan!', text: res.message, timer: 1500, showConfirmButton: false });
                    closeModal('aiModal');
                } else { Swal.fire('Gagal', res.message, 'error'); }
            });
    });

    // ── AI Data Modal ─────────────────────────────────────────────────────
    window.openAiDataModal = function (deviceId, deviceName) {
        $('#aiDataDeviceId').val(deviceId);
        $('#aiDataDeviceName').text(deviceName);
        cancelEditAiData();
        loadAiDataList(deviceId);
        openModal('aiDataModal');
    };

    window.closeAiDataModal = function () {
        closeModal('aiDataModal');
    };

    function loadAiDataList(deviceId) {
        const did = deviceId || $('#aiDataDeviceId').val();
        $.ajax({
            url: BASEURL + '/device/aiData?device_id=' + did, dataType: 'json',
            success(res) {
                const container = $('#aiDataList');
                if (!res.data || res.data.length === 0) {
                    container.html('<div class="text-center py-6 text-gray-400 text-sm"><svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7"/></svg>Belum ada data AI</div>');
                    return;
                }
                let html = '';
                res.data.forEach(item => {
                    const preview = item.content.length > 80 ? item.content.substring(0, 80) + '...' : item.content;
                    const charCount = item.content.length.toLocaleString();
                    html += `
                    <div class="bg-white border border-gray-100 rounded-xl p-4 flex items-start justify-between gap-3 hover:border-gray-200 transition" id="ai-data-item-${item.id}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 text-sm truncate">${escHtml(item.title)}</p>
                            <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">${escHtml(preview)}</p>
                            <span class="text-xs text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full mt-1 inline-block">${charCount} chars</span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button onclick="editAiDataItem(${item.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button onclick="deleteAiDataItem(${item.id})" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>`;
                });
                container.html(html);
            }
        });
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    // Character counter
    document.getElementById('aiDataContent').addEventListener('input', function () {
        const len = this.value.length;
        const el  = document.getElementById('aiDataCharCount');
        const quota = Math.ceil(len / 2000);
        el.textContent = `${len.toLocaleString()} / 50.000 characters · ~${quota} quota AI`;
        el.className = len > 50000 ? 'text-xs text-red-500 mt-1' : 'text-xs text-teal-600 mt-1';
    });

    window.submitAiData = function () {
        const deviceId = $('#aiDataDeviceId').val();
        const title    = $('#aiDataTitle').val().trim();
        const content  = $('#aiDataContent').val().trim();
        const editId   = $('#editAiDataId').val();

        if (!title || !content) { Swal.fire({ icon: 'warning', title: 'Isi semua field!', timer: 1200, showConfirmButton: false }); return; }
        if (content.length > 50000) { Swal.fire({ icon: 'error', title: 'Data terlalu panjang!', text: 'Maksimal 50.000 karakter.' }); return; }

        const url = editId ? BASEURL + '/device/aiDataEdit' : BASEURL + '/device/aiData';
        const fd  = new FormData();
        fd.append('device_id', deviceId);
        fd.append('title', title);
        fd.append('content', content);
        if (editId) fd.append('id', editId);

        fetch(url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
                    $('#aiDataTitle').val('');
                    $('#aiDataContent').val('');
                    document.getElementById('aiDataCharCount').textContent = 'max 50.000 characters. per 2000-2500 characters cost around 1 quota ai';
                    cancelEditAiData();
                    loadAiDataList(deviceId);
                } else { Swal.fire('Gagal', res.message, 'error'); }
            });
    };

    window.editAiDataItem = function (id) {
        const deviceId = $('#aiDataDeviceId').val();
        $.ajax({
            url: BASEURL + '/device/aiDataGet?id=' + id + '&device_id=' + deviceId, dataType: 'json',
            success(res) {
                if (res.status === 'success') {
                    $('#editAiDataId').val(res.data.id);
                    $('#aiDataTitle').val(res.data.title);
                    $('#aiDataContent').val(res.data.content);
                    $('#aiDataFormTitle').text('Edit Data');
                    $('#btnSubmitAiData').html('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Update');
                    $('#btnCancelAiData').removeClass('hidden');
                    document.getElementById('aiDataTitle').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    };

    window.deleteAiDataItem = function (id) {
        Swal.fire({
            title: 'Hapus Data?', text: 'Data ini akan dihapus permanen.', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Hapus!', cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            const deviceId = $('#aiDataDeviceId').val();
            const fd = new FormData();
            fd.append('id', id);
            fd.append('device_id', deviceId);
            fetch(BASEURL + '/device/aiDataDelete', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        $('#ai-data-item-' + id).remove();
                        Swal.fire({ icon: 'success', title: 'Dihapus!', timer: 1000, showConfirmButton: false });
                        if ($('#aiDataList .bg-white').length === 0) {
                            $('#aiDataList').html('<div class="text-center py-6 text-gray-400 text-sm">Belum ada data AI</div>');
                        }
                    }
                });
        });
    };

    window.cancelEditAiData = function () {
        $('#editAiDataId').val('');
        $('#aiDataTitle').val('');
        $('#aiDataContent').val('');
        $('#aiDataFormTitle').text('Add Data');
        $('#btnSubmitAiData').html('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add');
        $('#btnCancelAiData').addClass('hidden');
    };

    // ── QR Modal & Pairing Code ──────────────────────────────────────────
    let activeConnectTab = 'qr';

    function setQRStatus(type, text) {
        const map = { 
            info: 'bg-emerald-50 text-emerald-700 border border-emerald-200/70', 
            success: 'bg-green-50 text-green-700 border border-green-200', 
            error: 'bg-red-50 text-red-700 border border-red-200', 
            warning: 'bg-amber-50 text-amber-700 border border-amber-200' 
        };
        $('#qrStatusBanner').attr('class', 'mb-4 px-4 py-2.5 rounded-2xl text-xs font-semibold flex items-center justify-center gap-2 shadow-sm ' + (map[type] || map.info));
        $('#qrStatusText').text(text);
    }

    window.switchConnectTab = function (tab) {
        activeConnectTab = tab;
        if (tab === 'qr') {
            $('#tabBtnQR').attr('class', 'flex-1 py-2 px-3 text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5 bg-white text-emerald-700 shadow-sm');
            $('#tabBtnCode').attr('class', 'flex-1 py-2 px-3 text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100');
            $('#tabContentQR').removeClass('hidden').addClass('flex');
            $('#tabContentCode').addClass('hidden').removeClass('flex');
            if ($('#qrImage').attr('src')) {
                setQRStatus('info', '📱 Arahkan kamera WhatsApp ke QR Code di atas');
            }
        } else {
            $('#tabBtnCode').attr('class', 'flex-1 py-2 px-3 text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5 bg-white text-emerald-700 shadow-sm');
            $('#tabBtnQR').attr('class', 'flex-1 py-2 px-3 text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100');
            $('#tabContentCode').removeClass('hidden').addClass('flex');
            $('#tabContentQR').addClass('hidden').removeClass('flex');
            if ($('#pairingCodeBox').is(':visible')) {
                setQRStatus('info', '⏳ Masukkan kode di atas pada WhatsApp Business');
            } else {
                setQRStatus('info', '💡 Masukkan nomor WhatsApp Business Anda di bawah');
            }
        }
    };

    window.scanQR = function (sessionId, forceNew = false) {
        currentSession = sessionId;
        switchConnectTab('qr');
        setQRStatus('info', '⏳ Memulai koneksi WhatsApp...');
        $('#qrSpinner').removeClass('hidden');
        $('#qrContainer').addClass('hidden').removeClass('flex');
        $('#qrImage').attr('src', '');
        $('#qrSuccess').addClass('hidden').removeClass('flex');
        $('#qrInstructions').removeClass('hidden');
        $('#pairingCodeBox').addClass('hidden').removeClass('flex');
        $('#inputPairingPhone').val('');
        openModal('qrModal');

        $.ajax({
            url: BASEURL + '/device/startSession',
            type: 'POST',
            data: { session_id: sessionId, force_new: forceNew ? 1 : 0 },
            dataType: 'json',
            success(res) {
                if (res.status === 'engine_offline') {
                    setQRStatus('error', '🔴 ' + res.message);
                    $('#qrSpinner').addClass('hidden');
                    return;
                }
                if (res.status === 'already_connected') {
                    showQRConnected(res.phone);
                    return;
                }
                if (res.status === 'qr' && res.qr) {
                    $('#qrSpinner').addClass('hidden');
                    $('#qrContainer').removeClass('hidden').addClass('flex');
                    $('#qrImage').attr('src', res.qr);
                    setQRStatus('info', '📱 Arahkan kamera WhatsApp ke QR Code di atas');
                } else {
                    setQRStatus('info', '⏳ Menyiapkan QR Code definisi tinggi...');
                }
                startQRPoll(sessionId);
            },
            error() {
                setQRStatus('error', '❌ Tidak dapat menghubungi engine.');
                $('#qrSpinner').addClass('hidden');
            }
        });
    };

    window.refreshQR = function () {
        if (!currentSession) return;
        setQRStatus('info', '⏳ Memperbarui QR Code baru...');
        $('#qrContainer').addClass('hidden').removeClass('flex');
        $('#qrSpinner').removeClass('hidden');
        $.ajax({
            url: BASEURL + '/device/startSession',
            type: 'POST',
            data: { session_id: currentSession, force_new: 1 },
            dataType: 'json',
            success(res) {
                startQRPoll(currentSession);
            },
            error() {
                startQRPoll(currentSession);
            }
        });
    };

    window.doRequestPairingCode = function () {
        if (!currentSession) return;
        const phone = $('#inputPairingPhone').val().trim();
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'Nomor Belum Diisi', text: 'Masukkan nomor WhatsApp Anda!', timer: 1500, showConfirmButton: false });
            return;
        }

        $('#btnGetPairingText').addClass('hidden');
        $('#btnGetPairingSpinner').removeClass('hidden');
        $('#btnGetPairingCode').prop('disabled', true).addClass('opacity-75');
        setQRStatus('info', '⏳ Meminta kode pairing resmi dari WhatsApp...');

        $.ajax({
            url: BASEURL + '/device/requestPairingCode',
            type: 'POST',
            data: { session_id: currentSession, phone_number: phone },
            dataType: 'json',
            success(res) {
                $('#btnGetPairingText').removeClass('hidden');
                $('#btnGetPairingSpinner').addClass('hidden');
                $('#btnGetPairingCode').prop('disabled', false).removeClass('opacity-75');

                if (res.status === 'success' && res.pairingCode) {
                    $('#displayPairingCode').text(res.pairingCode);
                    $('#pairingCodeBox').removeClass('hidden').addClass('flex');
                    setQRStatus('info', '📱 Masukkan kode ' + res.pairingCode + ' di WhatsApp Business');
                    startQRPoll(currentSession);
                } else if (res.status === 'already_connected') {
                    showQRConnected(res.phone);
                } else {
                    setQRStatus('error', '❌ ' + (res.message || 'Gagal mendapatkan kode pairing'));
                    Swal.fire('Gagal', res.message || 'Gagal meminta kode pairing.', 'error');
                }
            },
            error() {
                $('#btnGetPairingText').removeClass('hidden');
                $('#btnGetPairingSpinner').addClass('hidden');
                $('#btnGetPairingCode').prop('disabled', false).removeClass('opacity-75');
                setQRStatus('error', '❌ Gagal menghubungi server engine.');
            }
        });
    };

    window.copyPairingCode = function () {
        const code = $('#displayPairingCode').text().trim();
        if (!code) return;
        navigator.clipboard.writeText(code.replace(/[^A-Za-z0-9]/g, '')).then(() => {
            Swal.fire({ icon: 'success', title: 'Kode Disalin!', text: 'Kode ' + code + ' siap ditempel di WhatsApp', timer: 1200, showConfirmButton: false });
        }).catch(() => {
            Swal.fire({ icon: 'info', title: 'Kode: ' + code, timer: 1500 });
        });
    };

    function showQRConnected(phone) {
        clearInterval(qrPollTimer);
        $('#qrSpinner').addClass('hidden');
        $('#tabContentQR').addClass('hidden').removeClass('flex');
        $('#tabContentCode').addClass('hidden').removeClass('flex');
        $('#qrSuccess').removeClass('hidden').addClass('flex').css('display', 'flex');
        $('#qrConnectedPhone').text(phone ? 'Nomor: +' + phone : 'WhatsApp Aktif');
        setQRStatus('success', '🎉 WhatsApp Berhasil Terhubung!');

        // Update baris tabel secara instan
        if (currentSession) {
            const row = $('[data-session-id="' + currentSession + '"]');
            if (row.length) {
                row.find('.dev-status-badge').html(`
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold border bg-emerald-50 border-emerald-200 text-emerald-700">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                        Terhubung
                    </span>
                `);
                if (phone) {
                    row.find('.dev-phone-text').text('+' + phone);
                }
            }
        }

        setTimeout(() => {
            closeQRModal();
            reloadPage();
        }, 2200);
    }

    function startQRPoll(sessionId) {
        let attempts = 0;
        clearInterval(qrPollTimer);
        qrPollTimer = setInterval(function () {
            attempts++;
            if (attempts > 120) { // ~3 menit
                clearInterval(qrPollTimer);
                setQRStatus('warning', '⏰ Waktu sesi habis. Klik Perbarui untuk mencoba lagi.');
                return;
            }
            $.ajax({
                url: BASEURL + '/device/getQR',
                data: { session_id: sessionId },
                dataType: 'json',
                success(res) {
                    switch (res.status) {
                        case 'qr_ready':
                            $('#qrSpinner').addClass('hidden');
                            if (activeConnectTab === 'qr') {
                                $('#qrContainer').removeClass('hidden').addClass('flex');
                            }
                            if ($('#qrImage').attr('src') !== res.qr) {
                                $('#qrImage').attr('src', res.qr);
                            }
                            if (activeConnectTab === 'qr') {
                                setQRStatus('info', '📱 Arahkan kamera WhatsApp ke QR Code di atas'); 
                            }
                            break;
                        case 'connected':
                            showQRConnected(res.phone);
                            break;
                        case 'authenticated': 
                            setQRStatus('info', '🔐 Kunci terverifikasi! Sedang menyinkronkan data...'); 
                            $('#qrContainer').addClass('hidden').removeClass('flex'); 
                            $('#qrSpinner').removeClass('hidden'); 
                            break;
                        case 'initializing':
                        case 'starting':
                            if (activeConnectTab === 'qr') {
                                setQRStatus('info', '⚙️ Menginisialisasi sesi WhatsApp...');
                            }
                            break;
                        case 'disconnected':
                            setQRStatus('warning', '⚠️ Sesi terputus atau QR kadaluwarsa. Klik Perbarui.');
                            break;
                        case 'engine_offline':
                            clearInterval(qrPollTimer);
                            setQRStatus('error', '🔴 Engine offline.');
                            break;
                    }
                }
            });
        }, 1500); // 1.5 detik interval agar sangat responsif
    }

    window.closeQRModal = function () {
        clearInterval(qrPollTimer);
        currentSession = null;
        closeModal('qrModal');
    };

})();
</script>
