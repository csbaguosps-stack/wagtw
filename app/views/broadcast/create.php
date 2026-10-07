<!-- SheetJS for Excel Reading -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<div class="space-y-5 max-w-7xl mx-auto mt-2 px-2.5 sm:px-4 lg:px-6 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <button type="button" onclick="if(typeof loadPage === 'function'){ loadPage(BASEURL + '/broadcast'); } else { window.location.href = BASEURL + '/broadcast'; }" class="bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 p-2.5 rounded-xl shadow-xs transition cursor-pointer" title="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </button>
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Buat Broadcast Baru</h2>
                <p class="text-gray-500 text-xs sm:text-sm">Kirim pesan massal cerdas anti-banned dengan pratinjau WhatsApp real-time.</p>
            </div>
        </div>

        <!-- Mobile Quick Preview Link -->
        <a href="#section-wa-preview" class="lg:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition shadow-2xs">
            <span>📱</span>
            <span>Lihat Preview WhatsApp</span>
        </a>
    </div>

    <!-- Form Broadcast -->
    <form id="form-broadcast" class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 items-start">
            
            <!-- Kolom Kiri: Form Konfigurasi (7 Cols di Desktop) -->
            <div class="lg:col-span-7 space-y-4 sm:space-y-5">
                
                <!-- 1. Info Dasar -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-sm border border-gray-100">
                    <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-3.5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Info Dasar</span>
                    </h3>
                    
                    <div class="space-y-3.5">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">Pilih Device Pengirim</label>
                            <select name="device_id" required class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none text-xs sm:text-sm transition appearance-none cursor-pointer">
                                <option value="">-- Pilih Device --</option>
                                <?php foreach ($data['devices'] as $d): ?>
                                    <option value="<?= $d['id'] ?>">📱 <?= htmlspecialchars($d['name']) ?> (+<?= htmlspecialchars($d['phone']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">Nama Campaign</label>
                            <input type="text" name="name" required placeholder="Misal: Promo Merdeka 2024 🎉" class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none text-xs sm:text-sm transition">
                        </div>
                    </div>
                </div>

                <!-- 2. Target Nomor -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-sm border border-gray-100 relative">
                    <div class="flex items-center justify-between mb-3.5 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> 
                                <span>Target Nomor</span>
                            </h3>
                            <span id="targetCounterBadge" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold whitespace-nowrap">0 Target</span>
                        </div>
                        
                        <!-- Direct Action Buttons: Download Contoh Excel & Import Excel/CSV -->
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                            <!-- Download Contoh Excel (.xlsx) -->
                            <a href="<?= BASEURL ?>/broadcast/download_template/xlsx" download="contoh_broadcast_target.xlsx" id="btn-download-contoh" class="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Download File Contoh Excel (.xlsx)">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Contoh Excel</span>
                            </a>
                            
                            <!-- Download Contoh CSV -->
                            <a href="<?= BASEURL ?>/broadcast/download_template/csv" download="contoh_broadcast_target.csv" id="btn-download-csv" class="bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200 px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer" title="Download Format CSV (.csv)">
                                <span>.CSV</span>
                            </a>

                            <!-- Import Excel Button -->
                            <input type="file" id="excel-upload" accept=".xlsx, .xls, .csv" class="hidden">
                            <button type="button" id="btn-import-excel" onclick="document.getElementById('excel-upload').click()" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Import File</span>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <textarea id="targetTextarea" name="targets" oninput="window.updateTargetCounter(); window.updateWhatsAppPreview();" onkeyup="window.updateTargetCounter(); window.updateWhatsAppPreview();" required rows="4" placeholder="08123xxx, Bro Budi&#10;08571xxx, Mbak Siti" class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-300 focus:border-green-400 outline-none text-xs sm:text-sm transition font-mono leading-relaxed resize-y"></textarea>
                        <div class="flex items-center justify-between flex-wrap gap-2 text-[11px] sm:text-xs text-gray-500 mt-1.5 leading-relaxed">
                            <p>
                                Format: <b>Nomor, Nama</b> per baris (Nama opsional).
                            </p>
                            <span class="text-slate-400">Contoh: <code>081234567890, Budi</code></span>
                        </div>
                    </div>
                </div>

                <!-- 3. Konten Pesan -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            <span>Konten Pesan</span>
                        </h3>
                    </div>

                    <!-- Toolbar Pintasan Tag, Format WhatsApp & Emoji Picker -->
                    <div class="space-y-1.5 mb-2.5 p-2 bg-slate-50/90 border border-slate-200/80 rounded-xl relative">
                        <!-- Baris 1: Tag Sisipan & Format Teks -->
                        <div class="flex items-center gap-1 sm:gap-1.5 flex-wrap">
                            <span class="text-[10px] font-bold text-slate-400 mr-0.5 uppercase tracking-wider">Sisipkan:</span>
                            <button type="button" onclick="window.insertMessageTag('[Name]')" class="btn-msg-tag px-2.5 py-1 rounded-lg bg-blue-100 hover:bg-blue-200 active:bg-blue-300 text-blue-800 text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer select-none" title="Sebut Nama Otomatis">
                                <span>+ [Name]</span>
                            </button>
                            <button type="button" onclick="window.insertMessageTag('{Hai|Halo|Salam}')" class="btn-msg-tag px-2.5 py-1 rounded-lg bg-indigo-100 hover:bg-indigo-200 active:bg-indigo-300 text-indigo-800 text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer select-none" title="Acak Kata Spintax">
                                <span>+ Spintax</span>
                            </button>
                            
                            <span class="text-slate-300 mx-0.5 hidden sm:inline">|</span>
                            
                            <button type="button" onclick="window.wrapMessageFormat('*')" class="btn-msg-fmt px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer select-none" title="Tebal (*teks*)">
                                <b>B</b>
                            </button>
                            <button type="button" onclick="window.wrapMessageFormat('_')" class="btn-msg-fmt px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 text-xs font-semibold italic transition cursor-pointer select-none" title="Miring (_teks_)">
                                <i>I</i>
                            </button>
                            <button type="button" onclick="window.wrapMessageFormat('~')" class="btn-msg-fmt px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 text-xs font-semibold line-through transition cursor-pointer select-none" title="Coret (~teks~)">
                                S
                            </button>
                            <button type="button" onclick="window.wrapMessageFormat('```')" class="btn-msg-fmt px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 text-xs font-mono transition cursor-pointer select-none" title="Monospace (```teks```)">
                                &lt;/&gt;
                            </button>

                            <span class="text-slate-300 mx-0.5">|</span>

                            <!-- Tombol Popover Emoji Lengkap -->
                            <div class="relative inline-block" id="emojiPickerWrapper">
                                <button type="button" id="btn-emoji-toggle" onclick="window.toggleEmojiPicker(event)" class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 active:bg-amber-300 text-amber-900 text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer select-none" title="Buka Koleksi Emoji">
                                    <span>😀 Emoji</span>
                                    <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <!-- Popover Dropdown Emoji -->
                                <div id="emoji-picker-dropdown" class="hidden absolute left-0 top-full mt-2 z-50 bg-white border border-slate-200 rounded-2xl shadow-2xl p-3 w-72 sm:w-80 max-w-[90vw] animate-fade-in text-left">
                                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                            <span>✨</span> Pilih Emoji WhatsApp
                                        </span>
                                        <button type="button" onclick="window.toggleEmojiPicker(event, false)" class="text-slate-400 hover:text-slate-700 text-xs p-1 rounded-md cursor-pointer">✕</button>
                                    </div>
                                    
                                    <div class="space-y-2.5 max-h-52 overflow-y-auto pr-1">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Populer & Promo</p>
                                            <div class="grid grid-cols-7 sm:grid-cols-8 gap-1 text-lg">
                                                <button type="button" onclick="window.insertEmoji('🎉')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🎉</button>
                                                <button type="button" onclick="window.insertEmoji('🔥')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🔥</button>
                                                <button type="button" onclick="window.insertEmoji('🚀')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🚀</button>
                                                <button type="button" onclick="window.insertEmoji('💰')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">💰</button>
                                                <button type="button" onclick="window.insertEmoji('🎁')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🎁</button>
                                                <button type="button" onclick="window.insertEmoji('📢')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">📢</button>
                                                <button type="button" onclick="window.insertEmoji('✨')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">✨</button>
                                                <button type="button" onclick="window.insertEmoji('⭐')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">⭐</button>
                                                <button type="button" onclick="window.insertEmoji('🏷️')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🏷️</button>
                                                <button type="button" onclick="window.insertEmoji('🛒')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🛒</button>
                                                <button type="button" onclick="window.insertEmoji('📦')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">📦</button>
                                                <button type="button" onclick="window.insertEmoji('💥')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">💥</button>
                                                <button type="button" onclick="window.insertEmoji('💯')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">💯</button>
                                                <button type="button" onclick="window.insertEmoji('🔔')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🔔</button>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ekspresi & Wajah</p>
                                            <div class="grid grid-cols-7 sm:grid-cols-8 gap-1 text-lg">
                                                <button type="button" onclick="window.insertEmoji('😀')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😀</button>
                                                <button type="button" onclick="window.insertEmoji('😃')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😃</button>
                                                <button type="button" onclick="window.insertEmoji('😄')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😄</button>
                                                <button type="button" onclick="window.insertEmoji('😊')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😊</button>
                                                <button type="button" onclick="window.insertEmoji('🥰')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🥰</button>
                                                <button type="button" onclick="window.insertEmoji('😍')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😍</button>
                                                <button type="button" onclick="window.insertEmoji('🤩')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🤩</button>
                                                <button type="button" onclick="window.insertEmoji('😎')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😎</button>
                                                <button type="button" onclick="window.insertEmoji('🥳')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🥳</button>
                                                <button type="button" onclick="window.insertEmoji('😂')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😂</button>
                                                <button type="button" onclick="window.insertEmoji('😉')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">😉</button>
                                                <button type="button" onclick="window.insertEmoji('🤝')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🤝</button>
                                                <button type="button" onclick="window.insertEmoji('🙏')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🙏</button>
                                                <button type="button" onclick="window.insertEmoji('👍')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">👍</button>
                                                <button type="button" onclick="window.insertEmoji('👏')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">👏</button>
                                                <button type="button" onclick="window.insertEmoji('❤️')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">❤️</button>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Simbol & Ceklis</p>
                                            <div class="grid grid-cols-7 sm:grid-cols-8 gap-1 text-lg">
                                                <button type="button" onclick="window.insertEmoji('✅')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">✅</button>
                                                <button type="button" onclick="window.insertEmoji('✔️')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">✔️</button>
                                                <button type="button" onclick="window.insertEmoji('📍')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">📍</button>
                                                <button type="button" onclick="window.insertEmoji('📞')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">📞</button>
                                                <button type="button" onclick="window.insertEmoji('💬')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">💬</button>
                                                <button type="button" onclick="window.insertEmoji('⏰')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">⏰</button>
                                                <button type="button" onclick="window.insertEmoji('💡')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">💡</button>
                                                <button type="button" onclick="window.insertEmoji('👉')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">👉</button>
                                                <button type="button" onclick="window.insertEmoji('👇')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">👇</button>
                                                <button type="button" onclick="window.insertEmoji('🟢')" class="p-1 hover:bg-amber-50 rounded transition text-center cursor-pointer">🟢</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Baris 2: Quick Emoji Bar Langsung Sekali Klik -->
                        <div class="flex items-center gap-1 overflow-x-auto pt-1 border-t border-slate-200/60 text-base scrollbar-none">
                            <span class="text-[10px] font-semibold text-slate-400 shrink-0 mr-1">Cepat:</span>
                            <button type="button" onclick="window.insertEmoji('🔥')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Api">🔥</button>
                            <button type="button" onclick="window.insertEmoji('🎉')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Pesta">🎉</button>
                            <button type="button" onclick="window.insertEmoji('👍')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Jempol">👍</button>
                            <button type="button" onclick="window.insertEmoji('👏')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Tepuk Tangan">👏</button>
                            <button type="button" onclick="window.insertEmoji('🚀')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Roket">🚀</button>
                            <button type="button" onclick="window.insertEmoji('💰')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Uang">💰</button>
                            <button type="button" onclick="window.insertEmoji('✨')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Kilau">✨</button>
                            <button type="button" onclick="window.insertEmoji('❤️')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Hati">❤️</button>
                            <button type="button" onclick="window.insertEmoji('🙏')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Terima Kasih">🙏</button>
                            <button type="button" onclick="window.insertEmoji('✅')" class="px-1.5 py-0.5 rounded hover:bg-white active:scale-95 transition cursor-pointer" title="Ceklis">✅</button>
                        </div>
                    </div>

                    <div class="space-y-3.5">
                        <div>
                            <textarea name="message" id="messageTextarea" oninput="window.updateWhatsAppPreview()" onkeyup="window.updateWhatsAppPreview()" required rows="4" placeholder="Halo {Bro|Kak} [Name], kita ada promo spesial hari ini 🎉..." class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none text-xs sm:text-sm transition leading-relaxed resize-y"></textarea>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500 mt-1 px-1 flex-wrap gap-1">
                                <span id="charCountLabel" class="font-medium text-slate-600">0 karakter</span>
                                <span class="text-[11px] text-slate-400">Variabel <code>[Name]</code> otomatis disesuaikan per kontak</span>
                            </div>

                            <div class="bg-blue-50/70 rounded-xl p-2.5 sm:p-3 mt-2 border border-blue-100">
                                <p class="text-[11px] font-bold text-blue-800 mb-0.5">PANDUAN ANTI-BANNED:</p>
                                <ul class="text-[11px] sm:text-xs text-blue-700 list-disc list-inside space-y-0.5">
                                    <li>Sebut nama dinamis: <code class="bg-blue-100/80 px-1 rounded font-bold">[Name]</code></li>
                                    <li>Spintax acak kata: <code class="bg-blue-100/80 px-1 rounded font-bold">{Hai|Halo|Salam}</code></li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs sm:text-sm font-semibold text-gray-700">Lampiran Media (Opsional)</label>
                                <button type="button" id="btn-clear-media" onclick="window.clearMediaUpload()" class="hidden text-xs text-rose-600 hover:text-rose-700 font-bold transition flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Hapus Lampiran
                                </button>
                            </div>
                            <input type="file" name="media" id="media-upload-input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx" class="block w-full text-xs sm:text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 sm:file:py-2.5 sm:file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- 4. Proteksi Anti Banned -->
                <div class="bg-indigo-50/80 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-indigo-100 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 text-indigo-200/30 pointer-events-none">
                        <svg class="w-24 h-24 sm:w-28 sm:h-28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-base sm:text-lg font-bold text-indigo-900 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg> 
                            <span>Proteksi Anti-Banned</span>
                        </h3>
                        
                        <div class="space-y-3.5">
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-indigo-900 mb-1.5">Jeda Pengiriman (Acak)</label>
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <input type="number" min="1" name="delay_min" value="10" title="Minimal delay (detik)" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs sm:text-sm font-bold text-center">
                                    <span class="text-indigo-400 font-bold text-xs sm:text-sm">sd.</span>
                                    <input type="number" min="2" name="delay_max" value="25" title="Maksimal delay (detik)" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs sm:text-sm font-bold text-center">
                                    <span class="text-xs sm:text-sm font-bold text-indigo-700">Detik</span>
                                </div>
                                <p class="text-[11px] sm:text-xs text-indigo-600 mt-1">Sistem memilih jeda acak di antara rentang waktu ini untuk menyerupai ketikan manusia.</p>
                            </div>

                            <div class="h-px bg-indigo-200/60 my-1.5"></div>

                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-indigo-900 mb-1.5">Fitur Batch Pacing (Istirahat Berkala)</label>
                                <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                                    <div>
                                        <p class="text-[10px] sm:text-[11px] font-bold text-indigo-500 mb-1 uppercase">Kirim sebanyak</p>
                                        <div class="relative">
                                            <input type="number" min="0" name="batch_send" value="20" class="w-full pl-3 pr-10 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs sm:text-sm font-bold">
                                            <span class="absolute right-2.5 top-2 text-[11px] font-bold text-gray-400">Pesan</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] sm:text-[11px] font-bold text-indigo-500 mb-1 uppercase">Lalu jeda selama</p>
                                        <div class="relative">
                                            <input type="number" min="0" name="batch_sleep" value="10" class="w-full pl-3 pr-10 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs sm:text-sm font-bold">
                                            <span class="absolute right-2.5 top-2 text-[11px] font-bold text-gray-400">Menit</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[11px] sm:text-xs text-indigo-600 mt-1">Isi 0 jika tidak ingin menggunakan fitur batch pacing.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit Form -->
                <button type="submit" id="btn-submit-broadcast" class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold py-3.5 sm:py-4 rounded-2xl text-sm transition shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2 cursor-pointer transform active:scale-[0.99]">
                    <svg id="btn-submit-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span id="btn-submit-text">Simpan & Daftarkan Broadcast</span>
                </button>

            </div>

            <!-- Kolom Kanan: PRATINJAU TAMPILAN WHATSAPP (5 Cols Desktop, Sticky) -->
            <div class="lg:col-span-5 space-y-3.5 lg:sticky lg:top-4" id="section-wa-preview">
                
                <!-- Toolbar Simulasi Target & Spintax -->
                <div class="bg-white rounded-2xl p-3 sm:p-4 shadow-sm border border-gray-100 space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h4 class="font-bold text-xs sm:text-sm text-gray-800">Pratinjau Tampilan WhatsApp</h4>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" id="btn-acak-spintax" onclick="window.rollSpintaxPreview()" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer" title="Acak variasi kata spintax {..|..}">
                                <span>Acak</span>
                                <span>🎲</span>
                            </button>
                            <!-- Mobile Kembali ke Form Button -->
                            <a href="#form-broadcast" class="lg:hidden text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2 py-1 bg-slate-100 rounded-lg">
                                ⬆️ Form
                            </a>
                        </div>
                    </div>

                    <!-- Target Switcher Simulator -->
                    <div class="flex items-center justify-between bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200/80 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-slate-400 font-medium text-[11px] shrink-0">Simulasi:</span>
                            <span id="wa-preview-target-badge" class="font-bold text-slate-800 truncate max-w-[130px] sm:max-w-[170px]">Budi Santoso</span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" id="btn-prev-target" onclick="window.navigatePreviewTarget(-1)" class="w-6 h-6 rounded-md bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs cursor-pointer shadow-2xs" title="Target Sebelumnya">‹</button>
                            <span id="wa-preview-target-idx" class="text-[10px] font-bold text-slate-500 px-1 min-w-[28px] text-center">1/1</span>
                            <button type="button" id="btn-next-target" onclick="window.navigatePreviewTarget(1)" class="w-6 h-6 rounded-md bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs cursor-pointer shadow-2xs" title="Target Berikutnya">›</button>
                        </div>
                    </div>
                </div>

                <!-- Mockup Smartphone Frame WhatsApp -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-950 p-2.5 sm:p-3.5 rounded-[32px] sm:rounded-[36px] shadow-2xl border-4 border-slate-800 shadow-slate-900/20 max-w-[320px] sm:max-w-[340px] mx-auto w-full">
                    <!-- Notch / Speaker HP -->
                    <div class="w-20 sm:w-24 h-3 bg-slate-900 rounded-full mx-auto mb-2 flex items-center justify-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-slate-800 border border-slate-700"></div>
                        <div class="w-7 h-1 bg-slate-800 rounded-full"></div>
                    </div>

                    <!-- Layar Dalam HP (Responsive Height) -->
                    <div class="bg-[#efeae2] rounded-[22px] overflow-hidden flex flex-col h-[410px] sm:h-[440px] xl:h-[470px] relative border border-slate-700/40 shadow-inner select-none" style="background-color: #efeae2; background-image: radial-gradient(#d5cdc1 0.75px, transparent 0.75px); background-size: 14px 14px;">
                        
                        <!-- Top Bar WhatsApp -->
                        <div class="bg-[#008069] text-white px-3 py-2 flex items-center justify-between shadow-md shrink-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="w-4 h-4 text-emerald-100 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-700 border border-emerald-400/40 flex items-center justify-center text-white font-bold text-xs uppercase shadow-xs shrink-0 overflow-hidden" id="wa-preview-avatar">
                                    <span id="wa-preview-initials">BS</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs leading-tight truncate text-white max-w-[110px] sm:max-w-[140px]" id="wa-preview-name">Budi Santoso</div>
                                    <div class="text-[10px] text-emerald-100/90 leading-tight flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                        <span id="wa-preview-status">online</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 text-emerald-100 shrink-0">
                                <svg class="w-3.5 h-3.5 cursor-pointer hover:text-white transition" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                                <svg class="w-3.5 h-3.5 cursor-pointer hover:text-white transition" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path></svg>
                                <svg class="w-3 h-3 cursor-pointer hover:text-white transition" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
                            </div>
                        </div>

                        <!-- Chat Messages Scrollable Area -->
                        <div class="flex-1 p-2.5 sm:p-3 overflow-y-auto space-y-2 flex flex-col justify-end" id="wa-chat-scroll-area">
                            
                            <!-- Date Pill -->
                            <div class="flex justify-center my-0.5">
                                <span class="bg-white/85 backdrop-blur-xs text-slate-600 text-[10px] font-semibold px-2.5 py-0.5 rounded-md shadow-2xs uppercase tracking-wider">Hari Ini</span>
                            </div>

                            <!-- Encryption Notice -->
                            <div class="flex justify-center my-0.5">
                                <div class="bg-[#ffeecd]/95 border border-amber-200/70 text-[#54656f] text-[10px] px-2.5 py-1 rounded-lg shadow-2xs max-w-[95%] text-center flex items-center gap-1.5 leading-snug">
                                    <svg class="w-3 h-3 shrink-0 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                                    <span>Pesan broadcast terkirim via device WhatsApp pengirim yang dipilih.</span>
                                </div>
                            </div>

                            <!-- Outgoing Bubble (Pesan Terkirim) -->
                            <div class="ml-auto max-w-[94%] bg-[#d9fdd3] text-[#111b21] rounded-2xl rounded-tr-xs p-2.5 shadow-sm border border-[#c4efb9] relative group">
                                <!-- Bubble Tail -->
                                <div class="absolute -top-0 -right-1.5 w-0 h-0 border-t-[8px] border-t-[#d9fdd3] border-r-[8px] border-r-transparent"></div>

                                <!-- Box Lampiran Gambar/Media -->
                                <div id="wa-preview-media-box" class="hidden mb-2 rounded-xl overflow-hidden bg-black/5 border border-emerald-300/40">
                                    <img id="wa-preview-img" src="" alt="Lampiran" class="w-full max-h-40 object-cover hidden">
                                    <div id="wa-preview-doc" class="p-2 flex items-center gap-2 bg-emerald-50/90 hidden">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-2xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div id="wa-preview-doc-name" class="font-bold text-xs truncate text-slate-800">document.pdf</div>
                                            <div id="wa-preview-doc-size" class="text-[10px] text-slate-500">120 KB • Dokumen</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text Body Pesan -->
                                <div id="wa-preview-text" class="text-xs sm:text-[12.5px] leading-relaxed break-words whitespace-pre-wrap font-sans text-slate-900">
                                    <span class="text-slate-400 italic">Ketik pesan di kolom "Konten Pesan" untuk melihat preview WhatsApp di sini...</span>
                                </div>

                                <!-- Waktu & Centang Dua Biru -->
                                <div class="flex items-center justify-end gap-1 mt-1 select-none">
                                    <span id="wa-preview-clock" class="text-[10px] text-slate-500 font-medium">11:30</span>
                                    <svg class="w-3.5 h-3.5 text-[#53bdeb]" viewBox="0 0 16 11" fill="currentColor"><path d="M11.15 0.5L4.85 6.8L2.35 4.3L1.15 5.5L4.85 9.2L12.35 1.7L11.15 0.5Z"/><path d="M15.15 0.5L8.85 6.8L7.85 5.8L6.65 7L8.85 9.2L16.35 1.7L15.15 0.5Z"/></svg>
                                </div>
                            </div>

                        </div>

                        <!-- Bottom Input Bar Mockup -->
                        <div class="p-2 bg-[#f0f2f5] flex items-center gap-1.5 border-t border-slate-200/80 shrink-0 select-none">
                            <div class="flex-1 bg-white rounded-full px-2.5 py-1 flex items-center gap-1.5 shadow-2xs border border-slate-200/50 text-slate-400 text-xs">
                                <span>😊</span>
                                <span class="text-[10px] sm:text-[11px] text-slate-400 flex-1 truncate">Ketik pesan</span>
                                <span>📎</span>
                                <span>📷</span>
                            </div>
                            <div class="w-7 h-7 rounded-full bg-[#00a884] text-white flex items-center justify-center shadow-xs shrink-0">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </form>
</div>

<!-- Floating Action Pill on Mobile (Quick Preview Scroll) -->
<div class="lg:hidden fixed bottom-4 right-4 z-40">
    <a href="#section-wa-preview" class="bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold px-4 py-2.5 rounded-full shadow-lg flex items-center gap-2 text-xs border border-emerald-400/40 transition transform active:scale-95">
        <span>📱</span>
        <span>Preview WA</span>
    </a>
</div>

<script>
// Global state & variables on window
window.previewTargetIndex = window.previewTargetIndex || 0;
window.spintaxSeed = window.spintaxSeed || Math.random();
window._lastTagClickTime = 0;

// ==========================================
// 1. TOOLBAR INSERT / FORMAT & EMOJI FUNCTIONS
// ==========================================
window.insertMessageTag = function(tag) {
    const now = Date.now();
    if (now - window._lastTagClickTime < 250) return;
    window._lastTagClickTime = now;

    const textarea = document.getElementById('messageTextarea');
    if (!textarea) return;

    const val = textarea.value;
    const start = (typeof textarea.selectionStart === 'number') ? textarea.selectionStart : val.length;
    const end = (typeof textarea.selectionEnd === 'number') ? textarea.selectionEnd : val.length;

    textarea.value = val.substring(0, start) + tag + val.substring(end);
    textarea.focus();
    const newPos = start + tag.length;
    try {
        textarea.setSelectionRange(newPos, newPos);
    } catch(e) {}

    window.updateWhatsAppPreview();
};

window.insertEmoji = function(emoji) {
    const textarea = document.getElementById('messageTextarea');
    if (!textarea) return;

    const val = textarea.value;
    const start = (typeof textarea.selectionStart === 'number') ? textarea.selectionStart : val.length;
    const end = (typeof textarea.selectionEnd === 'number') ? textarea.selectionEnd : val.length;

    textarea.value = val.substring(0, start) + emoji + val.substring(end);
    textarea.focus();
    const newPos = start + emoji.length;
    try {
        textarea.setSelectionRange(newPos, newPos);
    } catch(e) {}

    window.updateWhatsAppPreview();
};

window.toggleEmojiPicker = function(e, forceState) {
    if (e && e.stopPropagation) e.stopPropagation();
    const picker = document.getElementById('emoji-picker-dropdown');
    if (!picker) return;

    if (typeof forceState === 'boolean') {
        if (forceState) picker.classList.remove('hidden');
        else picker.classList.add('hidden');
    } else {
        picker.classList.toggle('hidden');
    }
};

// Close emoji picker when clicking outside
document.addEventListener('click', function(e) {
    const picker = document.getElementById('emoji-picker-dropdown');
    const toggleBtn = document.getElementById('btn-emoji-toggle');
    if (picker && !picker.classList.contains('hidden')) {
        if (!picker.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
            picker.classList.add('hidden');
        }
    }
});

window.wrapMessageFormat = function(symbol) {
    const now = Date.now();
    if (now - window._lastTagClickTime < 250) return;
    window._lastTagClickTime = now;

    const textarea = document.getElementById('messageTextarea');
    if (!textarea) return;

    const val = textarea.value;
    const start = (typeof textarea.selectionStart === 'number') ? textarea.selectionStart : 0;
    const end = (typeof textarea.selectionEnd === 'number') ? textarea.selectionEnd : 0;
    const selected = val.substring(start, end);

    if (selected.length > 0) {
        textarea.value = val.substring(0, start) + symbol + selected + symbol + val.substring(end);
        textarea.focus();
        try {
            textarea.setSelectionRange(start + symbol.length, end + symbol.length);
        } catch(e) {}
    } else {
        const placeholder = symbol === '*' ? 'teks tebal' : (symbol === '_' ? 'teks miring' : (symbol === '~' ? 'teks coret' : 'kode'));
        textarea.value = val.substring(0, start) + symbol + placeholder + symbol + val.substring(end);
        textarea.focus();
        try {
            textarea.setSelectionRange(start + symbol.length, start + symbol.length + placeholder.length);
        } catch(e) {}
    }

    window.updateWhatsAppPreview();
};

// ==========================================
// 2. PARSERS & COUNTERS
// ==========================================
window.getParsedTargets = function() {
    const el = document.getElementById('targetTextarea');
    const raw = el ? el.value : '';
    const lines = raw.split('\n');
    const targets = [];

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;
        const parts = line.split(',');
        const phone = (parts[0] || '').trim();
        const name = parts.slice(1).join(',').trim();
        const digits = phone.replace(/[^0-9]/g, '');
        if (digits.length >= 9) {
            targets.push({
                phone: phone,
                digits: digits,
                name: name || ('Target #' + (targets.length + 1))
            });
        }
    }
    return targets;
};

