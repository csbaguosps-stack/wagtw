<?php
$devices = $data['devices'] ?? [];
$selectedDeviceId = $data['selected_device_id'] ?? 0;
$tpl = $data['templates'] ?? [];
$mutedContacts = $data['muted_contacts'] ?? [];
$activeMonth = $data['current_month'] ?? intval(date('n'));
$activeYear  = $data['current_year'] ?? intval(date('Y'));
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$years = range(intval(date('Y')) - 2, intval(date('Y')) + 3);
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <h2 class="text-2xl font-bold text-gray-800">Template AI WhatsApp</h2>
            <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Handoff & Bot</span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Konfigurasi pesan bantuan admin, respon pengalihan (handoff), durasi reaktivasi timeout, dan kata kunci penutupan sesi admin.</p>
    </div>

    <!-- Actions & Device Selector -->
    <div class="flex flex-wrap items-center gap-3">
        <?php if (!empty($devices)): ?>
        <div class="relative min-w-[200px]">
            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pilih Device WhatsApp</label>
            <div class="relative">
                <select id="selectDevice" class="w-full pl-9 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 appearance-none transition-all">
                    <?php foreach ($devices as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($d['id'] == $selectedDeviceId) ? 'selected' : '' ?>>
                            📱 <?= htmlspecialchars($d['name'] ?: 'Tanpa Nama') ?> (<?= htmlspecialchars($d['phone'] ?: 'No Phone') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-amber-50 text-amber-800 text-xs px-3 py-2 rounded-xl border border-amber-200 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Belum ada Device terhubung. Tambahkan device di menu <a href="<?= BASEURL ?>/device" class="font-bold underline ml-1">Devices</a>.
        </div>
        <?php endif; ?>

        <div class="flex items-end gap-2 pt-4 md:pt-0">
            <button type="button" id="btnResetDefaults" class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition flex items-center gap-1.5 shadow-sm" title="Kembalikan semua teks ke default pabrik">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset Default
            </button>
            <button type="button" id="btnSubmitTop" class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Perubahan
            </button>
        </div>
    </div>
</div>

<!-- Main Form -->
<form id="formAiTemplate" class="space-y-6">
    <input type="hidden" name="device_id" id="hiddenDeviceId" value="<?= $selectedDeviceId ?>">

    <!-- Checkbox Apply to All Devices -->
    <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-emerald-900">Terapkan ke Seluruh Device?</h4>
                <p class="text-xs text-emerald-700">Jika dicentang, pengaturan template di bawah ini akan otomatis disalin ke semua device WhatsApp milik Anda.</p>
            </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="apply_to_all" id="applyToAll" value="1" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
        </label>
    </div>

    <!-- Grid 2 Kolom: Fitur 1 & Fitur 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- CARD 1: Template Footer Bantuan Admin -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-sm">1</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Footer Bantuan Admin</h3>
                            <p class="text-xs text-gray-500">Disisipkan di bagian bawah setiap balasan AI WhatsApp</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer" title="Aktifkan / Nonaktifkan footer bantuan admin">
                        <input type="checkbox" name="ai_footer_enabled" id="aiFooterEnabled" value="1" <?= ($tpl['ai_footer_enabled'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-medium">Teks Footer Bantuan Admin</span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="insertFormatting('aiFooterText', '*teks*', 1)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded text-gray-700 font-bold" title="Tebal (Bold)">*B*</button>
                            <button type="button" onclick="insertFormatting('aiFooterText', '_teks_', 1)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded text-gray-700 italic" title="Miring (Italic)">_I_</button>
                            <button type="button" onclick="insertFormatting('aiFooterText', '💬 ', 0)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded" title="Emoji">💬</button>
                        </div>
                    </div>
                    
                    <textarea name="ai_footer_text" id="aiFooterText" rows="4" class="w-full px-4 py-3 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono leading-relaxed resize-none" placeholder="Tuliskan format footer bantuan admin..."><?= htmlspecialchars($tpl['ai_footer_text'] ?? '') ?></textarea>
                    
                    <p class="text-[11px] text-gray-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Footer ini otomatis ditambahkan jika jawaban AI belum memuat opsi bantuan staf.
                    </p>
                </div>
            </div>

            <!-- Live Preview Balasan AI -->
            <div class="mt-5 pt-4 border-t border-gray-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block mb-2">Simulasi Balasan AI (WhatsApp Preview)</span>
                <div class="bg-[#EFEAE2] p-3 rounded-xl shadow-inner border border-gray-200/80 max-w-full">
                    <div class="bg-white rounded-lg p-3 shadow-sm max-w-[95%] text-xs text-gray-800 leading-relaxed border-l-4 border-emerald-500">
                        <p class="text-gray-600 mb-2">Halo kak! Tentu, produk kami bergaransi resmi selama 1 tahun dan gratis biaya pengecekan di seluruh cabang. 😊</p>
                        <div id="previewFooterWrapper" class="<?= ($tpl['ai_footer_enabled'] ?? 1) ? '' : 'hidden' ?>">
                            <pre id="previewFooterText" class="font-sans whitespace-pre-wrap text-[11.5px] text-gray-700 bg-gray-50/80 p-2 rounded border border-gray-100 mt-2 font-medium"><?= htmlspecialchars($tpl['ai_footer_text'] ?? '') ?></pre>
                        </div>
                        <div class="text-[10px] text-gray-400 text-right mt-1.5 flex items-center justify-end gap-1">
                            <span>10:14</span>
                            <span class="text-blue-500 font-bold">✓✓</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: Template Pesan Pengalihan (Handoff) ke Admin -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-black text-sm">2</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Pesan Pengalihan ke Admin (Handoff)</h3>
                            <p class="text-xs text-gray-500">Otomatis dikirim saat pelanggan mengetik pemicu "Chat Admin"</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200 uppercase">Auto Trigger</span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-medium">Teks Pesan Handoff</span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="insertFormatting('aiHandoffText', '*teks*', 1)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded text-gray-700 font-bold" title="Tebal (Bold)">*B*</button>
                            <button type="button" onclick="insertFormatting('aiHandoffText', '_teks_', 1)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded text-gray-700 italic" title="Miring (Italic)">_I_</button>
                            <button type="button" onclick="insertFormatting('aiHandoffText', '👤 ', 0)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded" title="Emoji">👤</button>
                            <button type="button" onclick="insertFormatting('aiHandoffText', '🙏 ', 0)" class="px-1.5 py-0.5 bg-gray-100 hover:bg-gray-200 rounded" title="Emoji">🙏</button>
                        </div>
                    </div>
                    
                    <textarea name="ai_handoff_text" id="aiHandoffText" rows="5" class="w-full px-4 py-3 bg-gray-50/70 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono leading-relaxed resize-none" placeholder="Tuliskan format pesan saat dialihkan ke admin..."><?= htmlspecialchars($tpl['ai_handoff_text'] ?? '') ?></textarea>
                    
                    <p class="text-[11px] text-gray-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Dipicu kata: <em>Chat Admin, Hubungi Admin, Tanya Admin, Operator, CS</em>, dsb.
                    </p>
                </div>
            </div>

            <!-- Live Preview Pesan Handoff -->
            <div class="mt-5 pt-4 border-t border-gray-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block mb-2">Simulasi Pengalihan WhatsApp</span>
                <div class="bg-[#EFEAE2] p-3 rounded-xl shadow-inner border border-gray-200/80 max-w-full space-y-2">
                    <!-- Customer Trigger -->
                    <div class="bg-[#D9FDD3] rounded-lg p-2.5 shadow-sm max-w-[80%] ml-auto text-xs text-gray-800">
                        <p class="font-medium">Chat Admin dong kak</p>
                        <div class="text-[10px] text-gray-400 text-right mt-1 flex items-center justify-end gap-1">
                            <span>10:15</span>
                            <span class="text-blue-500 font-bold">✓✓</span>
                        </div>
                    </div>
                    <!-- System Handoff Reply -->
                    <div class="bg-white rounded-lg p-3 shadow-sm max-w-[95%] text-xs text-gray-800 leading-relaxed border-l-4 border-teal-500">
                        <pre id="previewHandoffText" class="font-sans whitespace-pre-wrap text-[11.5px] text-gray-700 font-medium"><?= htmlspecialchars($tpl['ai_handoff_text'] ?? '') ?></pre>
                        <div class="text-[10px] text-gray-400 text-right mt-1.5 flex items-center justify-end gap-1">
                            <span>10:15</span>
                            <span class="text-blue-500 font-bold">✓✓</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Grid 2 Kolom: Fitur 3 & Fitur 4 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- CARD 3: Waktu AI Aktif Kembali (Reactivation Timeout) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-black text-sm">3</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Waktu AI Aktif Kembali (Auto-Timeout)</h3>
                            <p class="text-xs text-gray-500">Kapan Bot AI otomatis melayani kontak lagi setelah dialihkan</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase">Timeout</span>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Pilih Periode AI Aktif Kembali:</label>
                    <?php $curDur = $tpl['ai_reactivate_duration'] ?? 'next_day'; ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" id="durationRadioGroup">
                        
                        <label class="dur-card relative flex items-center p-3 rounded-xl border cursor-pointer transition-all <?= ($curDur === '1_hour') ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <input type="radio" name="ai_reactivate_duration" value="1_hour" <?= ($curDur === '1_hour') ? 'checked' : '' ?> class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= ($curDur === '1_hour') ? 'bg-emerald-500' : 'bg-gray-300' ?> radio-dot"></span>
                                <div>
                                    <span class="text-xs block font-bold">1 Jam</span>
                                    <span class="text-[10px] text-gray-400 block font-normal">Setelah tidak ada respon</span>
                                </div>
                            </div>
                        </label>

                        <label class="dur-card relative flex items-center p-3 rounded-xl border cursor-pointer transition-all <?= ($curDur === '2_hours') ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <input type="radio" name="ai_reactivate_duration" value="2_hours" <?= ($curDur === '2_hours') ? 'checked' : '' ?> class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= ($curDur === '2_hours') ? 'bg-emerald-500' : 'bg-gray-300' ?> radio-dot"></span>
                                <div>
                                    <span class="text-xs block font-bold">2 Jam</span>
                                    <span class="text-[10px] text-gray-400 block font-normal">Setelah tidak ada respon</span>
                                </div>
                            </div>
                        </label>

                        <label class="dur-card relative flex items-center p-3 rounded-xl border cursor-pointer transition-all <?= ($curDur === '3_hours') ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <input type="radio" name="ai_reactivate_duration" value="3_hours" <?= ($curDur === '3_hours') ? 'checked' : '' ?> class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= ($curDur === '3_hours') ? 'bg-emerald-500' : 'bg-gray-300' ?> radio-dot"></span>
                                <div>
                                    <span class="text-xs block font-bold">3 Jam</span>
                                    <span class="text-[10px] text-gray-400 block font-normal">Setelah tidak ada respon</span>
                                </div>
                            </div>
                        </label>

                        <label class="dur-card relative flex items-center p-3 rounded-xl border cursor-pointer transition-all <?= ($curDur === '6_hours') ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <input type="radio" name="ai_reactivate_duration" value="6_hours" <?= ($curDur === '6_hours') ? 'checked' : '' ?> class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= ($curDur === '6_hours') ? 'bg-emerald-500' : 'bg-gray-300' ?> radio-dot"></span>
                                <div>
                                    <span class="text-xs block font-bold">6 Jam</span>
                                    <span class="text-[10px] text-gray-400 block font-normal">Setelah tidak ada respon</span>
                                </div>
                            </div>
                        </label>

                        <label class="dur-card relative flex items-center p-3 rounded-xl border cursor-pointer transition-all <?= ($curDur === 'next_day') ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <input type="radio" name="ai_reactivate_duration" value="next_day" <?= ($curDur === 'next_day') ? 'checked' : '' ?> class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= ($curDur === 'next_day') ? 'bg-emerald-500' : 'bg-gray-300' ?> radio-dot"></span>
                                <div>
                                    <span class="text-xs block font-bold">Next Day (Besok Hari)</span>
                                    <span class="text-[10px] text-gray-400 block font-normal">Pukul 00:00 hari berikutnya</span>
                                </div>
                            </div>
                        </label>

                        <label class="dur-card relative flex items-center p-3 rounded-xl border cursor-pointer transition-all <?= ($curDur === 'manual') ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <input type="radio" name="ai_reactivate_duration" value="manual" <?= ($curDur === 'manual') ? 'checked' : '' ?> class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= ($curDur === 'manual') ? 'bg-emerald-500' : 'bg-gray-300' ?> radio-dot"></span>
                                <div>
                                    <span class="text-xs block font-bold">Manual Saja</span>
                                    <span class="text-[10px] text-gray-400 block font-normal">Hanya saat admin akhiri sesi</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 flex items-start gap-2.5 text-xs text-blue-800">
                <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="leading-relaxed">
                    <strong>Cara Kerja "Setelah Tidak Ada Respon":</strong><br>
                    Waktu dihitung mundur dari pesan percakapan terakhir antara admin & kontak. Setiap kali ada pesan baru saat sesi admin berlangsung, timer otomatis diperpanjang.
                </div>
            </div>
        </div>

        <!-- CARD 4: Kata Kunci Admin "Akhiri Percakapan" -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-black text-sm">4</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Aktivasi AI via Kata Kunci Admin</h3>
                            <p class="text-xs text-gray-500">Ketik kata kunci ini di WhatsApp untuk mengaktifkan AI seketika</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase">Admin Command</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">Kata Kunci Penutup Sesi (Bisa Lebih Dari 1):</label>
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded border border-emerald-200">Multi-Keywords</span>
                        </div>
                        <div class="relative">
                            <textarea name="ai_reactivate_keyword" id="aiReactivateKeyword" rows="2" class="w-full pl-10 pr-4 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-xs font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono leading-relaxed resize-none" placeholder="akhiri percakapan, selesai, #selesai, tutup sesi, end chat"><?= htmlspecialchars($tpl['ai_reactivate_keyword'] ?? 'akhiri percakapan, selesai, #selesai, tutup sesi, end chat') ?></textarea>
                            <div class="absolute top-2.5 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                            <span class="text-[11px] text-gray-400">Tambah cepat:</span>
                            <button type="button" onclick="appendKeyword('akhiri')" class="text-[10px] bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 px-2 py-0.5 rounded-md font-semibold transition border border-gray-200">+ akhiri</button>
                            <button type="button" onclick="appendKeyword('akhiri percakapan')" class="text-[10px] bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 px-2 py-0.5 rounded-md font-semibold transition border border-gray-200">+ akhiri percakapan</button>
                            <button type="button" onclick="appendKeyword('selesai')" class="text-[10px] bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 px-2 py-0.5 rounded-md font-semibold transition border border-gray-200">+ selesai</button>
                            <button type="button" onclick="appendKeyword('#selesai')" class="text-[10px] bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 px-2 py-0.5 rounded-md font-semibold transition border border-gray-200">+ #selesai</button>
                            <button type="button" onclick="appendKeyword('tutup sesi')" class="text-[10px] bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 px-2 py-0.5 rounded-md font-semibold transition border border-gray-200">+ tutup sesi</button>
                            <button type="button" onclick="appendKeyword('end chat')" class="text-[10px] bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 px-2 py-0.5 rounded-md font-semibold transition border border-gray-200">+ end chat</button>
                        </div>
                        <span class="text-[11px] text-gray-400 mt-1 block">
                            💡 <strong>Pisahkan dengan tanda koma ( , ) atau baris baru (Enter).</strong> Bot AI akan aktif kembali jika admin mengetik salah satu kata kunci di atas.
                        </span>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-gray-700">Kirim Pesan Konfirmasi ke Pelanggan?</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="ai_reactivate_notify" id="aiReactivateNotify" value="1" <?= ($tpl['ai_reactivate_notify'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div id="reactivateMessageWrapper" class="<?= ($tpl['ai_reactivate_notify'] ?? 1) ? '' : 'hidden' ?>">
                            <textarea name="ai_reactivate_message" id="aiReactivateMessage" rows="3" class="w-full px-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono leading-relaxed resize-none" placeholder="Tuliskan pesan konfirmasi bahwa sesi telah berakhir..."><?= htmlspecialchars($tpl['ai_reactivate_message'] ?? '') ?></textarea>
                            <span class="text-[11px] text-gray-400 mt-1 block">Pesan ini otomatis dikirim ke ruang chat saat admin mengetik kata kunci.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3.5 bg-amber-50/60 rounded-xl border border-amber-100 text-xs text-amber-800 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="leading-relaxed">
                    <strong>Catatan Praktis:</strong> Ketika staf admin mengetik kata kunci di WhatsApp (misal: <code>akhiri percakapan</code>), Bot AI akan langsung aktif kembali untuk percakapan tersebut.
                </div>
            </div>
        </div>

    </div>

    <!-- Tombol Simpan Bawah -->
    <div class="flex items-center justify-end gap-3 pt-2">
        <button type="submit" id="btnSubmitBottom" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Simpan Semua Pengaturan Template AI
        </button>
    </div>
</form>

<!-- TABEL KONTAK YANG SEDANG DITANGANI ADMIN (LIVE MUTED) -->
<div class="mt-8 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-gray-100">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-bold text-gray-800">Kontak Sedang Ditangani Admin (Bot AI Di-mute)</h3>
                <span id="mutedCountBadge" class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($mutedContacts) ?> Kontak</span>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">Daftar pelanggan yang saat ini sedang dalam sesi chat staf manual (Bot AI tidak merespons sementara).</p>
        </div>

        <!-- Toolbar: Filter Bulan/Tahun & Tombol Aksi -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Filter Bulan -->
            <div class="relative">
                <select id="filterMonth" title="Filter Bulan" class="pl-3 pr-8 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:bg-white transition cursor-pointer appearance-none shadow-sm">
                    <option value="all" <?= ($activeMonth === 'all') ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php foreach ($months as $num => $mName): ?>
                        <option value="<?= $num ?>" <?= ($activeMonth == $num) ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            <!-- Filter Tahun -->
            <div class="relative">
                <select id="filterYear" title="Filter Tahun" class="pl-3 pr-8 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:bg-white transition cursor-pointer appearance-none shadow-sm">
                    <?php foreach ($years as $yr): ?>
                        <option value="<?= $yr ?>" <?= ($activeYear == $yr) ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            <!-- Tombol Kembali ke Bulan Ini -->
            <button type="button" id="btnCurrentMonth" title="Kembali ke Bulan & Tahun Saat Ini" class="px-2.5 py-1.5 bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 border border-gray-200 hover:border-emerald-200 rounded-xl text-xs font-semibold transition flex items-center gap-1 shadow-sm">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Bulan Ini
            </button>

            <!-- Tombol Refresh -->
            <button type="button" onclick="reloadMutedList()" title="Muat ulang daftar" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 border border-gray-200 rounded-xl text-xs font-semibold transition flex items-center gap-1 shadow-sm">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>

            <!-- Tombol Hapus Semua -->
            <button type="button" id="btnDeleteAllMuted" title="Hapus semua kontak mute pada periode ini" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-600 border border-rose-200 hover:border-rose-600 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Semua
            </button>
        </div>
    </div>

    <!-- Toolbar Baris Terpilih (Bulk Action Bar) -->
    <div id="bulkActionBar" class="hidden mb-3 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between gap-3 transition-all">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
            <span class="text-xs font-bold text-rose-800" id="selectedCountText">0 kontak dipilih</span>
            <span class="text-[11px] text-rose-600 hidden sm:inline">— Hapus ceklis untuk mengaktifkan AI sekaligus</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="btnCancelSelection" class="px-3 py-1 text-xs font-semibold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">Batal</button>
            <button type="button" id="btnDeleteSelectedMuted" class="px-3 py-1 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Terpilih
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm" id="tableMutedContacts">
            <thead>
                <tr class="text-gray-400 uppercase text-[11px] font-bold border-b border-gray-100 bg-gray-50/50">
                    <th class="py-3 px-3 w-10 text-center">
                        <input type="checkbox" id="checkAllMuted" title="Pilih Semua" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer">
                    </th>
                    <th class="py-3 px-4">Nomor WhatsApp</th>
                    <th class="py-3 px-4">Alasan Sesi Admin</th>
                    <th class="py-3 px-4">Waktu Mulai</th>
                    <th class="py-3 px-4">Batas Waktu Mute (Expired)</th>
                    <th class="py-3 px-4 text-center">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody id="mutedListBody" class="divide-y divide-gray-100">
                <?php if (empty($mutedContacts)): ?>
                    <tr id="rowEmptyMuted">
                        <td colspan="6" class="text-center py-8 text-gray-400 text-xs">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <span class="font-medium text-gray-600">Tidak ada kontak yang sedang di-mute pada periode ini</span>
                                <span class="text-gray-400 text-[11px]">Seluruh percakapan WhatsApp dilayani otomatis oleh Bot AI secara normal.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mutedContacts as $mc): ?>
                        <tr id="muted-row-<?= $mc['id'] ?>" class="hover:bg-gray-50/60 transition">
                            <td class="py-3 px-3 text-center">
                                <input type="checkbox" class="check-muted-item w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer" value="<?= $mc['id'] ?>" data-phone="<?= htmlspecialchars($mc['phone']) ?>">
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-gray-800">
                                <?= htmlspecialchars($mc['phone']) ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($mc['reason'] === 'chat_admin'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        💬 Minta Chat Admin
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        👤 Admin Menjawab
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($mc['created_at'])) ?>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <?php if (!empty($mc['muted_until'])): ?>
                                    <span class="font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded"><?= date('d/m/Y H:i', strtotime($mc['muted_until'])) ?></span>
                                <?php else: ?>
                                    <span class="text-gray-500">Besok (<?= date('d/m/Y', strtotime($mc['muted_date'] . ' +1 day')) ?> 00:00)</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button type="button" onclick="unmuteContact(<?= $mc['id'] ?>, '<?= htmlspecialchars($mc['phone']) ?>')" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 font-semibold rounded-lg text-xs transition border border-emerald-200 flex items-center gap-1 mx-auto">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Aktifkan AI Sekarang
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    // ── Helper: Format live preview WhatsApp ──
    function updatePreviews() {
        const footerText = $('#aiFooterText').val() || '';
        const handoffText = $('#aiHandoffText').val() || '';
        const isFooterOn = $('#aiFooterEnabled').is(':checked');

        $('#previewFooterText').text(footerText);
        if (isFooterOn) {
            $('#previewFooterWrapper').removeClass('hidden');
        } else {
            $('#previewFooterWrapper').addClass('hidden');
        }

        $('#previewHandoffText').text(handoffText);
    }

    $('#aiFooterText, #aiHandoffText').on('input', updatePreviews);
    $('#aiFooterEnabled').on('change', updatePreviews);

    // Toggle notifikasi pesan selesai
    $('#aiReactivateNotify').on('change', function() {
        if ($(this).is(':checked')) {
            $('#reactivateMessageWrapper').removeClass('hidden');
        } else {
            $('#reactivateMessageWrapper').addClass('hidden');
        }
    });

    // ── Radio card styling listener ──
    $('input[name="ai_reactivate_duration"]').on('change', function() {
        $('.dur-card').each(function() {
            const radio = $(this).find('input[type="radio"]');
            const dot = $(this).find('.radio-dot');
            if (radio.is(':checked')) {
                $(this).addClass('border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500').removeClass('border-gray-200 text-gray-700');
                dot.addClass('bg-emerald-500').removeClass('bg-gray-300');
            } else {
                $(this).removeClass('border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold ring-1 ring-emerald-500').addClass('border-gray-200 text-gray-700');
                dot.removeClass('bg-emerald-500').addClass('bg-gray-300');
            }
        });
    });

    // ── Switch Device via Dropdown ──
    $('#selectDevice').on('change', function() {
        const devId = $(this).val();
        $('#hiddenDeviceId').val(devId);
        loadDeviceTemplate(devId);
    });

    function loadDeviceTemplate(deviceId) {
        if (!deviceId) return;
        const month = $('#filterMonth').val() || '';
        const year  = $('#filterYear').val() || '';

        $.ajax({
            url: BASEURL + `/aitemplate/getSettings?device_id=${deviceId}&month=${month}&year=${year}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.templates) {
                    const t = res.templates;
                    $('#aiFooterEnabled').prop('checked', t.ai_footer_enabled == 1);
                    $('#aiFooterText').val(t.ai_footer_text);
                    $('#aiHandoffText').val(t.ai_handoff_text);

                    // Duration
                    $(`input[name="ai_reactivate_duration"][value="${t.ai_reactivate_duration}"]`).prop('checked', true).trigger('change');

                    $('#aiReactivateKeyword').val(t.ai_reactivate_keyword);
                    $('#aiReactivateNotify').prop('checked', t.ai_reactivate_notify == 1).trigger('change');
                    $('#aiReactivateMessage').val(t.ai_reactivate_message);

                    updatePreviews();
                    renderMutedContacts(res.muted_contacts || []);
                }
            }
        });
    }

    function renderMutedContacts(list) {
        $('#mutedCountBadge').text(list.length + ' Kontak');
        const tbody = $('#mutedListBody');
        tbody.empty();

        // Reset check all & action bar
        $('#checkAllMuted').prop('checked', false);
        $('#bulkActionBar').addClass('hidden');

        if (list.length === 0) {
            $('#checkAllMuted').prop('disabled', true);
            tbody.append(`
                <tr id="rowEmptyMuted">
                    <td colspan="6" class="text-center py-8 text-gray-400 text-xs">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="font-medium text-gray-600">Tidak ada kontak yang sedang di-mute pada periode ini</span>
                            <span class="text-gray-400 text-[11px]">Seluruh percakapan WhatsApp dilayani otomatis oleh Bot AI secara normal.</span>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        $('#checkAllMuted').prop('disabled', false);

        list.forEach(mc => {
            const reasonBadge = mc.reason === 'chat_admin' 
                ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">💬 Minta Chat Admin</span>'
                : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">👤 Admin Menjawab</span>';

            const expiredBadge = mc.muted_until 
                ? `<span class="font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded">${mc.muted_until}</span>`
                : `<span class="text-gray-500">Besok 00:00</span>`;

            tbody.append(`
                <tr id="muted-row-${mc.id}" class="hover:bg-gray-50/60 transition">
                    <td class="py-3 px-3 text-center">
                        <input type="checkbox" class="check-muted-item w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer" value="${mc.id}" data-phone="${mc.phone}">
                    </td>
                    <td class="py-3 px-4 font-mono font-bold text-gray-800">${mc.phone}</td>
                    <td class="py-3 px-4">${reasonBadge}</td>
                    <td class="py-3 px-4 text-xs text-gray-500">${mc.created_at}</td>
                    <td class="py-3 px-4 text-xs">${expiredBadge}</td>
                    <td class="py-3 px-4 text-center">
                        <button type="button" onclick="unmuteContact(${mc.id}, '${mc.phone}')" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 font-semibold rounded-lg text-xs transition border border-emerald-200 flex items-center gap-1 mx-auto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Aktifkan AI Sekarang
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // ── Checkbox Selection State Handler ──
    function updateSelectedMutedState() {
        const checkedBoxes = $('.check-muted-item:checked');
        const totalBoxes = $('.check-muted-item');
        const count = checkedBoxes.length;

        if (count > 0) {
            $('#bulkActionBar').removeClass('hidden');
            $('#selectedCountText').text(`${count} kontak dipilih`);
        } else {
            $('#bulkActionBar').addClass('hidden');
        }

        if (totalBoxes.length > 0 && count === totalBoxes.length) {
            $('#checkAllMuted').prop('checked', true);
        } else {
            $('#checkAllMuted').prop('checked', false);
        }
    }

    $(document).on('change', '.check-muted-item', updateSelectedMutedState);

    $('#checkAllMuted').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.check-muted-item').prop('checked', isChecked);
        updateSelectedMutedState();
    });

    $('#btnCancelSelection').on('click', function() {
        $('.check-muted-item').prop('checked', false);
        $('#checkAllMuted').prop('checked', false);
        updateSelectedMutedState();
    });

    // ── Filter Bulan & Tahun Listener ──
    $('#filterMonth, #filterYear').on('change', function() {
        reloadMutedList();
    });

    $('#btnCurrentMonth').on('click', function() {
        const now = new Date();
        $('#filterMonth').val(now.getMonth() + 1);
        $('#filterYear').val(now.getFullYear());
        reloadMutedList();
    });

    window.reloadMutedList = function() {
        const devId = $('#hiddenDeviceId').val();
        const month = $('#filterMonth').val();
        const year  = $('#filterYear').val();
        if (!devId) return;

        $('#mutedCountBadge').text('Memuat...');
        $.ajax({
            url: BASEURL + `/aitemplate/getMutedContacts?device_id=${devId}&month=${month}&year=${year}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    renderMutedContacts(res.muted_contacts || []);
                }
            },
            error: function() {
                $('#mutedCountBadge').text('0 Kontak');
            }
        });
    };

    // ── Bulk Delete (Hapus Terpilih) ──
    $('#btnDeleteSelectedMuted').on('click', function() {
        const devId = $('#hiddenDeviceId').val();
        const ids = $('.check-muted-item:checked').map(function() { return $(this).val(); }).get();
        if (!ids.length) return;

        Swal.fire({
            title: 'Hapus Kontak Terpilih?',
            text: `Bot AI akan langsung diaktifkan kembali untuk ${ids.length} kontak yang dipilih.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: `Ya, Hapus (${ids.length})`,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mengaktifkan kembali Bot AI untuk kontak terpilih',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: BASEURL + '/aitemplate/deleteMutedBulk',
                    type: 'POST',
                    data: { device_id: devId, ids: ids },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            reloadMutedList();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Tidak dapat terhubung ke server.' });
                    }
                });
            }
        });
    });

    // ── Delete All (Hapus Semua Kontak Mute pada Periode Terpilih) ──
    $('#btnDeleteAllMuted').on('click', function() {
        const devId = $('#hiddenDeviceId').val();
        const month = $('#filterMonth').val();
        const year  = $('#filterYear').val();
        const monthText = $('#filterMonth option:selected').text();

        Swal.fire({
            title: 'Hapus SEMUA Kontak Mute?',
            text: `Seluruh kontak yang sedang di-mute pada periode ${monthText} ${year} akan diaktifkan kembali Bot AI-nya!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mengaktifkan kembali Bot AI untuk seluruh kontak',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: BASEURL + '/aitemplate/deleteAllMuted',
                    type: 'POST',
                    data: { device_id: devId, month: month, year: year },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            reloadMutedList();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Tidak dapat terhubung ke server.' });
                    }
                });
            }
        });
    });

    // ── Save Template Settings ──
    $('#btnSubmitTop').on('click', function() {
        $('#formAiTemplate').submit();
    });

    $('#formAiTemplate').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        Swal.fire({
            title: 'Menyimpan Pengaturan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: BASEURL + '/aitemplate/save',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message || 'Terjadi kesalahan saat menyimpan.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Tidak dapat terhubung ke server.'
                });
            }
        });
    });

    // ── Reset Defaults ──
    $('#btnResetDefaults').on('click', function() {
        Swal.fire({
            title: 'Kembalikan ke Default?',
            text: 'Format tulisan footer, pesan handoff, dan pengaturan akan dikembalikan ke teks standar bawaan sistem.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Kembalikan Default',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASEURL + '/aitemplate/resetDefaults',
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success' && res.defaults) {
                            const d = res.defaults;
                            $('#aiFooterEnabled').prop('checked', d.ai_footer_enabled == 1);
                            $('#aiFooterText').val(d.ai_footer_text);
                            $('#aiHandoffText').val(d.ai_handoff_text);
                            $(`input[name="ai_reactivate_duration"][value="${d.ai_reactivate_duration}"]`).prop('checked', true).trigger('change');
                            $('#aiReactivateKeyword').val(d.ai_reactivate_keyword);
                            $('#aiReactivateNotify').prop('checked', d.ai_reactivate_notify == 1).trigger('change');
                            $('#aiReactivateMessage').val(d.ai_reactivate_message);

                            updatePreviews();

                            Swal.fire({
                                icon: 'success',
                                title: 'Reset Berhasil',
                                text: 'Teks template telah diisi nilai default. Klik "Simpan Perubahan" untuk menerapkan.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    }
                });
            }
        });
    });

    // ── Helper Unmute Contact (Individual) ──
    window.unmuteContact = function(id, phone) {
        const devId = $('#hiddenDeviceId').val();
        Swal.fire({
            title: 'Aktifkan AI Kembali?',
            text: `Bot AI akan langsung aktif kembali merespons kontak ${phone}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Aktifkan AI',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASEURL + '/aitemplate/unmuteContact',
                    type: 'POST',
                    data: { id: id, device_id: devId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'AI Diaktifkan!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            reloadMutedList();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                        }
                    }
                });
            }
        });
    };

    // ── Helper Insert Textarea Formatting (Syntax Error Fixed) ──
    window.insertFormatting = function(textareaId, textToInsert, cursorOffset) {
        const textarea = document.getElementById(textareaId);
        if (!textarea) return;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + textToInsert + text.substring(end);
        textarea.focus();
        if (cursorOffset !== undefined) {
            textarea.setSelectionRange(start + cursorOffset, start + cursorOffset);
        }
    };

    // ── Helper Append Keyword (Multi-Keywords Tag) ──
    window.appendKeyword = function(keyword) {
        const textarea = document.getElementById('aiReactivateKeyword');
        if (!textarea) return;
        let current = textarea.value.trim();
        const kwList = current.split(/[\r\n,]+/).map(k => k.trim().toLowerCase()).filter(Boolean);
        if (!kwList.includes(keyword.toLowerCase())) {
            textarea.value = current ? (current + ', ' + keyword) : keyword;
        }
        textarea.focus();
    };
});
</script>
