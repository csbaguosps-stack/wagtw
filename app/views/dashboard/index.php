<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-white to-blue-50/50 rounded-2xl shadow-sm border border-blue-100 p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-blue-200">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-blue-100/50 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2.5 py-1 rounded-full">All Time</span>
        </div>
        <div>
            <h3 id="statTotalDevices" class="text-3xl font-black text-gray-800 tracking-tight transition-all"><?= count($data['devices']); ?></h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Total Devices</p>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-white to-emerald-50/50 rounded-2xl shadow-sm border border-emerald-100 p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-200">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-emerald-100/50 text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-live-pulse"></span> LIVE</span>
        </div>
        <div>
            <h3 id="statSent" class="text-3xl font-black text-gray-800 tracking-tight transition-all"><?= $data['stats']['sent'] ?? 0; ?></h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Pesan Terkirim</p>
        </div>
    </div>

    <div class="bg-gradient-to-br from-white to-amber-50/50 rounded-2xl shadow-sm border border-amber-100 p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-200">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-amber-100/50 text-amber-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-live-pulse"></span> LIVE</span>
        </div>
        <div>
            <h3 id="statReceived" class="text-3xl font-black text-gray-800 tracking-tight transition-all"><?= $data['stats']['received'] ?? 0; ?></h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Pesan Diterima</p>
        </div>
    </div>

    <div class="bg-gradient-to-br from-white to-rose-50/50 rounded-2xl shadow-sm border border-rose-100 p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-rose-200">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-rose-100/50 text-rose-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div>
            <h3 id="statFailed" class="text-3xl font-black text-gray-800 tracking-tight transition-all"><?= $data['stats']['failed'] ?? 0; ?></h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Pesan Gagal</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-50 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h3 class="text-lg font-bold text-gray-800 tracking-tight">Your Connected Devices</h3>
            <p class="text-sm text-gray-500 mt-0.5">Manage and monitor all your active WhatsApp sessions.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200/60 text-xs font-semibold text-emerald-700 select-none">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-live-pulse"></span> Auto-Sync: ON
            </div>
            <a href="<?= BASEURL; ?>/device" class="inline-flex items-center justify-center text-sm font-bold text-white bg-gray-900 hover:bg-gray-800 px-4 py-2 rounded-xl transition-colors w-full sm:w-auto">View All Devices</a>
        </div>
    </div>
    <div class="p-6">
        <table id="deviceTable" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-100">
                    <th class="py-4 px-5 font-semibold">Device Name</th>
                    <th class="py-4 px-5 font-semibold">Phone Number</th>
                    <th class="py-4 px-5 font-semibold">Status</th>
                    <th class="py-4 px-5 font-semibold">Last Seen</th>
                </tr>
            </thead>
            <tbody id="deviceTableBody" class="divide-y divide-gray-50">
                <?php if(!empty($data['devices'])): ?>
                    <?php foreach($data['devices'] as $dev): ?>
                    <tr class="hover:bg-gray-50/60 transition-colors" data-device-id="<?= $dev['id'] ?>">
                        <td class="py-4 px-5"><span class="font-semibold text-gray-800"><?= htmlspecialchars($dev['name']); ?></span></td>
                        <td class="py-4 px-5 text-gray-600"><?= htmlspecialchars($dev['phone'] ?: '-'); ?></td>
                        <td class="py-4 px-5">
                            <?php if($dev['status'] == 'connected'): ?>
                                <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-md text-xs font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Connected
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> <?= ucfirst($dev['status']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-5 text-sm text-gray-500 font-medium"><?= $dev['last_seen'] ? date('d M Y, H:i', strtotime($dev['last_seen'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#deviceTable')) {
        $('#deviceTable').DataTable().destroy();
    }
    if ($('#deviceTable').length && $.fn.DataTable) {
        $('#deviceTable').DataTable({
            responsive: true,
            lengthChange: false,
            pageLength: 5,
            info: false,
            searching: false,
            language: { emptyTable: "No data available in table" }
        });
    }

    // Auto-update dashboard stats & devices via AJAX
    window.fetchDashboardStats = function() {
        $.ajax({
            url: BASEURL + '/dashboard/stats_ajax',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res && res.status === 'success') {
                    if (res.stats) {
                        $('#statSent').text(res.stats.sent || 0);
                        $('#statReceived').text(res.stats.received || 0);
                        $('#statFailed').text(res.stats.failed || 0);
                    }
                    if (typeof res.total_devices !== 'undefined') {
                        $('#statTotalDevices').text(res.total_devices);
                    }
                }
            }
        });
    };

    if (typeof window.registerInterval === 'function') {
        window.registerInterval(window.fetchDashboardStats, 5000);
    } else {
        setInterval(window.fetchDashboardStats, 5000);
    }
})();
</script>