window.updateTargetCounter = function() {
    const targets = window.getParsedTargets();
    const count = targets.length;
    const badge = document.getElementById('targetCounterBadge');
    if (badge) {
        badge.textContent = count + ' Target';
        badge.className = count > 0 
            ? "px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold whitespace-nowrap"
            : "px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold whitespace-nowrap";
    }
};

window.resolveSpintax = function(text, seed) {
    if (!text) return '';
    const regex = /\{([^{}]+)\}/g;
    let counter = 0;
    return text.replace(regex, function(match, contents) {
        const options = contents.split('|');
        if (options.length === 0) return '';
        const r = ((seed * 9301 + 49297 + counter * 233) % 233280) / 233280;
        counter++;
        const chosenIndex = Math.floor(r * options.length);
        return (options[chosenIndex] || '').trim();
    });
};

window.parseWhatsAppMarkdown = function(text) {
    if (!text) return '';
    let safe = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    // Monospace ```code```
    safe = safe.replace(/```([\s\S]*?)```/g, '<code class="bg-black/5 px-1 py-0.5 rounded font-mono text-[11.5px] text-emerald-900">$1</code>');
    // Bold *text*
    safe = safe.replace(/\*([^\*\n]+)\*/g, '<strong class="font-bold text-slate-900">$1</strong>');
    // Italic _text_
    safe = safe.replace(/_([^_\n]+)_/g, '<em class="italic">$1</em>');
    // Strike ~text~
    safe = safe.replace(/~([^~\n]+)~/g, '<del class="line-through text-slate-500">$1</del>');
    // Links
    safe = safe.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" class="text-blue-600 underline hover:text-blue-800 break-all">$1</a>');
    // Newlines
    safe = safe.replace(/\n/g, '<br>');

    return safe;
};

