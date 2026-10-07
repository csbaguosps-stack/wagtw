/**
 * WAGTW - AJAX SPA Engine & UI Handler
 * 
 * Coding by: cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-Requested-With': 'XMLHttpRequest' } });

    // --- Global Top Progress Bar Helpers ---
    function showProgress() {
        $('#topProgressBar').css({ opacity: 1, width: '30%', transition: 'width 0.4s ease' });
        setTimeout(() => {
            if ($('#topProgressBar').css('opacity') === '1') {
                $('#topProgressBar').css({ width: '70%', transition: 'width 2s ease' });
            }
        }, 400);
    }
    
    function hideProgress() {
        $('#topProgressBar').css({ width: '100%', transition: 'width 0.2s ease' });
        setTimeout(() => {
            $('#topProgressBar').css({ opacity: 0, transition: 'opacity 0.3s ease' });
            setTimeout(() => { $('#topProgressBar').css({ width: '0%' }); }, 300);
        }, 200);
    }

    // --- Global Interval Manager to prevent ghost timers between SPA views ---
    window.appIntervals = window.appIntervals || [];
    window.registerInterval = function(fn, ms) {
        const id = setInterval(fn, ms);
        window.appIntervals.push(id);
        return id;
    };
    window.clearAppIntervals = function() {
        if (window.appIntervals && Array.isArray(window.appIntervals)) {
            window.appIntervals.forEach(t => clearInterval(t));
            window.appIntervals = [];
        }
    };

    // --- Helper to Normalize Relative & Subdirectory URLs ---
    window.normalizeAppUrl = function(url) {
        if (!url) return typeof BASEURL !== 'undefined' ? BASEURL : '';
        if (url.startsWith('http://') || url.startsWith('https://')) return url;
        
        if (typeof BASEURL !== 'undefined' && BASEURL) {
            const base = BASEURL.replace(/\/+$/, '');
            if (url.startsWith('/')) {
                try {
                    const baseUrlObj = new URL(base);
                    const basePath = baseUrlObj.pathname.replace(/\/+$/, '');
                    if (basePath && url.startsWith(basePath)) {
                        return baseUrlObj.origin + url;
                    }
                } catch(e) {}
                return base + url;
            } else {
                return base + '/' + url;
            }
        }
        return url;
    };

    // --- Global Load Page Helper (SPA Engine) ---
    window.loadPage = function(url, pushState = true) {
        url = window.normalizeAppUrl(url);

        if(url.includes('logout') || url.includes('download') || url.includes('export')) {
            window.location.href = url;
            return;
        }

        // Clear active intervals from prior view
        window.clearAppIntervals();

        showProgress();

        $.ajax({
            url: url,
            type: 'GET',
            data: { ajax: true },
            cache: false,
            success: function(response) {
                hideProgress();
                $('#ajaxContentProvider').html(response).scrollTop(0);
                
                if(pushState) {
                    window.history.pushState({path: url}, '', url);
                }

                // Auto update Sidebar Active State & Page Title
                updateSidebarActive(url);
            },
            error: function() {
                hideProgress();
                Swal.fire('Error', 'Gagal memuat halaman, coba lagi.', 'error');
            }
        });
    };

    function updateSidebarActive(url) {
        if (!url) url = window.location.href;

        // Clean query strings & hashes & trailing slashes
        const path = url.split('?')[0].split('#')[0].replace(/\/+$/, '');

        let matched = null;
        let longestMatchLength = 0;

        $('.nav-link').each(function() {
            const linkHref = $(this).attr('href');
            if (!linkHref) return;
            const linkPath = linkHref.split('?')[0].split('#')[0].replace(/\/+$/, '');
            const target = $(this).data('target');

            if (path === linkPath || path.startsWith(linkPath + '/')) {
                if (linkPath.length > longestMatchLength) {
                    matched = $(this);
                    longestMatchLength = linkPath.length;
                }
            } else if (target && (path.endsWith('/' + target) || path.includes('/' + target + '/'))) {
                if (!matched) matched = $(this);
            }
        });

        // Fallback to dashboard if at root
        if (!matched && (path === (typeof BASEURL !== 'undefined' ? BASEURL.replace(/\/+$/, '') : '') || path.endsWith('/public') || path.endsWith('/wagtw') || path.endsWith('/'))) {
            matched = $('.nav-link[data-target="dashboard"]');
        }

        if (matched && matched.length) {
            // Remove active classes from all links
            $('.nav-link')
                .removeClass('bg-emerald-50 text-emerald-700 font-semibold active-nav bg-gray-100 text-gray-700')
                .addClass('text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium');

            // Set emerald active styling for matched link
            matched
                .removeClass('text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium')
                .addClass('bg-emerald-50 text-emerald-700 font-semibold active-nav');

            // Update Header Title from link text if appropriate
            const text = matched.contents().filter(function() {
                return this.nodeType === 3;
            }).text().trim();
            if (text && $('#pageTitle').length) {
                const currentTitle = $('#pageTitle').text().trim();
                if (!currentTitle || currentTitle.toLowerCase().includes(text.toLowerCase())) {
                    $('#pageTitle').text(text);
                }
            }
        }
    }

    // Handle Login Submit
    $(document).on('submit', '#formLogin', function(e) {
        e.preventDefault();
        const btn = $('#btnLogin');
        const loader = $('#btnLoader');
        const btnText = btn.find('span');

        btn.prop('disabled', true);
        btnText.text('Memproses...');
        loader.removeClass('hidden');

        $.ajax({
            url: BASEURL + '/auth/doLogin',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, showConfirmButton: false, timer: 1500 })
                    .then(() => { window.location.href = response.redirect; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message });
                    btn.prop('disabled', false); btnText.text('Sign In'); loader.addClass('hidden');
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan sistem.' });
                btn.prop('disabled', false); btnText.text('Sign In'); loader.addClass('hidden');
            }
        });
    });

    // Handle SPA Navigation for ALL local links
    $(document).on('click', 'a', function(e) {
        const url = $(this).attr('href');
        
        // Ignore links without href, anchors, javascript, opens in new tab, download attribute, or download/export URLs
        if (!url || url.startsWith('#') || url.startsWith('javascript:') || $(this).attr('target') === '_blank' || typeof $(this).attr('data-no-ajax') !== 'undefined' || typeof $(this).attr('download') !== 'undefined' || $(this).is('[download]') || url.includes('download') || url.includes('export')) {
            return;
        }
        
        // Ignore external domain links
        const domainURL = url.toLowerCase();
        if (domainURL.startsWith('http') && !domainURL.includes(BASEURL.toLowerCase())) {
            return;
        }

        e.preventDefault();

        // Chat halaman butuh full page load agar SPA engine-nya bisa init dengan benar
        const cleanUrl = url.split('?')[0].replace(/\/$/, '');
        const isChat = cleanUrl.endsWith('/chat') || url.includes('/chat?');
        if (isChat) {
            window.location.href = url;
            return;
        }

        loadPage(url);
    });

    // Handle Browser Back Button for SPA
    window.addEventListener('popstate', function(e) {
        if(e.state && e.state.path) {
            loadPage(e.state.path, false);
        } else {
            // Default load current location if no state
            loadPage(window.location.pathname, false);
        }
    });

    // --- Global Reload Current Page Helper ---
    window.reloadCurrentPage = function() {
        if (typeof window.loadPage === 'function') {
            window.loadPage(window.location.href, false);
        } else {
            location.reload();
        }
    };

    // --- Audio Notification Synthesizer (Web Audio API) ---
    let soundEnabled = localStorage.getItem('wagtw_sound_enabled') !== 'false';
    function updateSoundButton() {
        if (soundEnabled) {
            $('#soundIconOn').removeClass('hidden');
            $('#soundIconOff').addClass('hidden');
        } else {
            $('#soundIconOn').addClass('hidden');
            $('#soundIconOff').removeClass('hidden');
        }
    }
    $(document).on('click', '#toggleNotificationSound', function() {
        soundEnabled = !soundEnabled;
        localStorage.setItem('wagtw_sound_enabled', soundEnabled);
        updateSoundButton();
        if (soundEnabled) playNotificationSound();
    });
    updateSoundButton();

    function playNotificationSound() {
        if (!soundEnabled) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) {}
    }
    window.playNotificationSound = playNotificationSound;

    // --- Live Toast Notification Engine ---
    window.showLiveToast = function(title, message, type = 'info') {
        const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        const iconBg = type === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600';
        const toastHtml = `
            <div id="${id}" class="wagtw-toast bg-white/95 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100 p-4 flex items-start gap-3 transition-all">
                <div class="p-2 rounded-xl ${iconBg} shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-gray-800 tracking-tight truncate">${title}</h5>
                        <span class="text-[10px] text-gray-400">Baru saja</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1 line-clamp-2 leading-relaxed">${message}</p>
                </div>
                <button onclick="$('#${id}').removeClass('show'); setTimeout(() => $('#${id}').remove(), 350);" class="text-gray-400 hover:text-gray-600 p-1 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;
        $('#wagtwToastContainer').prepend(toastHtml);
        setTimeout(() => { $(`#${id}`).addClass('show'); }, 30);
        setTimeout(() => {
            $(`#${id}`).removeClass('show');
            setTimeout(() => { $(`#${id}`).remove(); }, 400);
        }, 5000);
    };

    // --- Background Live Message Poller ---
    let lastKnownMsgId = 0;
    let isPolling = false;

    // Inisialisasi ID pesan terakhir
    $.get(BASEURL + '/messagehistory/check_new', function(res) {
        if (res && res.latest_id) {
            lastKnownMsgId = res.latest_id;
        }
    });

    function pollNewMessages() {
        if (isPolling) return;
        isPolling = true;

        $.ajax({
            url: BASEURL + '/messagehistory/check_new',
            type: 'GET',
            data: { since_id: lastKnownMsgId },
            dataType: 'json',
            timeout: 5000,
            success: function(res) {
                if (res && res.has_new && Array.isArray(res.new_messages) && res.new_messages.length > 0) {
                    lastKnownMsgId = res.latest_id;
                    
                    // Mainkan suara & tampilkan toast
                    playNotificationSound();
                    const latestMsg = res.new_messages[0];
                    const sender = latestMsg.direction === 'out' ? latestMsg.to_phone : latestMsg.from_phone;
                    const preview = latestMsg.message || 'Pesan baru diterima';
                    const dirLabel = latestMsg.direction === 'in' ? '📨 Pesan Masuk' : '📤 Pesan Keluar';
                    window.showLiveToast(`${dirLabel} (+${sender})`, preview);

                    // Auto-reload DataTable jika sedang di halaman Message History
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#historyTable')) {
                        $('#historyTable').DataTable().ajax.reload(null, false);
                    }

                    // Auto-reload Dashboard jika sedang di halaman Dashboard
                    if (typeof window.fetchDashboardStats === 'function') {
                        window.fetchDashboardStats();
                    }

                    // Dispatch custom event untuk listener lain di halaman mana pun
                    window.dispatchEvent(new CustomEvent('wagtw:new_message', { detail: res.new_messages }));
                } else if (res && res.latest_id) {
                    lastKnownMsgId = Math.max(lastKnownMsgId, res.latest_id);
                }
            },
            complete: function() {
                isPolling = false;
            }
        });
    }

    // Jalankan polling setiap 3.5 detik
    setInterval(pollNewMessages, 3500);

    // Initial Active Check
    updateSidebarActive(window.location.href);

    // --- Mobile Sidebar Logic ---
    const sidebar = $('#mainSidebar');
    const sidebarOverlay = $('#mobileSidebarOverlay');

    function openSidebar() {
        sidebar.removeClass('-translate-x-full');
        sidebarOverlay.removeClass('hidden');
        setTimeout(() => sidebarOverlay.removeClass('opacity-0').addClass('opacity-100'), 10);
    }

    function closeSidebar() {
        sidebar.addClass('-translate-x-full');
        sidebarOverlay.removeClass('opacity-100').addClass('opacity-0');
        setTimeout(() => sidebarOverlay.addClass('hidden'), 300);
    }

    $(document).on('click', '#openSidebarBtn', openSidebar);
    $(document).on('click', '#closeSidebarBtn, #mobileSidebarOverlay, .nav-link', function(e) {
        if(window.innerWidth < 768) {
            closeSidebar();
        }
    });

});
