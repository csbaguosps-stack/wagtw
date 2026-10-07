<?php
// Hitung metrik ringkasan untuk 4 kartu statistik
$totalCampaigns = count($data['campaigns']);
$runningCampaigns = 0;
$totalSent = 0;
$totalFailed = 0;
$totalRecipients = 0;

foreach ($data['campaigns'] as $camp) {
    if ($camp['status'] === 'running') $runningCampaigns++;
    $totalSent += intval($camp['sent'] ?? 0);
    $totalFailed += intval($camp['failed'] ?? 0);
    $totalRecipients += intval($camp['total'] ?? 0);
}

$processed = $totalSent + $totalFailed;
$successRate = $processed > 0 ? round(($totalSent / $processed) * 100, 1) : 100;
?>

<!-- SheetJS for Excel Reading -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<div id="broadcast-container" class="space-y-6 max-w-7xl mx-auto mt-2 px-3 sm:px-6 pb-16">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-7 rounded-3xl text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
            <svg class="w-64 h-64 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"/></svg>
        </div>

        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                WhatsApp Anti-Banned Engine
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                Broadcast Kampanye
            </h1>
            <p class="text-slate-300 text-sm mt-1 max-w-xl leading-relaxed">
                Kelola pengiriman pesan massal cerdas dengan jeda acak dinamis, batch pacing, dan rotasi spintax otomatis.
            </p>
        </div>

        <div class="relative z-10 shrink-0">
            <a href="<?= BASEURL ?>/broadcast/create" data-nav class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Buat Broadcast Baru
            </a>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Card 1: Total Campaign -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider truncate">Total Campaign</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5 sm:mt-1"><?= number_format($totalCampaigns) ?></h3>
                <span class="text-[11px] sm:text-xs text-indigo-600 font-medium truncate block">Semua daftar antrean</span>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center p-2.5 sm:p-3 shadow-inner shrink-0 ml-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>

        <!-- Card 2: Sedang Berjalan -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider truncate">Sedang Berjalan</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5 sm:mt-1 flex items-center gap-1.5">
                    <?= number_format($runningCampaigns) ?>
                    <?php if ($runningCampaigns > 0): ?>
                    <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <?php endif; ?>
                </h3>
                <span class="text-[11px] sm:text-xs text-emerald-600 font-medium truncate block"><?= $runningCampaigns > 0 ? 'Pekerja aktif berjalan' : 'Tidak ada pengiriman' ?></span>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center p-2.5 sm:p-3 shadow-inner shrink-0 ml-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Card 3: Pesan Terkirim -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider truncate">Pesan Terkirim</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5 sm:mt-1"><?= number_format($totalSent) ?></h3>
                <span class="text-[11px] sm:text-xs text-blue-600 font-medium truncate block">Dari <?= number_format($totalRecipients) ?> total</span>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center p-2.5 sm:p-3 shadow-inner shrink-0 ml-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Card 4: Tingkat Keberhasilan -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider truncate">Keberhasilan</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5 sm:mt-1"><?= $successRate ?>%</h3>
                <span class="text-[11px] sm:text-xs text-rose-500 font-medium truncate block"><?= number_format($totalFailed) ?> pesan gagal</span>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center p-2.5 sm:p-3 shadow-inner shrink-0 ml-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
    </div>

    <!-- Main List Card -->
    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/40">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-800">Daftar Antrean & Riwayat Campaign</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pantau status pengiriman berkala dan kontrol eksekusi secara real-time.</p>
            </div>
        </div>

        <?php if (empty($data['campaigns'])): ?>
        <!-- Friendly Empty State -->
        <div class="p-10 sm:p-16 text-center max-w-lg mx-auto">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-4 sm:mb-5 shadow-sm border border-indigo-100/60">
                <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <h4 class="text-base sm:text-lg font-bold text-slate-800 mb-1">Belum Ada Campaign Broadcast</h4>
            <p class="text-xs sm:text-sm text-slate-500 mb-5 sm:mb-6 leading-relaxed">
                Anda belum membuat antrean pengiriman pesan broadcast. Buat campaign sekarang untuk mulai menyapa ribuan kontak secara aman.
            </p>
            <a href="<?= BASEURL ?>/broadcast/create" data-nav class="inline-flex items-center gap-2 px-5 py-2.5 sm:px-6 sm:py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Buat Broadcast Pertama
            </a>
        </div>
        <?php else: ?>
        <!-- Data Table -->
        <div class="overflow-x-auto p-2 sm:p-4">
            <table class="w-full text-left border-collapse min-w-[680px]" id="table-campaign">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider border-b border-slate-100">
                        <th class="py-4 px-4 rounded-l-xl">Campaign & Device</th>
                        <th class="py-4 px-4">Metode Anti-Banned</th>
                        <th class="py-4 px-4">Progres Pengiriman</th>
                        <th class="py-4 px-4">Status</th>
                        <th class="py-4 px-4 text-right rounded-r-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach ($data['campaigns'] as $c): ?>
                    <tr class="hover:bg-slate-50/70 transition-colors group" data-campaign-id="<?= $c['id'] ?>" data-campaign-status="<?= $c['status'] ?>">
                        <!-- Campaign & Device -->
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-800 text-base group-hover:text-indigo-600 transition-colors">
                                <?= htmlspecialchars($c['name']) ?>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <?= htmlspecialchars($c['device_name'] ?? 'Device') ?>
                                    <?php if (!empty($c['device_phone'])): ?>
                                        <span class="text-slate-400 font-normal">(+<?= htmlspecialchars($c['device_phone']) ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($c['media_path'])): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-xs font-semibold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    Media
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Tombol Langsung Lihat Riwayat Penerima -->
                            <div class="mt-2">
                                <button type="button" onclick="openCampaignHistory(<?= $c['id'] ?>)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[11px] transition border border-blue-200/60 shadow-xs">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Riwayat Penerima</span>
                                    <span class="px-1.5 py-0.5 rounded-md bg-white text-[10px] text-slate-700 font-mono border border-slate-200">
                                        <span class="text-emerald-600 font-bold camp-sent-counter" data-id="<?= $c['id'] ?>"><?= $c['sent'] ?></span> Terkirim
                                        <?php if ($c['failed'] > 0): ?>
                                            • <span class="text-rose-500 font-bold camp-failed-counter" data-id="<?= $c['id'] ?>"><?= $c['failed'] ?></span> Gagal
                                        <?php endif; ?>
                                    </span>
                                </button>
                            </div>
                        </td>

                        <!-- Anti Banned Methods -->
                        <td class="py-4 px-4">
                            <div class="space-y-1">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50/80 text-indigo-700 text-xs font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Jeda <?= $c['delay_min'] ?>-<?= $c['delay_max'] ?> detik
                                </div>
                                <?php if ($c['batch_send'] > 0 && $c['batch_sleep'] > 0): ?>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-semibold block w-fit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                    Batch: <?= $c['batch_send'] ?> pesan / <?= $c['batch_sleep'] ?> mnt
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Progress Bar -->
                        <td class="py-4 px-4 min-w-[180px]">
                          <?php 
                            $total = max(intval($c['total']), 1);
                            $sent = intval($c['sent']);
                            $failed = intval($c['failed']);
                            $pctSent = round(($sent / $total) * 100);
                            $pctFailed = round(($failed / $total) * 100);
                        ?>
                        <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                            <span class="text-indigo-600 camp-progress-label" data-id="<?= $c['id'] ?>"><?= $sent ?> / <?= $c['total'] ?> terkirim</span>
                            <span class="text-slate-400 font-medium camp-progress-pct" data-id="<?= $c['id'] ?>"><?= $pctSent ?>%</span>
                        </div>
                        <!-- Modern Progress Track -->
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden flex shadow-inner">
                            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-2.5 transition-all duration-700 camp-bar-sent" data-id="<?= $c['id'] ?>" style="width: <?= $pctSent ?>%"></div>
                            <div class="bg-rose-500 h-2.5 transition-all duration-700 camp-bar-failed" data-id="<?= $c['id'] ?>" style="width: <?= $pctFailed ?>%"></div>
                        </div>
                        <div class="text-[11px] text-rose-500 font-semibold mt-1 flex items-center gap-1 camp-failed-note" data-id="<?= $c['id'] ?>"<?= $failed > 0 ? '' : ' style="display:none"' ?>>
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <span class="camp-failed-count" data-id="<?= $c['id'] ?>"><?= $failed ?></span> nomor tidak terkirim / gagal
                        </div>
                        </td>

                        <td class="py-4 px-4">
                            <span class="camp-status-badge" data-id="<?= $c['id'] ?>">
                            <?php 
                                switch($c['status']) {
                                    case 'running':
                                        echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Berjalan</span>';
                                        break;
                                    case 'paused':
                                        echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Jeda</span>';
                                        break;
                                    case 'done':
                                        if (intval($c['failed']) > 0 && intval($c['sent']) > 0) {
                                            echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Selesai Sebagian</span>';
                                        } elseif (intval($c['sent']) === 0 && intval($c['failed']) > 0) {
                                            echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Gagal Total</span>';
                                        } else {
                                            echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Selesai</span>';
                                        }
                                        break;
                                    case 'failed':
                                        echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Gagal</span>';
                                        break;
                                    default:
                                        echo '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Draft</span>';
                                }
                            ?>
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- History / Riwayat Penerima Button -->
                                <button onclick="openCampaignHistory(<?= $c['id'] ?>)" class="p-2.5 bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white rounded-xl transition shadow-xs" title="Riwayat & Detail Penerima">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>

                                <!-- Edit Campaign Button (Sebelum Mulai / saat tidak running) -->
                                <?php if ($c['status'] !== 'running'): ?>
                                <button onclick="openEditCampaign(<?= $c['id'] ?>)" class="p-2.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-600 hover:text-white rounded-xl transition shadow-xs" title="Edit Campaign Sebelum Mulai">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <?php endif; ?>

                                <?php if ($c['status'] === 'draft' || $c['status'] === 'paused'): ?>
                                <button onclick="actionCampaign(<?= $c['id'] ?>, 'start')" class="p-2.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white rounded-xl transition shadow-xs" title="Jalankan Pengiriman">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                </button>
                                <?php elseif ($c['status'] === 'running'): ?>
                                <button onclick="actionCampaign(<?= $c['id'] ?>, 'pause')" class="p-2.5 bg-amber-50 hover:bg-amber-600 text-amber-600 hover:text-white rounded-xl transition shadow-xs" title="Jeda Pengiriman">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6"/></svg>
                                </button>
                                <?php endif; ?>

                                <button onclick="actionCampaign(<?= $c['id'] ?>, 'delete')" class="p-2.5 bg-rose-50 hover:bg-rose-600 text-rose-500 hover:text-white rounded-xl transition shadow-xs" title="Hapus Campaign">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal Riwayat Penerima Broadcast -->
    <div id="modal-history-recipients" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-2 sm:p-4 md:p-6 flex items-center justify-center transition-opacity duration-200">
        <div class="bg-white w-full max-w-4xl rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[94vh] animate-in fade-in zoom-in duration-150">
            <!-- Modal Header -->
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-start sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-semibold uppercase tracking-wider shrink-0">
                            Riwayat Pengiriman
                        </span>
                        <span id="hist-camp-name" class="font-bold text-white text-sm sm:text-base break-words truncate max-w-[240px] sm:max-w-md">Campaign</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-300 mt-1 leading-normal">Daftar nomor dan nama target penerima yang berhasil, gagal, atau sedang menunggu.</p>
                </div>
                <button onclick="closeCampaignHistory()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- 4 Mini Summary Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50/70 border-b border-slate-100">
                <div class="bg-white p-2.5 sm:p-3 rounded-xl sm:rounded-2xl border border-slate-200/60 shadow-xs">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Target</span>
                    <h4 id="hist-stat-total" class="text-lg sm:text-xl font-black text-slate-800 mt-0.5">0</h4>
                </div>
                <div class="bg-white p-2.5 sm:p-3 rounded-xl sm:rounded-2xl border border-emerald-100 shadow-xs">
                    <span class="text-[10px] sm:text-[11px] font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span> Berhasil
                    </span>
                    <h4 id="hist-stat-sent" class="text-lg sm:text-xl font-black text-emerald-600 mt-0.5">0</h4>
                </div>
                <div class="bg-white p-2.5 sm:p-3 rounded-xl sm:rounded-2xl border border-rose-100 shadow-xs">
                    <span class="text-[10px] sm:text-[11px] font-bold text-rose-500 uppercase tracking-wider flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span> Gagal
                    </span>
                    <h4 id="hist-stat-failed" class="text-lg sm:text-xl font-black text-rose-600 mt-0.5">0</h4>
                </div>
                <div class="bg-white p-2.5 sm:p-3 rounded-xl sm:rounded-2xl border border-amber-100 shadow-xs">
                    <span class="text-[10px] sm:text-[11px] font-bold text-amber-600 uppercase tracking-wider flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span> Menunggu
                    </span>
                    <h4 id="hist-stat-pending" class="text-lg sm:text-xl font-black text-amber-600 mt-0.5">0</h4>
                </div>
            </div>

            <!-- Toolbar: Filter Pills + Search + Export Buttons -->
            <div class="p-3 sm:p-4 border-b border-slate-100 bg-white space-y-3">
                <!-- Baris 1: Filter Pills (Touch Scrollable on Mobile) -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1 bg-slate-100/90 p-1 rounded-xl text-xs font-semibold overflow-x-auto scrollbar-none touch-scroll w-full sm:w-auto">
                        <button type="button" onclick="setHistoryFilter('all')" id="btn-filter-all" class="px-3 py-1.5 rounded-lg transition bg-white text-slate-800 shadow-xs whitespace-nowrap">Semua (<span id="count-all">0</span>)</button>
                        <button type="button" onclick="setHistoryFilter('sent')" id="btn-filter-sent" class="px-3 py-1.5 rounded-lg transition text-slate-600 hover:text-slate-900 whitespace-nowrap">Berhasil (<span id="count-sent">0</span>)</button>
                        <button type="button" onclick="setHistoryFilter('failed')" id="btn-filter-failed" class="px-3 py-1.5 rounded-lg transition text-slate-600 hover:text-slate-900 whitespace-nowrap">Gagal (<span id="count-failed">0</span>)</button>
                        <button type="button" onclick="setHistoryFilter('pending')" id="btn-filter-pending" class="px-3 py-1.5 rounded-lg transition text-slate-600 hover:text-slate-900 whitespace-nowrap">Menunggu (<span id="count-pending">0</span>)</button>
                    </div>
                </div>

                <!-- Baris 2: Pencarian & Tombol Aksi -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                    <!-- Search Input -->
                    <div class="relative flex-1 sm:max-w-xs">
                        <input type="text" id="hist-search-input" oninput="renderHistoryTable()" placeholder="Cari nomor atau nama..." class="w-full pl-8 pr-3 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-indigo-400/20 focus:border-indigo-500 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center flex-wrap gap-1.5">
                        <button type="button" onclick="copySentNumbers()" title="Salin nomor terkirim ke clipboard" class="px-2.5 py-1.5 sm:px-3 sm:py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-emerald-200/70 shadow-xs shrink-0 active:scale-95">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>Salin Terkirim</span>
                        </button>
                        <button type="button" onclick="copyFailedNumbers()" title="Salin nomor yang gagal dikirim ke clipboard" class="px-2.5 py-1.5 sm:px-3 sm:py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-rose-200/70 shadow-xs shrink-0 active:scale-95">
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Salin Gagal</span>
                        </button>
                        <button type="button" onclick="exportRecipientsXLS()" title="Export data penerima ke file Excel (.xlsx)" class="px-2.5 py-1.5 sm:px-3 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs shrink-0 active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zm-1.5 8.5l1.8 3h-1.6l-1-1.8l-1 1.8H8.1l1.8-3l-1.7-3h1.6l.9 1.7l.9-1.7h1.6l-1.7 3z"/></svg>
                            <span>Export Excel</span>
                        </button>
                        <button type="button" onclick="exportRecipientsCSV()" title="Export data penerima ke file CSV" class="px-2.5 py-1.5 sm:px-3 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-200/80 shadow-xs shrink-0 active:scale-95">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Export CSV</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Container (Scrollable & Responsive) -->
            <div class="overflow-y-auto max-h-[52vh] p-2.5 sm:p-4 bg-slate-50/40">
                <div class="overflow-x-auto rounded-2xl border border-slate-200/70 bg-white shadow-xs">
                    <table class="w-full text-left text-xs border-collapse min-w-[600px]">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-slate-50/95 backdrop-blur-xs text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200/80">
                                <th class="py-3 px-3 w-12 text-center">#</th>
                                <th class="py-3 px-3 min-w-[150px]">Nomor WhatsApp</th>
                                <th class="py-3 px-3 min-w-[130px]">Nama Penerima</th>
                                <th class="py-3 px-3 min-w-[105px]">Status</th>
                                <th class="py-3 px-3 min-w-[160px]">Waktu Kirim / Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="hist-recipients-tbody" class="divide-y divide-slate-100">
                            <!-- Rendered via JS -->
                        </tbody>
                    </table>
                    <div id="hist-empty-state" class="hidden text-center py-12 px-4">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-slate-500 text-xs font-semibold">Tidak ada target penerima yang sesuai filter.</p>
                        <p class="text-slate-400 text-[11px] mt-0.5">Coba ubah kata kunci pencarian atau tab filter di atas.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 sm:p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between flex-wrap gap-2">
                <div class="text-[11px] text-slate-500 font-medium">
                    Menampilkan <span id="hist-visible-count" class="font-bold text-slate-700">0</span> dari <span id="hist-total-count" class="font-bold text-slate-700">0</span> penerima
                </div>
                <button type="button" onclick="closeCampaignHistory()" class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition active:scale-95">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Edit Campaign (Sebelum Mulai) -->
    <div id="modal-edit-campaign" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-2 sm:p-4 md:p-6 flex items-center justify-center transition-opacity duration-200">
        <div class="bg-white w-full max-w-4xl rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[94vh] animate-in fade-in zoom-in duration-150">
            <!-- Modal Header -->
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-start sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold uppercase tracking-wider shrink-0">
                            Edit Campaign
                        </span>
                        <span class="font-bold text-white text-sm sm:text-base truncate">Ubah Pengaturan & Target</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-300 mt-1 leading-normal">Perbarui detail kampanye, nomor target penerima, atau pesan sebelum mulai dikirim.</p>
                </div>
                <button onclick="closeEditCampaign()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Form Content (Scrollable) -->
            <form id="form-edit-campaign" class="overflow-y-auto max-h-[75vh] p-4 sm:p-6 space-y-4 sm:space-y-5">
                <input type="hidden" name="id" id="edit-camp-id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    <!-- Kolom Kiri: Info Dasar & Anti-Banned -->
                    <div class="space-y-4">
                        <!-- Info Dasar -->
                        <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-200/70 space-y-3">
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Informasi Utama
                            </h4>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Campaign</label>
                                <input type="text" name="name" id="edit-camp-name" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-300 outline-none text-xs font-medium">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Device Pengirim</label>
                                <select name="device_id" id="edit-camp-device-id" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-300 outline-none text-xs font-medium">
                                    <option value="">-- Pilih Device --</option>
                                    <?php if (!empty($data['devices'])): ?>
                                        <?php foreach ($data['devices'] as $d): ?>
                                            <option value="<?= $d['id'] ?>">📱 <?= htmlspecialchars($d['name']) ?> (+<?= htmlspecialchars($d['phone']) ?>)</option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Anti Banned Timing -->
                        <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100 space-y-3">
                            <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Proteksi Anti-Banned
                            </h4>

                            <div>
                                <label class="block text-xs font-semibold text-indigo-900 mb-1">Jeda Waktu Acak (Detik)</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" min="1" name="delay_min" id="edit-camp-delay-min" value="10" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs font-bold text-center">
                                    <span class="text-xs text-indigo-500 font-bold">sd.</span>
                                    <input type="number" min="2" name="delay_max" id="edit-camp-delay-max" value="25" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs font-bold text-center">
                                    <span class="text-xs font-bold text-indigo-700">Detik</span>
                                </div>
                            </div>

                            <div class="h-px bg-indigo-100"></div>

                            <div>
                                <label class="block text-xs font-semibold text-indigo-900 mb-1">Batch Pacing (Istirahat)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <span class="text-[10px] text-indigo-600 font-medium">Kirim sebanyak</span>
                                        <input type="number" min="0" name="batch_send" id="edit-camp-batch-send" value="20" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs font-bold">
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-indigo-600 font-medium">Lalu jeda (menit)</span>
                                        <input type="number" min="0" name="batch_sleep" id="edit-camp-batch-sleep" value="10" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl outline-none text-xs font-bold">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Attachment -->
                        <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-200/70 space-y-2">
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                File / Gambar Media (Opsional)
                            </h4>
                            
                            <div id="edit-current-media-box" class="hidden p-2.5 rounded-xl bg-purple-50 border border-purple-200 text-xs flex items-center justify-between">
                                <div class="flex items-center gap-2 text-purple-800 truncate">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span id="edit-current-media-name" class="truncate font-semibold">media.jpg</span>
                                </div>
                                <label class="inline-flex items-center gap-1.5 text-rose-600 hover:text-rose-700 font-bold cursor-pointer shrink-0">
                                    <input type="checkbox" name="remove_media" value="1" id="edit-remove-media" class="rounded text-rose-600">
                                    <span>Hapus Media</span>
                                </label>
                            </div>

                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Ganti atau unggah media baru:</label>
                                <input type="file" name="media" class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Daftar Target & Konten Pesan -->
                    <div class="space-y-4">
                        <!-- Target Nomor -->
                        <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-200/70 space-y-2">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Target Nomor & Nama
                                </h4>
                                <span id="edit-target-badge" class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">0 Target</span>
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[11px] text-slate-500">Format: <code>Nomor, Nama</code> per baris.</p>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" onclick="downloadSampleBroadcastFile('xlsx')" class="bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 shadow-xs cursor-pointer" title="Download Contoh Format Excel (.xlsx)">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Contoh Format
                                    </button>
                                    <input type="file" id="edit-excel-upload" accept=".xlsx, .xls, .csv" class="hidden">
                                    <button type="button" onclick="document.getElementById('edit-excel-upload').click()" class="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 shadow-xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Import Excel
                                    </button>
                                </div>
                            </div>

                            <textarea name="targets" id="edit-camp-targets" rows="5" required oninput="updateEditTargetCounter()" placeholder="08123xxx, Nama&#10;08571xxx, Budi" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-300 outline-none text-xs font-mono"></textarea>
                        </div>

                        <!-- Konten Pesan -->
                        <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-200/70 space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    Konten Pesan
                                </h4>
                                <div class="flex items-center gap-1 text-[11px]">
                                    <button type="button" onclick="insertEditTag('[Name]')" class="px-2 py-0.5 rounded bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold transition">+ [Name]</button>
                                    <button type="button" onclick="insertEditTag('{Hai|Halo|Salam}')" class="px-2 py-0.5 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-semibold transition">+ Spintax</button>
                                </div>
                            </div>

                            <textarea name="message" id="edit-camp-message" rows="5" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none text-xs"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeEditCampaign()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" id="btn-save-edit-camp" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function initBroadcastTable() {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#table-campaign')) {
        $('#table-campaign').DataTable().destroy();
    }
    
    if ($.fn.DataTable && $('#table-campaign').length) {
        $('#table-campaign').DataTable({
            responsive: true,
            ordering: false,
            pageLength: 10,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Cari campaign...",
                lengthMenu: "Tampil _MENU_ data",
                info: "Menampilkan _START_ sd _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                zeroRecords: "Campaign tidak ditemukan",
                paginate: {
                    previous: "← Prev",
                    next: "Next →"
                }
            },
            dom: '<"flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-white border-b border-slate-100"<"flex items-center gap-2"l><"w-full sm:w-64"f>>rt<"flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-white border-t border-slate-100"<"text-xs text-slate-500"i><"text-xs"p>>'
        });
    }
}

