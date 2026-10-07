<!-- SheetJS for Excel Export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-6rem)] sm:h-[calc(100vh-8rem)]">
    <!-- Header / Filter Area -->
    <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-gray-800">History Pesan</h2>
            <p class="text-xs sm:text-sm text-gray-500">Log semua riwayat pesan masuk dan keluar</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <select id="filterDevice" class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-700 shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                <option value="">All Devices</option>
                <?php if (isset($data['devices']) && is_array($data['devices'])): ?>
                    <?php foreach ($data['devices'] as $dev): ?>
                        <option value="<?= $dev['id'] ?>"><?= htmlspecialchars($dev['name']) ?> (<?= htmlspecialchars($dev['phone'] ?? '-') ?>)</option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            
            <button type="button" id="btnExportXLS" onclick="window.exportHistoryXLS()" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-xs shrink-0 active:scale-95 cursor-pointer" title="Export riwayat pesan ke Excel (.xls)">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zm-1.5 8.5l1.8 3h-1.6l-1-1.8l-1 1.8H8.1l1.8-3l-1.7-3h1.6l.9 1.7l.9-1.7h1.6l-1.7 3z"/></svg>
                <span>Excel</span>
            </button>
            <button type="button" id="btnExportCSV" onclick="window.exportHistoryCSV()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition border border-slate-200 shadow-xs flex items-center gap-1.5 shrink-0 active:scale-95 cursor-pointer" title="Export riwayat pesan ke CSV">
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>CSV</span>
            </button>
            <button id="btnRefresh" class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl font-bold text-xs transition flex items-center gap-1 shrink-0">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button id="btnDeleteSelected" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/60 rounded-xl font-bold text-xs transition flex items-center gap-1 shrink-0">
                <i class="fas fa-trash-alt"></i> Hapus Terpilih
            </button>
            <button id="btnDeleteAll" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-1 shrink-0">
                <i class="fas fa-dumpster"></i> Hapus Semua
            </button>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-xl text-xs font-bold shadow-xs select-none shrink-0" title="Tabel otomatis memuat pesan baru secara live">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-live-pulse"></span>
                <span>Live Update</span>
            </div>
        </div>
    </div>

    <!-- Table Content -->
    <div class="flex-1 overflow-auto p-6">
        <table id="historyTable" class="w-full text-left border-collapse" style="width: 100%;">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100" style="width: 30px;">
                        <input type="checkbox" id="cb-all" class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                    </th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Id</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Time</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Device</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Target</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Contact</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Type</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100" style="width: 25%;">Message</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Url</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Status</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">State</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Action</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700">
                <!-- DataTables Content -->
            </tbody>
        </table>
    </div>
</div>

<!-- jQuery & DataTables -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    var table = $('#historyTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": BASEURL + "/messagehistory/ajax_list",
            "type": "POST",
            "data": function(d) {
                d.device_id = $('#filterDevice').val();
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [0, 5, 8, 11] },
            { "className": "py-3 px-4 align-top border-b border-gray-50 text-gray-600", "targets": "_all" }
        ],
        "order": [[ 1, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "",
            "searchPlaceholder": "Search...",
            "lengthMenu": "Show _MENU_",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "paginate": {
                "previous": "<i class='fas fa-chevron-left text-xs'></i>",
                "next": "<i class='fas fa-chevron-right text-xs'></i>"
            }
        },
        "drawCallback": function() {
            // Style pagination
            $('.dataTables_paginate .paginate_button').addClass('px-3 py-1.5 mx-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 cursor-pointer');
            $('.dataTables_paginate .paginate_button.current').removeClass('bg-gray-50 text-gray-600').addClass('bg-emerald-500 text-white border-emerald-500');
            $('.dataTables_paginate .paginate_button.disabled').addClass('opacity-50 cursor-not-allowed').removeClass('hover:bg-gray-50');
            $('#cb-all').prop('checked', false);
        }
    });

    // Custom Search Input Styling
    $('.dataTables_filter input').addClass('w-full md:w-64 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all');
    $('.dataTables_length select').addClass('px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all');

    $('#filterDevice').change(function() {
        table.ajax.reload();
    });

    $('#btnRefresh').click(function() {
        table.ajax.reload(null, false);
    });

    // Auto-reload tabel setiap 3 detik via AJAX tanpa me-refresh browser
    const reloadTimer = function() {
        if (!$('.dataTables_filter input').is(':focus') && !$('.cb-child:checked').length) {
            table.ajax.reload(null, false);
        }
    };
    if (typeof window.registerInterval === 'function') {
        window.registerInterval(reloadTimer, 3000);
    } else {
        setInterval(reloadTimer, 3000);
    }

    // Instant reload saat ada pesan masuk baru dari background listener
    window.addEventListener('wagtw:new_message', function() {
        table.ajax.reload(null, false);
    });

    // Checkbox All
    $('#cb-all').click(function() {
        $('.cb-child').prop('checked', $(this).prop('checked'));
    });

    // Bulk Delete
    $('#btnDeleteSelected').click(function() {
        var ids = [];
        $('.cb-child:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            Swal.fire('Oops', 'Pilih minimal satu pesan untuk dihapus', 'warning');
            return;
        }

        Swal.fire({
            title: 'Hapus Pesan?',
            text: ids.length + " pesan terpilih akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASEURL + '/messagehistory/bulk_delete',
                    type: 'POST',
                    data: { ids: ids },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Terhapus!', res.message, 'success');
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Delete All
    $('#btnDeleteAll').click(function() {
        Swal.fire({
            title: 'Hapus SEMUA Pesan?',
            text: "Ini akan menghapus SELURUH riwayat pesan di database!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Kosongkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASEURL + '/messagehistory/delete_all',
                    type: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Terhapus!', res.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});

function deleteMessage(id) {
    Swal.fire({
        title: 'Hapus Pesan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: BASEURL + '/messagehistory/delete',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        $('#historyTable').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus', 'error');
                    }
                }
            });
        }
    });
}

function resendMessage(id) {
    Swal.fire({
        title: 'Kirim Ulang Pesan?',
        text: "Pesan ini akan diantrikan untuk dikirim ulang via Node.js",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Kirim!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Mengirim...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            $.ajax({
                url: BASEURL + '/messagehistory/resend',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Berhasil', res.message, 'success');
                        $('#historyTable').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
                }
            });
        }
    });
}

window.exportHistoryXLS = function() {
    const deviceId = $('#filterDevice').val() || '';
    const baseUrl = (typeof BASEURL !== 'undefined' ? BASEURL : '');
    const url = baseUrl + '/messagehistory/download_export?format=xls&device_id=' + encodeURIComponent(deviceId);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'Menyiapkan File Excel...',
            text: 'File riwayat pesan sedang diunduh secara otomatis.',
            timer: 1600,
            showConfirmButton: false
        });
    }

    const a = document.createElement('a');
    a.href = url;
    a.setAttribute('download', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
};

