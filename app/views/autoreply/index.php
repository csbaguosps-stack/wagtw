<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Autoreply</h2>
        <p class="text-sm text-gray-500 mt-1">Balas pesan pelanggan secara otomatis 24/7 menggunakan kecerdasan sistem.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <button id="btnAddAR" onclick="openModal('addARModal')" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium rounded-xl px-5 py-2.5 transition-all transform hover:-translate-y-0.5 shadow-md flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Buat Autoreply
        </button>
    </div>
</div>

<div class="mb-6 flex space-x-1 bg-gray-100 p-1 rounded-xl w-full max-w-md">
    <button onclick="switchTab('keywords')" id="btn-tab-keywords" class="flex-1 py-2 px-4 rounded-lg font-bold text-sm transition-all bg-white text-gray-800 shadow-sm">Aturan Keyword</button>
    <button onclick="switchTab('business')" id="btn-tab-business" class="flex-1 py-2 px-4 rounded-lg font-medium text-sm transition-all text-gray-500 hover:text-gray-700">Pengaturan Bisnis</button>
</div>

<!-- Autoreply Cards Grid -->
<div id="tab-keywords">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if(empty($data['autoreplies'])): ?>
    <div class="col-span-full">
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500 flex flex-col items-center justify-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-700 mb-1">Belum ada Autoreply</h3>
            <p class="text-sm text-gray-400 mb-6">Anda belum membuat aturan balasan otomatis apapun.</p>
            <button onclick="openModal('addARModal')" class="text-green-600 font-medium hover:text-green-700 hover:underline">Buat aturan pertama Anda</button>
        </div>
    </div>
    <?php else: ?>
        <?php foreach($data['autoreplies'] as $ar): 
            $isGlobal = empty($ar['device_id']);
        ?>
        <div id="ar-card-<?= $ar['id'] ?>" class="bg-white rounded-2xl p-5 shadow-sm border <?= $isGlobal ? 'border-indigo-100 bg-indigo-50/10' : 'border-gray-100' ?> hover:shadow-md transition duration-300 flex flex-col">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-2">
                    <?php if($isGlobal): ?>
                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-md text-[10px] font-bold tracking-wider uppercase">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            GLOBAL (Semua Device)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-[10px] font-bold tracking-wider uppercase border border-gray-200">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Device: <?= htmlspecialchars($ar['device_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <!-- Status Badge (Active/Inactive not dynamically handled yet, fallback to active) -->
                <span class="w-2.5 h-2.5 bg-green-500 rounded-full shadow-sm animate-pulse"></span>
            </div>
            
            <div class="mb-3">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-widest mb-1">Trigger (Keyword)</p>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="bg-green-50 text-green-700 font-mono font-semibold px-3 py-1.5 border border-green-200 rounded-lg text-sm shadow-sm">
                        "<?= htmlspecialchars($ar['trigger_word']); ?>"
                    </span>
                    <span class="text-[10px] bg-gray-100 text-gray-500 border border-gray-200 px-2 py-1 rounded-md" title="Match Type">
                        <?= strtoupper($ar['match_type']); ?>
                    </span>
                    <?php 
                    $target = $ar['target_reply'] ?? 'both';
                    if($target === 'private'): ?>
                        <span class="text-[10px] bg-blue-50 text-blue-700 border border-blue-200 px-2 py-1 rounded-md font-medium" title="Target: Chat Pribadi Saja">
                            👤 Pribadi
                        </span>
                    <?php elseif($target === 'group'): ?>
                        <span class="text-[10px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-1 rounded-md font-medium" title="Target: Grup Saja">
                            👥 Grup
                        </span>
                    <?php else: ?>
                        <span class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-md font-medium" title="Target: Pribadi & Grup">
                            🌐 Pribadi & Grup
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex-1 bg-gray-50 rounded-xl p-3 border border-gray-100/50 mb-4 relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-400"></div>
                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-1 ml-1.5">Isi Balasan</p>
                <p class="text-sm text-gray-700 ml-1.5 leading-relaxed truncate-2-lines line-clamp-3">
                    <?= nl2br(htmlspecialchars($ar['reply_text'])); ?>
                </p>
            </div>

            <div class="mt-auto border-t border-gray-100 pt-3 flex justify-between items-center">
                <div class="flex gap-2">
                    <span class="flex items-center text-xs text-gray-500 bg-gray-50 border border-gray-200 px-2 py-1 rounded-md" title="Membaca Pesan">
                        <svg class="w-3 h-3 mr-1 <?= $ar['set_read'] ? 'text-blue-500' : 'text-gray-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7M5 13l4 4L19 7m-9 4l-4 4"/></svg>
                        Read
                    </span>
                    <span class="flex items-center text-xs text-gray-500 bg-gray-50 border border-gray-200 px-2 py-1 rounded-md" title="Status Mengetik">
                        <svg class="w-3 h-3 mr-1 <?= $ar['set_typing'] ? 'text-green-500' : 'text-gray-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <?= $ar['delay'] ?>s
                    </span>
                </div>
                <button onclick="deleteAR(<?= $ar['id'] ?>)" class="p-2 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Rule">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Tab: Business Settings -->
<div id="tab-business" class="hidden">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-gray-100">
            <div>
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    WhatsApp Business Settings
                </h3>
                <p class="text-sm text-gray-500 mt-1">Konfigurasi pesan sambutan (Welcome) dan pesan saat Anda tidak di tempat (Away).</p>
            </div>
            
            <div class="mt-4 md:mt-0 md:min-w-[300px]">
                <label class="block mb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Pilih Device:</label>
                <select id="businessDeviceSelect" onchange="loadBusinessSettings()" class="bg-gray-50 border text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-2.5 shadow-sm border-gray-200 font-medium">
                    <option value="" disabled selected>-- Pilih Device --</option>
                    <?php foreach($data['devices'] as $dev): ?>
                        <option value="<?= $dev['id'] ?>">📱 <?= $dev['name'] ?> (+<?= $dev['phone'] ?? 'Offline' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <form id="formBusinessSettings" onsubmit="saveBusinessSettings(event)" class="hidden">
            <!-- Pesan Sambutan (Welcome Message) -->
            <div class="mb-8 bg-blue-50/50 rounded-2xl p-6 border border-blue-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-bold text-blue-900">Pesan Sambutan (Welcome Message)</h4>
                        <p class="text-xs text-blue-600 mt-0.5">Dikirim otomatis kepada pelanggan yang baru pertama kali mengirim pesan atau tidak aktif > 1 hari.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="welcome_enabled" id="welcome_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div>
                    <textarea name="welcome_message" id="welcome_message" rows="3" class="bg-white border border-blue-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm" placeholder="Halo! Terima kasih telah menghubungi kami. Ada yang bisa kami bantu?"></textarea>
                </div>
            </div>

            <!-- Pesan Tidak Di Tempat (Away Message) -->
            <div class="mb-8 bg-orange-50/50 rounded-2xl p-6 border border-orange-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-bold text-orange-900">Pesan Di Luar Jam Kerja (Away Message)</h4>
                        <p class="text-xs text-orange-600 mt-0.5">Dikirim saat pesan masuk di luar jadwal kerja Anda.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="away_enabled" id="away_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>
                <div class="mb-6">
                    <textarea name="away_message" id="away_message" rows="3" class="bg-white border border-orange-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 block w-full p-3 shadow-sm" placeholder="Maaf, saat ini kami sedang berada di luar jam operasional. Kami akan membalas pesan Anda sesegera mungkin."></textarea>
                </div>

                <div class="border-t border-orange-200/50 pt-5">
                    <h5 class="text-sm font-bold text-orange-900 mb-4">Jadwal Jam Kerja (Work Hours)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="workHoursContainer">
                        <?php 
                        $days = ['mon'=>'Senin', 'tue'=>'Selasa', 'wed'=>'Rabu', 'thu'=>'Kamis', 'fri'=>'Jumat', 'sat'=>'Sabtu', 'sun'=>'Minggu'];
                        foreach($days as $key => $dayName):
                        ?>
                        <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-orange-100 shadow-sm work-day-row">
                            <div class="flex items-center gap-3 w-1/3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer day-toggle" data-day="<?= $key ?>" checked>
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                                </label>
                                <span class="text-sm font-bold text-gray-700 w-16"><?= $dayName ?></span>
                            </div>
                            <div class="flex items-center gap-2 time-inputs">
                                <input type="time" class="time-start bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-lg focus:ring-orange-500 focus:border-orange-500 block px-2.5 py-1.5" value="08:00">
                                <span class="text-gray-400 text-xs font-medium">s/d</span>
                                <input type="time" class="time-end bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-lg focus:ring-orange-500 focus:border-orange-500 block px-2.5 py-1.5" value="17:00">
                            </div>
                            <span class="text-xs font-bold text-rose-500 hidden off-badge">LIBUR</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" id="btnSaveBusiness" class="px-6 py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all shadow-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Simpan Pengaturan Bisnis
                </button>
            </div>
        </form>

        <div id="businessSelectPrompt" class="text-center py-16">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <h4 class="text-lg font-bold text-gray-800">Pilih Device</h4>
            <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Silakan pilih device di sudut kanan atas terlebih dahulu untuk memuat dan mengubah pengaturan bisnisnya.</p>
        </div>
    </div>
</div>

<!-- Modal Tambah Autoreply -->
<div id="addARModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 sm:p-6">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-auto transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh] sm:max-h-[85vh]" id="modalContent">
        <form id="formAddAR" class="flex flex-col overflow-hidden h-full">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-2xl shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Buat Rule Autoreply Baru</h3>
                    <p class="text-xs text-gray-500 mt-1">Konfigurasi balasan otomatis saat kustomer mengirim pesan tertentu.</p>
                </div>
                <button type="button" onclick="closeModal('addARModal')" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Target Device -->
                <div class="mb-5">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Aplikasikan ke Device</label>
                    <select name="device_id" class="bg-white border text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3 shadow-sm border-gray-200">
                        <!-- Pilihan Global -->
                        <option value="" class="font-bold text-indigo-600">🌐 SEMUA DEVICE (Global Rule)</option>
                        
                        <optgroup label="Spesifik 1 Device:" class="text-gray-400 text-xs">
                        <?php foreach($data['devices'] as $dev): ?>
                            <option value="<?= $dev['id'] ?>" class="text-gray-800 text-sm">📱 <?= $dev['name'] ?> (+<?= $dev['phone'] ?? 'Offline' ?>)</option>
                        <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Target Balasan: Pribadi / Grup / Keduanya -->
                <div class="mb-5 border-t border-gray-100 pt-5">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Target Balasan Pesan</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative flex flex-col items-center justify-center p-3 text-center border-2 border-green-500 bg-green-50/40 rounded-xl cursor-pointer hover:bg-green-50/70 transition-all target-option" data-val="both">
                            <input type="radio" name="target_reply" value="both" class="sr-only" checked>
                            <svg class="w-5 h-5 mb-1 text-green-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="text-xs font-bold text-gray-800">Keduanya</span>
                            <span class="text-[10px] text-gray-500">Pribadi & Grup</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center p-3 text-center border-2 border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all target-option" data-val="private">
                            <input type="radio" name="target_reply" value="private" class="sr-only">
                            <svg class="w-5 h-5 mb-1 text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="text-xs font-bold text-gray-800">Pribadi Saja</span>
                            <span class="text-[10px] text-gray-500">Chat Japri</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center p-3 text-center border-2 border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all target-option" data-val="group">
                            <input type="radio" name="target_reply" value="group" class="sr-only">
                            <svg class="w-5 h-5 mb-1 text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span class="text-xs font-bold text-gray-800">Grup Saja</span>
                            <span class="text-[10px] text-gray-500">Grup WA</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5 border-t border-gray-100 pt-5">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Keyword (Kata Kunci)</label>
                        <input type="text" name="trigger_word" class="bg-gray-50 border text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3 shadow-sm border-gray-200" required placeholder="Cth: Halo, Harga, Pesan">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Pola Kecocokan</label>
                        <select name="match_type" class="bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3 shadow-sm">
                            <option value="contains">Terdapat kata (Contains)</option>
                            <option value="exact">Sama Persis (Exact Match)</option>
                            <option value="startswith">Berawal Dengan (Starts With)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Teks Balasan (Kirim Teks)</label>
                    <textarea name="reply_text" rows="4" class="bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3 shadow-sm" required placeholder="Tulis pesan otomatis di sini..."></textarea>
                </div>

                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100 mb-2">
                    <h4 class="text-sm font-bold text-yellow-800 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Pengaturan Lanjutan
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-yellow-200/50 pt-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-yellow-800">Tandai Dibaca</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                              <input type="checkbox" name="set_read" class="sr-only peer" checked>
                              <div class="w-9 h-5 bg-yellow-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-yellow-600 shadow-inner"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-yellow-800">Status Mengetik</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                              <input type="checkbox" name="set_typing" class="sr-only peer" checked>
                              <div class="w-9 h-5 bg-yellow-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-yellow-600 shadow-inner"></div>
                            </label>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-yellow-800 flex-shrink-0">Jeda Waktu</span>
                                <div class="relative w-full">
                                    <input type="number" name="delay" value="2" class="bg-white border-none text-yellow-900 rounded-lg w-full p-2 text-xs font-bold text-center pr-8 shadow-sm ring-1 ring-yellow-300 focus:ring-2 focus:ring-yellow-500" required>
                                    <span class="absolute right-2 top-1.5 text-xs text-yellow-600 font-bold">dtk</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50 rounded-b-2xl shrink-0">
                <button type="button" onclick="closeModal('addARModal')" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 hover:text-gray-900 transition">Batal</button>
                <button type="submit" id="btnSaveAR" class="px-5 py-2.5 bg-green-500 text-white rounded-xl font-medium hover:bg-green-600 transition shadow-sm hover:shadow">Simpan Aturan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { 
        $('#' + id).removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    }
    
    function closeModal(id) { 
        $('#modalContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#' + id).removeClass('flex').addClass('hidden');
        }, 300);
    }

    $(document).on('change', 'input[name="target_reply"]', function() {
        $('.target-option').removeClass('border-green-500 bg-green-50/40 border-blue-500 bg-blue-50/40 border-amber-500 bg-amber-50/40')
                           .addClass('border-gray-200 bg-white');
        $('.target-option svg').removeClass('text-green-600 text-blue-600 text-amber-600').addClass('text-gray-500');
        
        const val = $(this).val();
        const parent = $(this).closest('.target-option');
        parent.removeClass('border-gray-200 bg-white');
        if (val === 'both') {
            parent.addClass('border-green-500 bg-green-50/40');
            parent.find('svg').removeClass('text-gray-500').addClass('text-green-600');
        } else if (val === 'private') {
            parent.addClass('border-blue-500 bg-blue-50/40');
            parent.find('svg').removeClass('text-gray-500').addClass('text-blue-600');
        } else if (val === 'group') {
            parent.addClass('border-amber-500 bg-amber-50/40');
            parent.find('svg').removeClass('text-gray-500').addClass('text-amber-600');
        }
    });

    $('#formAddAR').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSaveAR');
        btn.prop('disabled', true).html('<span class="flex items-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...</span>');

        $.ajax({
            url: BASEURL + '/autoreply/add', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.status == 'success') {
                    closeModal('addARModal');
                    if (document.getElementById('formAddAR')) {
                        document.getElementById('formAddAR').reset();
                        $('input[name="target_reply"][value="both"]').prop('checked', true).trigger('change');
                    }
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1200, showConfirmButton: false }).then(()=>{
                        if (typeof window.loadPage === 'function') {
                            window.loadPage(BASEURL + '/autoreply', false);
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    btn.prop('disabled', false).text('Simpan Aturan');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error Sistem', 'Terjadi kesalahan pada server: ' + error + '\n' + xhr.responseText.substring(0, 100), 'error');
                btn.prop('disabled', false).text('Simpan Aturan');
            }
        });
    });

    function deleteAR(id) {
        Swal.fire({
            title: 'Hapus Rule?',
            text: 'Aturan balasan ini akan dihapus secara permanen.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#ar-card-' + id).css({ opacity: 0.4, pointerEvents: 'none' });
                $.ajax({
                    url: BASEURL + '/autoreply/delete', type: 'POST', data: { id: id }, dataType: 'json',
                    success: function(res) {
                        if(res.status == 'success') {
                            $('#ar-card-' + id).fadeOut(300);
                            Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message, timer: 1000, showConfirmButton: false }).then(()=>{
                                if (typeof window.loadPage === 'function') {
                                    window.loadPage(BASEURL + '/autoreply', false);
                                } else {
                                    location.reload();
                                }
                            });
                        } else {
                            $('#ar-card-' + id).css({ opacity: 1, pointerEvents: 'auto' });
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    },
                    error: function() {
                        $('#ar-card-' + id).css({ opacity: 1, pointerEvents: 'auto' });
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    }
                });
            }
        });
    }

    // --- Business Settings Logic ---
    function switchTab(tabId) {
        if(tabId === 'keywords') {
            $('#tab-keywords').removeClass('hidden');
            $('#tab-business').addClass('hidden');
            $('#btnAddAR').removeClass('hidden');
            
            $('#btn-tab-keywords').removeClass('text-gray-500 hover:text-gray-700').addClass('bg-white text-gray-800 font-bold shadow-sm');
            $('#btn-tab-business').removeClass('bg-white text-gray-800 font-bold shadow-sm').addClass('text-gray-500 hover:text-gray-700');
        } else {
            $('#tab-business').removeClass('hidden');
            $('#tab-keywords').addClass('hidden');
            $('#btnAddAR').addClass('hidden');

            $('#btn-tab-business').removeClass('text-gray-500 hover:text-gray-700').addClass('bg-white text-gray-800 font-bold shadow-sm');
            $('#btn-tab-keywords').removeClass('bg-white text-gray-800 font-bold shadow-sm').addClass('text-gray-500 hover:text-gray-700');
        }
    }

    // Setup day toggles
    $('.day-toggle').on('change', function() {
        const row = $(this).closest('.work-day-row');
        if($(this).is(':checked')) {
            row.find('.time-inputs').removeClass('hidden').addClass('flex');
            row.find('.off-badge').addClass('hidden');
        } else {
            row.find('.time-inputs').removeClass('flex').addClass('hidden');
            row.find('.off-badge').removeClass('hidden');
        }
    });

    function loadBusinessSettings() {
        const deviceId = $('#businessDeviceSelect').val();
        if(!deviceId) return;

        $('#businessSelectPrompt').addClass('hidden');
        $('#formBusinessSettings').removeClass('hidden');
        
        Swal.fire({title: 'Memuat...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});

        $.get(BASEURL + '/autoreply/get_business_settings?device_id=' + deviceId, function(res) {
            Swal.close();
            if(res.status === 'success') {
                const d = res.data;
                $('#welcome_enabled').prop('checked', d.welcome_enabled == 1);
                $('#welcome_message').val(d.welcome_message || '');
                $('#away_enabled').prop('checked', d.away_enabled == 1);
                $('#away_message').val(d.away_message || '');

                if(d.work_hours) {
                    try {
                        const wh = JSON.parse(d.work_hours);
                        Object.keys(wh).forEach(day => {
                            const toggle = $(`.day-toggle[data-day="${day}"]`);
                            const row = toggle.closest('.work-day-row');
                            
                            toggle.prop('checked', !wh[day].is_off);
                            row.find('.time-start').val(wh[day].start);
                            row.find('.time-end').val(wh[day].end);
                            
                            if(wh[day].is_off) {
                                row.find('.time-inputs').removeClass('flex').addClass('hidden');
                                row.find('.off-badge').removeClass('hidden');
                            } else {
                                row.find('.time-inputs').removeClass('hidden').addClass('flex');
                                row.find('.off-badge').addClass('hidden');
                            }
                        });
                    } catch(e) { console.error('Gagal parse JSON jadwal', e); }
                }
            }
        });
    }

    function saveBusinessSettings(e) {
        e.preventDefault();
        const deviceId = $('#businessDeviceSelect').val();
        if(!deviceId) return;

        // Kumpulkan data jadwal kerja
        let workHours = {};
        $('.day-toggle').each(function() {
            const day = $(this).data('day');
            const row = $(this).closest('.work-day-row');
            workHours[day] = {
                is_off: !$(this).is(':checked'),
                start: row.find('.time-start').val(),
                end: row.find('.time-end').val()
            };
        });

        const formData = {
            device_id: deviceId,
            welcome_enabled: $('#welcome_enabled').is(':checked') ? 1 : 0,
            welcome_message: $('#welcome_message').val(),
            away_enabled: $('#away_enabled').is(':checked') ? 1 : 0,
            away_message: $('#away_message').val(),
            work_hours: JSON.stringify(workHours)
        };

        const btn = $('#btnSaveBusiness');
        btn.prop('disabled', true).html('<span class="flex items-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...</span>');

        $.ajax({
            url: BASEURL + '/autoreply/save_business_settings', type: 'POST', data: formData, dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Simpan Pengaturan Bisnis');
            }
        });
    }
</script>