// ==========================================
// 3. WHATSAPP LIVE PREVIEW UPDATER
// ==========================================
window.updateWhatsAppPreview = function() {
    const targets = window.getParsedTargets();
    const totalTargets = targets.length;

    let currentTarget = null;
    if (totalTargets > 0) {
        if (window.previewTargetIndex >= totalTargets) window.previewTargetIndex = 0;
        if (window.previewTargetIndex < 0) window.previewTargetIndex = totalTargets - 1;
        currentTarget = targets[window.previewTargetIndex];
    } else {
        currentTarget = {
            name: 'Budi Santoso',
            phone: '081234567890',
            digits: '6281234567890'
        };
    }

    const nameEl = document.getElementById('wa-preview-name');
    const badgeEl = document.getElementById('wa-preview-target-badge');
    const idxEl = document.getElementById('wa-preview-target-idx');
    const initialsEl = document.getElementById('wa-preview-initials');

    if (nameEl) nameEl.textContent = currentTarget.name;
    if (badgeEl) badgeEl.textContent = currentTarget.name;
    if (idxEl) idxEl.textContent = totalTargets > 0 ? ((window.previewTargetIndex + 1) + '/' + totalTargets) : '1/1';
    
    if (initialsEl) {
        const words = currentTarget.name.split(' ').filter(w => w.length > 0);
        let initials = 'WA';
        if (words.length >= 2) {
            initials = (words[0][0] + words[1][0]).toUpperCase();
        } else if (words.length === 1) {
            initials = words[0].substring(0, 2).toUpperCase();
        }
        initialsEl.textContent = initials;
    }

    const messageInput = document.getElementById('messageTextarea');
    const rawMessage = messageInput ? messageInput.value : '';
    
    // Update Char Count
    const charCountEl = document.getElementById('charCountLabel');
    if (charCountEl) {
        charCountEl.textContent = rawMessage.length + ' karakter';
    }

    const previewTextEl = document.getElementById('wa-preview-text');
    if (previewTextEl) {
        if (!rawMessage.trim()) {
            previewTextEl.innerHTML = '<span class="text-slate-400 italic">Ketik pesan di kolom "Konten Pesan" untuk melihat preview WhatsApp di sini...</span>';
        } else {
            let processed = window.resolveSpintax(rawMessage, window.spintaxSeed);
            processed = processed.replace(/\[Name\]/gi, currentTarget.name);
            previewTextEl.innerHTML = window.parseWhatsAppMarkdown(processed);
        }
    }

    const clockEl = document.getElementById('wa-preview-clock');
    if (clockEl) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        clockEl.textContent = hours + ':' + mins;
    }
};