// Inisialisasi DataTable langsung (kompatibel penuh dengan AJAX load)
initBroadcastTable();

// ══════════════════════════════════════════════════════════════
// REAL-TIME AUTO POLLING — Update progress tanpa reload halaman
// ══════════════════════════════════════════════════════════════
(function() {
    let _pollTimer = null;
    let _pollActive = false;

    // Helper: render status badge HTML dari cstatus + sent + failed
    function renderStatusBadge(cstatus, sent, failed) {
        if (cstatus === 'running') {
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Berjalan</span>';
        } else if (cstatus === 'paused') {
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Jeda</span>';
        } else if (cstatus === 'done') {
            if (failed > 0 && sent > 0) {
                return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Selesai Sebagian</span>';
            } else if (sent === 0 && failed > 0) {
                return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Gagal Total</span>';
            } else {
                return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Selesai</span>';
            }
        } else if (cstatus === 'failed') {
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Gagal</span>';
        }
        return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-xs font-bold tracking-wide uppercase"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Draft</span>';
    }

    function doPoll() {
        if (_pollActive) return; // Hindari request ganda
        _pollActive = true;

        fetch(BASEURL + '/broadcast/progress')
            .then(r => r.json())
            .then(res => {
                _pollActive = false;
                if (res.status !== 'success' || !Array.isArray(res.campaigns)) return;

                let anyRunning = false;

                res.campaigns.forEach(c => {
                    const id    = c.id;
                    const sent  = c.sent;
                    const failed = c.failed;
                    const total = Math.max(c.total, 1);
                    const pctSent   = Math.round((sent / total) * 100);
                    const pctFailed = Math.round((failed / total) * 100);

                    if (c.cstatus === 'running') anyRunning = true;

                    // -- Progress bar --
                    const barSent = document.querySelector(`.camp-bar-sent[data-id="${id}"]`);
                    if (barSent) barSent.style.width = pctSent + '%';

                    const barFailed = document.querySelector(`.camp-bar-failed[data-id="${id}"]`);
                    if (barFailed) barFailed.style.width = pctFailed + '%';

                    // -- Label "X / Y terkirim" --
                    const label = document.querySelector(`.camp-progress-label[data-id="${id}"]`);
                    if (label) label.textContent = sent + ' / ' + c.total + ' terkirim';

                    // -- Persen --
                    const pct = document.querySelector(`.camp-progress-pct[data-id="${id}"]`);
                    if (pct) pct.textContent = pctSent + '%';

                    // -- Counter di tombol riwayat --
                    const cntSent = document.querySelector(`.camp-sent-counter[data-id="${id}"]`);
                    if (cntSent) cntSent.textContent = sent;

                    const cntFailed = document.querySelector(`.camp-failed-counter[data-id="${id}"]`);
                    if (cntFailed) cntFailed.textContent = failed;

                    // -- Note gagal (teks "X nomor gagal") --
                    const noteEl = document.querySelector(`.camp-failed-note[data-id="${id}"]`);
                    if (noteEl) {
                        noteEl.style.display = failed > 0 ? '' : 'none';
                        const countEl = noteEl.querySelector(`.camp-failed-count[data-id="${id}"]`);
                        if (countEl) countEl.textContent = failed;
                    }

                    // -- Status Badge --
                    const badge = document.querySelector(`.camp-status-badge[data-id="${id}"]`);
                    if (badge) {
                        const row = badge.closest('tr[data-campaign-id]');
                        const prevStatus = row ? row.getAttribute('data-campaign-status') : null;

                        // Hanya update jika status berubah (kurangi DOM thrashing)
                        if (prevStatus !== c.cstatus) {
                            badge.innerHTML = renderStatusBadge(c.cstatus, sent, failed);
                            if (row) row.setAttribute('data-campaign-status', c.cstatus);
                        }
                    }
                });

                // -- Jadwal poll berikutnya --
                // Jika ada campaign running: setiap 3 detik
                // Jika semua selesai: setiap 15 detik (hemat request)
                const nextInterval = anyRunning ? 3000 : 15000;
                _pollTimer = setTimeout(doPoll, nextInterval);
            })
            .catch(() => {
                _pollActive = false;
                // Retry setelah 10 detik jika error
                _pollTimer = setTimeout(doPoll, 10000);
            });
    }

    // Mulai polling pertama setelah 2 detik (beri waktu halaman render)
    _pollTimer = setTimeout(doPoll, 2000);

    // Bersihkan timer saat user navigasi keluar (SPA mode)
    window.addEventListener('beforeunload', () => clearTimeout(_pollTimer));
    document.addEventListener('page:before-cache', () => clearTimeout(_pollTimer));
})();