window.exportHistoryCSV = function() {
    const deviceId = $('#filterDevice').val() || '';
    const baseUrl = (typeof BASEURL !== 'undefined' ? BASEURL : '');
    const url = baseUrl + '/messagehistory/download_export?format=csv&device_id=' + encodeURIComponent(deviceId);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'Menyiapkan File CSV...',
            text: 'File CSV riwayat pesan sedang diunduh secara otomatis.',
            timer: 1600,
            showConfirmButton: false
        });
    }

    const a = document.createElement('a');
    a.href = url;
    a.setAttribute('download', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
};

// Delegated jQuery clicks to ensure clicks are caught regardless of DOM state
$(document).off('click', '#btnExportXLS').on('click', '#btnExportXLS', function(e) {
    e.preventDefault();
    window.exportHistoryXLS();
});

$(document).off('click', '#btnExportCSV').on('click', '#btnExportCSV', function(e) {
    e.preventDefault();
    window.exportHistoryCSV();
});

function escapeMessageDetail(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character];
    });
}

$(document).off('click', '.message-detail-trigger').on('click', '.message-detail-trigger', function() {
    const button = $(this);
    const detail = {
        message: escapeMessageDetail(button.attr('data-message')),
        time: escapeMessageDetail(button.attr('data-time')),
        device: escapeMessageDetail(button.attr('data-device')),
        direction: escapeMessageDetail(button.attr('data-direction')),
        number: escapeMessageDetail(button.attr('data-number')),
        type: escapeMessageDetail(button.attr('data-type')),
        status: escapeMessageDetail(button.attr('data-status'))
    };

    Swal.fire({
        title: 'Detail Pesan',
        html: `
            <div class="text-left space-y-4">
                <div class="flex flex-wrap gap-2">
                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">${detail.direction}</span>
                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">${detail.type}</span>
                    <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">${detail.status}</span>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-xl border border-slate-100 bg-slate-50 p-4 text-left">
                    <div><div class="text-[10px] font-bold uppercase text-slate-400">Waktu</div><div class="mt-1 text-sm font-medium text-slate-700">${detail.time}</div></div>
                    <div><div class="text-[10px] font-bold uppercase text-slate-400">Device</div><div class="mt-1 text-sm font-medium text-slate-700 break-words">${detail.device}</div></div>
                    <div class="col-span-2"><div class="text-[10px] font-bold uppercase text-slate-400">Nomor</div><div class="mt-1 text-sm font-medium text-slate-700 break-all">${detail.number}</div></div>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-white p-4 text-left shadow-sm">
                    <div class="mb-2 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Isi Pesan</div>
                    <div class="max-h-[40vh] overflow-y-auto text-sm leading-relaxed text-slate-700" style="white-space: pre-wrap; overflow-wrap: anywhere;">${detail.message || '<span class="italic text-slate-400">Pesan kosong</span>'}</div>
                </div>
            </div>
        `,
        width: 620,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#059669',
        buttonsStyling: true,
        customClass: {
            popup: 'rounded-2xl',
            title: 'text-gray-800',
            htmlContainer: 'mx-0'
        }
    });
});
</script>