window.navigatePreviewTarget = function(direction) {
    const targets = window.getParsedTargets();
    const total = targets.length > 0 ? targets.length : 1;
    window.previewTargetIndex = (window.previewTargetIndex + direction + total) % total;
    window.updateWhatsAppPreview();
};

window.rollSpintaxPreview = function() {
    window.spintaxSeed = Math.random();
    window.updateWhatsAppPreview();
};

window.clearMediaUpload = function() {
    const input = document.getElementById('media-upload-input');
    if (input) input.value = '';
    const box = document.getElementById('wa-preview-media-box');
    const img = document.getElementById('wa-preview-img');
    const doc = document.getElementById('wa-preview-doc');
    const clearBtn = document.getElementById('btn-clear-media');

    if (box) box.classList.add('hidden');
    if (img) { img.src = ''; img.classList.add('hidden'); }
    if (doc) doc.classList.add('hidden');
    if (clearBtn) clearBtn.classList.add('hidden');
};

// ==========================================
// 4. ATTACH EVENT LISTENERS (Self-contained Vanilla JS)
// ==========================================

// Delegated input listener
document.addEventListener('input', function(e) {
    if (!e.target) return;
    if (e.target.id === 'messageTextarea' || e.target.name === 'message') {
        window.updateWhatsAppPreview();
    } else if (e.target.id === 'targetTextarea' || e.target.name === 'targets') {
        window.updateTargetCounter();
        window.updateWhatsAppPreview();
    }
});