function actionCampaign(id, action) {
    if (action === 'delete') {
        Swal.fire({
            title: 'Hapus Campaign?',
            text: 'Semua antrean target pada campaign ini akan ikut terhapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hapus!'
        }).then(result => {
            if (result.isConfirmed) {
                doAction(id, action);
            }
        });
    } else {
        doAction(id, action);
    }
}

function doAction(id, action) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('action', action);

    Swal.fire({
        title: 'Memproses...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(BASEURL + '/broadcast/action', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: res.message,
                timer: 1000,
                showConfirmButton: false
            });
            setTimeout(() => {
                if (typeof window.loadPage === 'function') {
                    window.loadPage(BASEURL + '/broadcast', false);
                } else {
                    location.reload();
                }
            }, 1000);
        } else {
            Swal.fire('Gagal', res.message, 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Terjadi kesalahan jaringan atau server.', 'error');
    });
}

// ----------------- RIWAYAT / HISTORY PENERIMA -----------------
window.wagtwHistoryRecipients = window.wagtwHistoryRecipients || [];
window.wagtwHistoryFilter = window.wagtwHistoryFilter || 'all';
window.wagtwHistoryCampaign = window.wagtwHistoryCampaign || {};

window.openCampaignHistory = function(id) {
    Swal.fire({
        title: 'Memuat Riwayat...',
        text: 'Mengambil data penerima broadcast.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(BASEURL + '/broadcast/recipients?id=' + id)
    .then(r => r.json())
    .then(res => {
        Swal.close();
        if (res.status !== 'success') {
            Swal.fire('Gagal', res.message || 'Gagal memuat data penerima', 'error');
            return;
        }

        const c = res.campaign || {};
        window.wagtwHistoryCampaign = c;
        window.wagtwHistoryRecipients = Array.isArray(res.recipients) ? res.recipients : [];
        window.wagtwHistoryFilter = 'all';

        // Update modal header & stats
        $('#hist-camp-name').text(c.name || ('Campaign #' + (c.id || id)));
        
        let sentCount = 0;
        let failedCount = 0;
        let pendingCount = 0;
        
        window.wagtwHistoryRecipients.forEach(r => {
            if (r.status === 'sent') sentCount++;
            else if (r.status === 'failed') failedCount++;
            else pendingCount++;
        });

        $('#hist-stat-total').text(window.wagtwHistoryRecipients.length);
        $('#hist-stat-sent').text(sentCount);
        $('#hist-stat-failed').text(failedCount);
        $('#hist-stat-pending').text(pendingCount);

        $('#count-all').text(window.wagtwHistoryRecipients.length);
        $('#count-sent').text(sentCount);
        $('#count-failed').text(failedCount);
        $('#count-pending').text(pendingCount);

        $('#hist-search-input').val('');
        window.setHistoryFilter('all');

        $('#modal-history-recipients').removeClass('hidden');
    })
    .catch(err => {
        Swal.fire('Error', 'Terjadi kesalahan jaringan atau server: ' + (err ? err.message : 'Unknown error'), 'error');
    });
};

window.closeCampaignHistory = function() {
    $('#modal-history-recipients').addClass('hidden');
};

window.setHistoryFilter = function(filter) {
    window.wagtwHistoryFilter = filter;
    ['all', 'sent', 'failed', 'pending'].forEach(f => {
        const btn = $('#btn-filter-' + f);
        if (f === filter) {
            btn.addClass('bg-white text-slate-800 shadow-xs').removeClass('text-slate-600');
        } else {
            btn.removeClass('bg-white text-slate-800 shadow-xs').addClass('text-slate-600');
        }
    });
    window.renderHistoryTable();
};

window.getFilteredRecipients = function() {
    const list = window.wagtwHistoryRecipients || [];
    const filter = window.wagtwHistoryFilter || 'all';
    const search = ($('#hist-search-input').val() || '').toLowerCase().trim();

    return list.filter(r => {
        if (filter !== 'all' && r.status !== filter) return false;
        if (search) {
            const phone = String(r.phone || '').toLowerCase();
            const name = String(r.name || '').toLowerCase();
            if (!phone.includes(search) && !name.includes(search)) return false;
        }
        return true;
    });
};

window.renderHistoryTable = function() {
    const search = ($('#hist-search-input').val() || '').toLowerCase().trim();
    const tbody = $('#hist-recipients-tbody');
    tbody.empty();

    let visibleCount = 0;
    const recipients = window.wagtwHistoryRecipients || [];

    recipients.forEach(r => {
        if (window.wagtwHistoryFilter !== 'all' && r.status !== window.wagtwHistoryFilter) {
            return;
        }

        const phone = String(r.phone || '');
        const name = String(r.name || '');

        if (search) {
            if (!phone.toLowerCase().includes(search) && !name.toLowerCase().includes(search)) {
                return;
            }
        }

        visibleCount++;

        let statusBadge = '';
        if (r.status === 'sent') {
            statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px]">
                <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Terkirim
            </span>`;
        } else if (r.status === 'failed') {
            statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[11px]" title="${escapeHtml(r.error_msg || 'Gagal terkirim')}">
                <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                Gagal
            </span>`;
        } else {
            statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[11px]">
                <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Menunggu
            </span>`;
        }

        let timeDetail = '<span class="text-slate-400 font-mono">-</span>';
        if (r.status === 'sent' && r.sent_at) {
            timeDetail = `<span class="text-slate-600 font-medium font-mono text-[11px]">${escapeHtml(r.sent_at)}</span>`;
        } else if (r.status === 'failed') {
            timeDetail = `<span class="text-rose-500 font-medium text-[11px]" title="${escapeHtml(r.error_msg || 'Pengiriman gagal')}">${escapeHtml(r.error_msg || 'Pengiriman gagal')}</span>`;
        }

        const cleanPhone = phone.replace(/[^0-9]/g, '');
        const displayPhone = cleanPhone ? (cleanPhone.startsWith('62') ? '+' + cleanPhone : cleanPhone) : phone;

        const tr = `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-xs">${visibleCount}</td>
                <td class="py-2.5 px-3">
                    <a href="https://wa.me/${escapeHtml(cleanPhone)}" target="_blank" class="font-bold text-slate-800 hover:text-emerald-600 inline-flex items-center gap-1.5 transition text-xs font-mono">
                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.771.785 2.796.785 3.182 0 5.768-2.587 5.769-5.766 0-3.181-2.587-5.771-5.769-5.771zm3.374 8.211c-.14.394-.712.729-1.026.774-.314.045-.694.062-2.072-.511-1.654-.687-2.719-2.378-2.802-2.489-.083-.111-.673-.896-.673-1.711 0-.814.428-1.215.581-1.378.152-.163.332-.204.442-.204.111 0 .222.001.319.006.103.006.242-.039.378.291.141.341.482 1.177.525 1.263.042.086.07.188.014.3-.056.111-.084.18-.167.277-.083.097-.174.217-.249.292-.083.084-.17.175-.073.342.097.167.433.714.93 1.156.638.569 1.176.745 1.343.828.167.083.264.069.362-.042.097-.111.417-.486.528-.653.111-.167.222-.139.375-.083.153.056.972.458 1.139.542.167.083.278.125.319.194.042.07.042.403-.098.797z"/></svg>
                        <span>${escapeHtml(displayPhone)}</span>
                    </a>
                </td>
                <td class="py-2.5 px-3 font-medium text-slate-700 text-xs">
                    ${name ? escapeHtml(name) : '<span class="text-slate-400 italic">-</span>'}
                </td>
                <td class="py-2.5 px-3">${statusBadge}</td>
                <td class="py-2.5 px-3">${timeDetail}</td>
            </tr>
        `;
        tbody.append(tr);
    });

    $('#hist-visible-count').text(visibleCount);
    $('#hist-total-count').text(recipients.length);

    if (visibleCount === 0) {
        $('#hist-empty-state').removeClass('hidden');
    } else {
        $('#hist-empty-state').addClass('hidden');
    }
};

window.copySentNumbers = function() {
    const sentList = (window.wagtwHistoryRecipients || []).filter(r => r.status === 'sent').map(r => r.phone);
    if (!sentList.length) {
        Swal.fire('Info', 'Belum ada nomor yang berstatus terkirim.', 'info');
        return;
    }
    navigator.clipboard.writeText(sentList.join('\n')).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: `${sentList.length} nomor terkirim disalin ke clipboard.`,
            timer: 1500,
            showConfirmButton: false
        });
    });
};

window.copyFailedNumbers = function() {
    const failedList = (window.wagtwHistoryRecipients || []).filter(r => r.status === 'failed').map(r => r.phone);
    if (!failedList.length) {
        Swal.fire('Info', 'Tidak ada nomor yang berstatus gagal pada campaign ini.', 'info');
        return;
    }
    navigator.clipboard.writeText(failedList.join('\n')).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: `${failedList.length} nomor gagal dikirim disalin ke clipboard.`,
            timer: 1500,
            showConfirmButton: false
        });
    });
};

// ----------------- EXPORT EXCEL (XLSX) -----------------
window.exportRecipientsXLS = function() {
    const list = window.getFilteredRecipients();
    if (!list.length) {
        Swal.fire('Info', 'Tidak ada data penerima yang sesuai filter untuk di-export.', 'info');
        return;
    }

    if (typeof XLSX === 'undefined') {
        Swal.fire('Error', 'Library Excel (SheetJS) belum selesai dimuat. Silakan refresh halaman.', 'error');
        return;
    }

    const campaign = window.wagtwHistoryCampaign || {};
    const campaignName = (campaign.name || 'Broadcast').replace(/[^a-zA-Z0-9_-]/g, '_');
    const dateStr = new Date().toISOString().slice(0, 10);

    // Build data array for worksheet
    const headers = ["No", "Nomor WhatsApp", "Nama Penerima", "Status", "Waktu Kirim", "Catatan Error"];
    const rows = [headers];

    list.forEach((r, i) => {
        let statusLabel = 'Menunggu';
        if (r.status === 'sent') statusLabel = 'Terkirim';
        else if (r.status === 'failed') statusLabel = 'Gagal';

        const phoneDigits = String(r.phone || '').replace(/[^0-9]/g, '');
        const phoneFormatted = phoneDigits ? '+' + phoneDigits : '';

        rows.push([
            i + 1,
            phoneFormatted,
            r.name || '',
            statusLabel,
            r.sent_at || '',
            r.error_msg || ''
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);

    // Explicitly set phone column as text string type ('s') so Excel doesn't format as scientific notation
    for (let R = 1; R < rows.length; ++R) {
        const cellRef = XLSX.utils.encode_cell({ c: 1, r: R });
        if (ws[cellRef]) {
            ws[cellRef].t = 's';
        }
    }

    // Auto calculate column widths
    ws['!cols'] = [
        { wch: 6 },
        { wch: 22 },
        { wch: 25 },
        { wch: 15 },
        { wch: 22 },
        { wch: 35 }
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Riwayat Penerima");

    const filename = `Riwayat_Broadcast_${campaignName}_${dateStr}.xlsx`;
    XLSX.writeFile(wb, filename);

    Swal.fire({
        icon: 'success',
        title: 'Export Excel Berhasil!',
        text: `${list.length} data penerima tersimpan ke ${filename}`,
        timer: 1800,
        showConfirmButton: false
    });
};

// ----------------- EXPORT CSV -----------------
window.exportRecipientsCSV = function() {
    const list = window.getFilteredRecipients();
    if (!list.length) {
        Swal.fire('Info', 'Tidak ada data penerima yang sesuai filter untuk di-export.', 'info');
        return;
    }

    const campaign = window.wagtwHistoryCampaign || {};
    const campaignName = (campaign.name || 'Broadcast').replace(/[^a-zA-Z0-9_-]/g, '_');
    const dateStr = new Date().toISOString().slice(0, 10);

    // UTF-8 BOM so Microsoft Excel handles Unicode/Indonesian characters properly
    let csv = "\uFEFFNo,Nomor WhatsApp,Nama Penerima,Status,Waktu Kirim,Catatan Error\r\n";

    list.forEach((r, i) => {
        let statusLabel = 'Menunggu';
        if (r.status === 'sent') statusLabel = 'Terkirim';
        else if (r.status === 'failed') statusLabel = 'Gagal';

        const phoneDigits = String(r.phone || '').replace(/[^0-9]/g, '');
        // In CSV opened in Excel, formatting as ="+62..." or "'+62..." preserves text format
        const phoneCol = phoneDigits ? `"'+${phoneDigits}"` : '""';
        const nameCol = `"${(r.name || '').replace(/"/g, '""')}"`;
        const statusCol = `"${statusLabel}"`;
        const sentAtCol = `"${(r.sent_at || '').replace(/"/g, '""')}"`;
        const errorCol = `"${(r.error_msg || '').replace(/"/g, '""')}"`;

        csv += `${i + 1},${phoneCol},${nameCol},${statusCol},${sentAtCol},${errorCol}\r\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    const filename = `Riwayat_Broadcast_${campaignName}_${dateStr}.csv`;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    Swal.fire({
        icon: 'success',
        title: 'Export CSV Berhasil!',
        text: `${list.length} data penerima tersimpan ke ${filename}`,
        timer: 1800,
        showConfirmButton: false
    });
};

// ----------------- EDIT CAMPAIGN (SEBELUM MULAI) -----------------
window.openEditCampaign = function(id) {
    Swal.fire({
        title: 'Memuat Data Campaign...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(BASEURL + '/broadcast/get?id=' + id)
    .then(r => r.json())
    .then(res => {
        Swal.close();
        if (res.status !== 'success') {
            Swal.fire('Gagal', res.message || 'Gagal memuat data campaign', 'error');
            return;
        }

        const c = res.campaign;
        $('#edit-camp-id').val(c.id);
        $('#edit-camp-name').val(c.name);
        $('#edit-camp-device-id').val(c.device_id);
        $('#edit-camp-delay-min').val(c.delay_min || 10);
        $('#edit-camp-delay-max').val(c.delay_max || 25);
        $('#edit-camp-batch-send').val(c.batch_send || 0);
        $('#edit-camp-batch-sleep').val(c.batch_sleep || 0);
        $('#edit-camp-message').val(c.message);
        $('#edit-camp-targets').val(res.target_text || '');
        
        // Media Preview
        if (c.media_path) {
            $('#edit-current-media-box').removeClass('hidden');
            $('#edit-current-media-name').text(c.media_path.split('/').pop());
            $('#edit-remove-media').prop('checked', false);
        } else {
            $('#edit-current-media-box').addClass('hidden');
            $('#edit-remove-media').prop('checked', false);
        }

        window.updateEditTargetCounter();
        $('#modal-edit-campaign').removeClass('hidden');
    })
    .catch(err => {
        Swal.fire('Error', 'Terjadi kesalahan jaringan atau server: ' + (err ? err.message : 'Unknown error'), 'error');
    });
};

window.closeEditCampaign = function() {
    $('#modal-edit-campaign').addClass('hidden');
};

window.updateEditTargetCounter = function() {
    const text = $('#edit-camp-targets').val() || '';
    const lines = text.split('\n');
    let count = 0;
    lines.forEach(l => {
        const clean = l.replace(/[^0-9]/g, '');
        if (clean.length >= 9) count++;
    });
    $('#edit-target-badge').text(count + ' Target Terdeteksi');
};

window.insertEditTag = function(tag) {
    const textarea = document.getElementById('edit-camp-message');
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + tag + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
};

// Edit Form Excel Import
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'edit-excel-upload') {
        const file = e.target.files[0];
        if (!file) return;

        Swal.fire({ title: 'Membaca Excel...', text: 'Tunggu sebentar.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

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
                        resultText += nameCol ? `${phoneDigits}, ${nameCol}\n` : `${phoneDigits}\n`;
                        validCount++;
                    }
                }

                if (validCount > 0) {
                    const existing = $('#edit-camp-targets').val();
                    $('#edit-camp-targets').val(existing ? existing + '\n' + resultText : resultText);
                    updateEditTargetCounter();
                    Swal.fire('Berhasil Terbaca!', `Sebanyak ${validCount} nomor target berhasil di-import dari Excel.`, 'success');
                } else {
                    Swal.fire('Peringatan!', 'Tidak ada data nomor telepon valid ditemukan di kolom A.', 'warning');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal memproses file Excel: ' + err.message, 'error');
            }
            e.target.value = '';
        };
        reader.readAsArrayBuffer(file);
    }
});

// Edit Form Submit Handler
$(document).on('submit', '#form-edit-campaign', function(e) {
    e.preventDefault();

    const fd = new FormData(this);
    if (!fd.get('device_id')) {
        Swal.fire('Error', 'Pilih device pengirim!', 'warning');
        return;
    }

    Swal.fire({
        title: 'Menyimpan Perubahan...',
        text: 'Memperbarui data campaign broadcast.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(BASEURL + '/broadcast/update', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            closeEditCampaign();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: res.message || 'Campaign berhasil diperbarui.',
                timer: 1200,
                showConfirmButton: false
            });
            setTimeout(() => {
                if (typeof window.loadPage === 'function') {
                    window.loadPage(BASEURL + '/broadcast', false);
                } else {
                    location.reload();
                }
            }, 1200);
        } else {
            Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat memperbarui', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Terjadi kesalahan jaringan atau server: ' + err.message, 'error');
    });
});

function downloadSampleBroadcastFile(format = 'xlsx') {
    window.location.href = BASEURL + '/broadcast/download_template/' + format;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

<?php if ($runningCampaigns > 0): ?>
// Live Polling otomatis setiap 5 detik saat campaign sedang berjalan
if (typeof window.registerInterval === 'function') {
    window.registerInterval(function() {
        if (window.location.href.includes('/broadcast') && !$('.swal2-shown').length && $('#modal-history-recipients').hasClass('hidden') && $('#modal-edit-campaign').hasClass('hidden')) {
            $.ajax({
                url: BASEURL + '/broadcast',
                type: 'GET',
                data: { ajax: true },
                cache: false,
                success: function(html) {
                    const newContent = $(html).find('#broadcast-container').html();
                    if (newContent) {
                        $('#broadcast-container').html(newContent);
                        initBroadcastTable();
                    }
                }
            });
        }
    }, 5000);
}
<?php endif; ?>
</script>