// Media upload change listener
var mediaUploadInput = document.getElementById('media-upload-input');
if (mediaUploadInput) {
    mediaUploadInput.onchange = function(e) {
        const file = e.target.files[0];
        const box = document.getElementById('wa-preview-media-box');
        const img = document.getElementById('wa-preview-img');
        const doc = document.getElementById('wa-preview-doc');
        const clearBtn = document.getElementById('btn-clear-media');

        if (!file) {
            window.clearMediaUpload();
            return;
        }
        if (clearBtn) clearBtn.classList.remove('hidden');

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                if (img) {
                    img.src = evt.target.result;
                    img.classList.remove('hidden');
                }
                if (doc) doc.classList.add('hidden');
                if (box) box.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            if (img) img.classList.add('hidden');
            if (doc) {
                doc.classList.remove('hidden');
                const docName = document.getElementById('wa-preview-doc-name');
                const docSize = document.getElementById('wa-preview-doc-size');
                if (docName) docName.textContent = file.name;
                if (docSize) {
                    const sizeKB = (file.size / 1024).toFixed(1);
                    const ext = file.name.split('.').pop().toUpperCase();
                    docSize.textContent = sizeKB + ' KB • ' + ext;
                }
            }
            if (box) box.classList.remove('hidden');
        }
    };
}

// Excel upload change listener
var excelUploadInput = document.getElementById('excel-upload');
if (excelUploadInput) {
    excelUploadInput.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Library pembaca Excel sedang dimuat, coba sesaat lagi.', 'info');
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({ 
                title: 'Membaca Excel / CSV...', 
                text: 'Sedang mengekstrak nomor telepon dan nama...', 
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });
        }

        const reader = new FileReader();
        reader.onload = function(evt) {
            try {
                const data = new Uint8Array(evt.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const json = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                
                let resultText = '';
                let validCount = 0;
                
                for (let i = 0; i < json.length; i++) {
                    const row = json[i];
                    if (!row || row.length === 0) continue;
                    
                    const phoneCol = String(row[0] || '').trim();
                    const nameCol = String(row[1] || '').trim();
                    const phoneDigits = phoneCol.replace(/[^0-9]/g, '');
                    
                    if (phoneDigits.length >= 9) {
                        if (nameCol) {
                            resultText += phoneDigits + ', ' + nameCol + '\n';
                        } else {
                            resultText += phoneDigits + '\n';
                        }
                        validCount++;
                    }
                }

                if (validCount > 0) {
                    const textarea = document.getElementById('targetTextarea');
                    if (textarea) {
                        const existing = textarea.value.trim();
                        textarea.value = existing ? existing + '\n' + resultText.trim() : resultText.trim();
                    }
                    
                    window.updateTargetCounter();
                    window.updateWhatsAppPreview();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Terbaca!',
                            text: 'Sebanyak ' + validCount + ' kontak target berhasil ditambahkan.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Peringatan!', 'Tidak ada data nomor telepon valid (min. 9 digit) ditemukan di kolom A.', 'warning');
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memproses file: ' + err.message, 'error');
            }
            
            excelUploadInput.value = '';
        };
        reader.readAsArrayBuffer(file);
    };
}

// Form submit listener with feedback & loading state
var formBroadcastEl = document.getElementById('form-broadcast');
if (formBroadcastEl) {
    let isSubmitting = false;

    formBroadcastEl.onsubmit = function(e) {
        e.preventDefault();
        if (isSubmitting) return;
        
        const fd = new FormData(formBroadcastEl);
        if (!fd.get('device_id')) {
            if (typeof Swal !== 'undefined') Swal.fire('Peringatan', 'Silakan pilih device WhatsApp pengirim terlebih dahulu!', 'warning');
            return;
        }

        const targets = window.getParsedTargets();
        if (targets.length === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Target Kosong', 'Masukkan minimal 1 nomor telepon target yang valid (minimal 9 digit)!', 'warning');
            return;
        }

        const msgText = (fd.get('message') || '').toString().trim();
        if (!msgText) {
            if (typeof Swal !== 'undefined') Swal.fire('Pesan Kosong', 'Silakan isi konten pesan broadcast!', 'warning');
            return;
        }

        isSubmitting = true;
        const btnSubmit = document.getElementById('btn-submit-broadcast');
        const btnSubmitText = document.getElementById('btn-submit-text');
        if (btnSubmit) btnSubmit.disabled = true;
        if (btnSubmitText) btnSubmitText.textContent = 'Menyimpan Broadcast...';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyimpan Broadcast...',
                text: 'Sistem sedang memproses campaign dan kontak penerima.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        }

        const baseUrl = (typeof BASEURL !== 'undefined' ? BASEURL : '');
        fetch(baseUrl + '/broadcast/store', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan!',
                        text: res.message || 'Broadcast telah dijadwalkan.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                }
                setTimeout(() => {
                    if (typeof loadPage === 'function') {
                        loadPage(baseUrl + '/broadcast');
                    } else {
                        window.location.href = baseUrl + '/broadcast';
                    }
                }, 1600);
            } else {
                isSubmitting = false;
                if (btnSubmit) btnSubmit.disabled = false;
                if (btnSubmitText) btnSubmitText.textContent = 'Simpan & Daftarkan Broadcast';
                if (typeof Swal !== 'undefined') Swal.fire('Gagal Menyimpan', res.message || 'Terjadi kesalahan sistem saat menyimpan broadcast.', 'error');
            }
        })
        .catch(err => {
            isSubmitting = false;
            if (btnSubmit) btnSubmit.disabled = false;
            if (btnSubmitText) btnSubmitText.textContent = 'Simpan & Daftarkan Broadcast';
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan jaringan atau koneksi server saat menyimpan broadcast.', 'error');
        });
    };
}

// Initial Run Immediately
window.updateTargetCounter();
window.updateWhatsAppPreview();
</script>
